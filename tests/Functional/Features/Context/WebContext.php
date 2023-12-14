<?php

declare(strict_types = 1);

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

namespace Surfnet\Gssp\Test\Features\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Context\Context;
use Exception;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\AuthnRequest;
use SAML2\Certificate\PrivateKeyLoader;
use SAML2\Configuration\PrivateKey;
use SAML2\Constants;
use SAML2\XML\saml\Issuer;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\SamlBundle\Exception\NotFound;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;

class WebContext implements Context
{
    protected MinkContext $minkContext;

    protected string $previousMinkSession;

    public function __construct(protected KernelInterface $kernel)
    {
    }

    /**
     * Fetch the required contexts.
     *
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope): void
    {
        $environment = $scope->getEnvironment();
        $this->minkContext = $environment->getContext(MinkContext::class);
    }

    /**
     * Create AuthnRequest from demo IdP.
     *
     * @When the service provider send the AuthnRequest with HTTP-Redirect binding
     *
     * @throws NotFound
     */
    public function callIdentityProviderSSOActionWithAuthnRequest(): void
    {
        $this->minkContext->visit('https://pieter.aai.surfnet.nl/simplesamlphp/sp.php?sp=default-sp');
        $this->minkContext->selectOption('idp', 'https://demogssp.dev.openconext.local/saml/metadata');
        $this->minkContext->pressButton('Login');
    }

    public function getIdentityProvider(): IdentityProvider
    {
        /** @var RequestStack $stack */
        $stack = $this->kernel->getContainer()->get('request_stack');
        $stack->push(Request::create('https://demogssp.dev.openconext.local'));
        $ip = $this->kernel->getContainer()->get('surfnet_saml.hosted.identity_provider');
        $stack->pop();

        return $ip;
    }

    /**
     * @throws NotFound
     */
    public function getServiceProvider(): ServiceProvider
    {
        $serviceProviders = $this->kernel->getContainer()->get('surfnet_saml.remote.service_providers');
        return $serviceProviders->getServiceProvider(
            'https://pieter.aai.surfnet.nl/simplesamlphp/module.php/saml/sp/metadata.php/default-sp'
        );
    }

    /**
     * @Given /^a normal SAML 2.0 AuthnRequest form a unknown service provider$/
     *
     * @throws Exception
     */
    public function aNormalSAMLAuthnRequestFormAUnknownServiceProvider(): void
    {
        $issuer = new Issuer();
        $issuer->setValue('https://service_provider_unkown/saml/metadata');

        $authnRequest = new AuthnRequest();
        $authnRequest->setAssertionConsumerServiceURL('https://service_provider_unkown/saml/acs');
        $authnRequest->setDestination($this->getIdentityProvider()->getSsoUrl());
        $authnRequest->setIssuer($issuer);
        $authnRequest->setProtocolBinding(Constants::BINDING_HTTP_REDIRECT);

        // Sign with random key, does not mather for now.
        $authnRequest->setSignatureKey(
            $this->loadPrivateKey($this->getIdentityProvider()->getPrivateKey(PrivateKey::NAME_DEFAULT))
        );

        $request = \Surfnet\SamlBundle\SAML2\AuthnRequest::createNew($authnRequest);
        $query = $request->buildRequestQuery();
        $this->minkContext->visitPath('/saml/sso?' . $query);
    }

    /**
     * @throws Exception
     */
    private static function loadPrivateKey(PrivateKey $key): XMLSecurityKey
    {
        $keyLoader = new PrivateKeyLoader();
        $privateKey = $keyLoader->loadPrivateKey($key);

        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $key->loadKey($privateKey->getKeyAsString());

        return $key;
    }
}
