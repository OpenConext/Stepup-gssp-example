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

namespace Surfnet\Gssp\Infrastructure\Controller;

use Surfnet\GsspBundle\Service\AuthenticationService;
use Surfnet\GsspBundle\Service\RegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function is_string;

class DefaultController extends AbstractController
{
    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly RegistrationService $registrationService,
    ) {
    }

    /**
     * Replace this example code with whatever you need/
     */
    #[Route(path: '/', name: 'homepage')]
    public function index(): Response
    {
        return $this->render('default/index.html.twig');
    }

    /**
     * Replace this example code with whatever you need.
     *
     * See @see RegistrationService for a more clean example.
     */
    #[Route(path: '/registration', name: 'app_identity_registration')]
    public function registration(Request $request): Response
    {
        if ($request->get('action') === 'error') {
            $message = $request->get('message');
            if (!is_string($message)) {
                $message = '';
            }
            $this->registrationService->reject($message);
            return $this->registrationService->replyToServiceProvider();
        }

        if ($request->get('action') === 'register') {
            $nameId = $request->get('NameID');
            if (!is_string($nameId)) {
                $nameId = '';
            }
            $this->registrationService->register($nameId);
            return $this->registrationService->replyToServiceProvider();
        }

        $requiresRegistration = $this->registrationService->registrationRequired();
        $response = new Response(null, $requiresRegistration ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);

        return $this->render('default/registration.html.twig', [
            'requiresRegistration' => $requiresRegistration,
            'NameID' => uniqid('test-prefix-', true),
        ], $response);
    }

    /**
     * Replace this example code with whatever you need.
     *
     * See @see AuthenticationService for a more clean example.
     */
    #[Route(path: '/authentication', name: 'app_identity_authentication')]
    public function authentication(Request $request): Response
    {
        $nameId = $this->authenticationService->getNameId();

        if ($request->get('action') === 'error') {
            $message = $request->get('message');
            if (!is_string($message)) {
                $message = '';
            }
            $this->authenticationService->reject($message);
            return $this->authenticationService->replyToServiceProvider();
        }

        if ($request->get('action') === 'authenticate') {
            // The application should very if the user matches the nameId.
            $this->authenticationService->authenticate();
            return $this->authenticationService->replyToServiceProvider();
        }

        $requiresAuthentication = $this->authenticationService->authenticationRequired();
        $response = new Response(null, $requiresAuthentication ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);

        return $this->render('default/authentication.html.twig', [
            'requiresAuthentication' => $requiresAuthentication,
            'NameID' => $nameId ?: 'unknown',
        ], $response);
    }
}
