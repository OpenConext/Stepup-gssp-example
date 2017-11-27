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
use Surfnet\GsspBundle\Service\AuthenticationRegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    private $authenticationRegistrationService;

    public function __construct(
        AuthenticationRegistrationService $authenticationRegistrationService
    ) {
        $this->authenticationRegistrationService = $authenticationRegistrationService;
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
            $this->authenticationRegistrationService->register($request->get('NameID'));
            return $this->authenticationRegistrationService->createRedirectResponse();
        }

        if ($request->get('action') === 'error') {
            $this->authenticationRegistrationService->error($request->get('message'));
            return $this->authenticationRegistrationService->createRedirectResponse();
        }

        $requiresRegistration = $this->authenticationRegistrationService->requiresRegistration();
        $response = new Response(null, $requiresRegistration ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);

        return $this->render('AppBundle:default:registration.html.twig', [
            'requiresRegistration' => $requiresRegistration,
            'NameID' => uniqid('test-prefix-', 'test-entropy'),
        ], $response);
    }
}
