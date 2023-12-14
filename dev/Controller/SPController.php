<?php
/**
 * Copyright 2019 SURFnet B.V.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Dev\Controller;

use Exception;
use SAML2\Response as SamlResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use DOMDocument;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\Certificate\PrivateKeyLoader;
use SAML2\Configuration\PrivateKey;
use SAML2\DOMDocumentFactory;
use SAML2\Message;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\SamlBundle\Http\Exception\AuthnFailedSamlResponseException;
use Surfnet\SamlBundle\Http\PostBinding;
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Surfnet\SamlBundle\SAML2\AuthnRequestFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use function is_bool;
use function is_string;

/**
 * Demo SP.
 */
final class SPController extends AbstractController
{
    public function __construct(
        private readonly ServiceProvider $serviceProvider,
        private readonly IdentityProvider $identityProvider,
        private readonly PostBinding $postBinding,
    ) {
    }

    #[Route(path: '/demo/sp', name: 'sp_demo')]
    public function demoSp(Request $request): Response|RedirectResponse
    {
        if (!$request->isMethod(Request::METHOD_POST)) {
            return $this->render('dev/sp.html.twig');
        }
        $authnRequest = AuthnRequestFactory::createNewRequest($this->serviceProvider, $this->identityProvider);

        // Set nameId when we want to authenticate.
        if ($request->get('action') === 'authenticate') {
            $nameId = $request->get('NameID');
            if (!is_string($nameId)) {
                throw new Exception('NameID is not a string..');
            }
            $authnRequest->setSubject($nameId);
        }

        // Build request query parameters.
        $requestAsXml = $authnRequest->getUnsignedXML();
        $deflated = gzdeflate($requestAsXml);
        if (is_bool($deflated)) {
            throw new Exception('Unable to deflate $requestAsXml');
        }
        $encodedRequest = base64_encode($deflated);
        $queryParams = [AuthnRequest::PARAMETER_REQUEST => $encodedRequest];
        $relayState = $request->get(AuthnRequest::PARAMETER_RELAY_STATE);
        if (!empty($relayState)) {
            $queryParams[AuthnRequest::PARAMETER_RELAY_STATE] = $relayState;
        }

        // Create redirect response.
        $query = $this->signRequestQuery($queryParams);
        $url = sprintf('%s?%s', $this->identityProvider->getSsoUrl(), $query);
        $response = new RedirectResponse($url);

        // Set Stepup request id header.
        $stepupRequestId = $request->get('X-Stepup-Request-Id');
        if (!empty($stepupRequestId)) {
            $response->headers->set('X-Stepup-Request-Id', $stepupRequestId);
        }

        return $response;
    }

    #[Route(path: '/demo/sp/acs', name: 'sp_demo_acs')]
    public function assertionConsumerService(Request $request): Response
    {
        $xmlResponse = $request->request->get('SAMLResponse');
        $xml = base64_decode($xmlResponse);
        if (!is_string($xml)) {
            throw new Exception('Unable to base64 decode the xml response');
        }
        try {
            $response = $this->postBinding->processResponse($request, $this->identityProvider, $this->serviceProvider);

            $nameID = $response->getNameId();

            return $this->render('dev/acs.html.twig', [
                'requestId' => $response->getId(),
                'nameId' => $nameID !== null ? [
                    'value' => $nameID->getValue(),
                    'format' => $nameID->getFormat(),
                ] : [],
                'issuer' => $response->getIssuer(),
                'relayState' => $request->get(AuthnRequest::PARAMETER_RELAY_STATE, ''),
                'authenticatingAuthority' => $response->getAuthenticatingAuthority(),
                'xml' => $this->toFormattedXml($xml),
            ]);
        } catch (AuthnFailedSamlResponseException $e) {
            $samlResponse = $this->toUnsignedErrorResponse($xml);

            return $this->render('dev/acs-error-response.html.twig', [
                'error' => $e->getMessage(),
                'status' => $samlResponse->getStatus(),
                'requestId' => $samlResponse->getId(),
                'issuer' => $samlResponse->getIssuer()->getValue(),
                'relayState' => $request->get(AuthnRequest::PARAMETER_RELAY_STATE, ''),
                'xml' => $this->toFormattedXml($xml),
            ]);
        }
    }

    /**
     * Formats xml.
     */
    private function toFormattedXml(string $xml): string
    {
        $domxml = new DOMDocument('1.0');
        $domxml->preserveWhiteSpace = false;
        $domxml->formatOutput = true;
        $domxml->loadXML($xml);

        $result = $domxml->saveXML();
        if (!is_string($result)) {
            throw new Exception('Unable to create valid XML from the provided document');
        }
        return $result;
    }

    /**
     * Sign AuthnRequest query parameters.
     */
    private function signRequestQuery(array $queryParams): string
    {
        $securityKey = $this->loadServiceProviderPrivateKey();
        $queryParams[AuthnRequest::PARAMETER_SIGNATURE_ALGORITHM] = $securityKey->type;
        $toSign = http_build_query($queryParams);
        $signature = $securityKey->signData($toSign);
        if (!is_string($signature)) {
            throw new Exception('Unable to sign the AuthnRequest');
        }
        return $toSign.'&Signature='.urlencode(base64_encode($signature));
    }

    /**
     * Loads the private key from the service provider.
     */
    private function loadServiceProviderPrivateKey(): XMLSecurityKey
    {
        $keyLoader = new PrivateKeyLoader();
        $privateKey = $keyLoader->loadPrivateKey(
            $this->serviceProvider->getPrivateKey(PrivateKey::NAME_DEFAULT)
        );
        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $key->loadKey($privateKey->getKeyAsString());

        return $key;
    }

    private function toUnsignedErrorResponse(string $xml): Message
    {
        $asXml = DOMDocumentFactory::fromString($xml);
        return SamlResponse::fromXML($asXml->documentElement);
    }
}
