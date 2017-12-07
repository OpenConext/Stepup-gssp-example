<?php
/**
 * Copyright 2017 SURFnet B.V.
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

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Surfnet\GsspBundle\Service\AuthenticationService;
use Surfnet\GsspBundle\Service\RegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    private $authenticatinService;
    private $registrationService;

    public function __construct(
        AuthenticationService $authenticationService,
        RegistrationService $registrationService
    ) {
        $this->authenticatinService = $authenticationService;
        $this->registrationService = $registrationService;
    }

    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
        ]);
    }

    /**
     * @Route("/registration", name="app_identity_registration")
     *
     * @throws \InvalidArgumentException
     */
    public function registrationAction(Request $request)
    {
        // replace this example code with whatever you need
        if ($request->get('action') === 'register') {
            $this->registrationService->register($request->get('NameID'));
            return $this->registrationService->replyToServiceProvider();
        }

        if ($request->get('action') === 'error') {
            $this->registrationService->reject($request->get('message'));
            return $this->registrationService->replyToServiceProvider();
        }

        $requiresRegistration = $this->registrationService->registrationRequired();
        $response = new Response(null, $requiresRegistration ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);

        return $this->render('AppBundle:default:registration.html.twig', [
            'requiresRegistration' => $requiresRegistration,
            'NameID' => uniqid('test-prefix-', 'test-entropy'),
        ], $response);
    }

    /**
     * @Route("/authentication", name="app_identity_authentication")
     *
     * @throws \InvalidArgumentException
     */
    public function authenticationAction(Request $request)
    {
        $nameId = $this->authenticatinService->getNameId();

        // replace this example code with whatever you need
        if ($request->get('action') === 'authenticate') {
            // Implement the logic to verify authentication by the corresponding nameId.
            $this->authenticatinService->authenticate();
            return $this->authenticatinService->replyToServiceProvider();
        }

        if ($request->get('action') === 'error') {
            $this->authenticatinService->reject($request->get('message'));
            return $this->authenticatinService->replyToServiceProvider();
        }

        $requiresRegistration = $this->authenticatinService->authenticationRequired();
        $response = new Response(null, $requiresRegistration ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);

        return $this->render('AppBundle:default:authentication.html.twig', [
            'requiresAuthentication' => $requiresRegistration,
            'NameID' => $nameId ?: 'unknown',
        ], $response);
    }
}
