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

namespace Surfnet\Gssp\Infrastructure\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles the lang selection based on cookie.
 */
final readonly class LocaleResponseListener implements EventSubscriberInterface
{
    public const STEPUP_LOCALE_COOKIE = 'stepup_locale';

    public function __construct(private TranslatorInterface $translator, private RequestStack $requestStack)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse'],
            KernelEvents::REQUEST => ['onKernelRequest'],
        ];
    }

    /**
     * Sets the application local based on stepup cookie.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $local = $request->cookies->get(self::STEPUP_LOCALE_COOKIE, $request->getLocale());
        $request->setLocale($local);
        $this->translator->setLocale($local);
    }

    /**
     * Preserves the current selected local as user cookie.
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $this->requestStack->getMainRequest();
        $response = $event->getResponse();
        $cookie = Cookie::create(self::STEPUP_LOCALE_COOKIE, $request->getLocale(), 0, '/', $this->getNakedDomain());
        $response->headers->setCookie($cookie);
    }

    /**
     * Return's the naked domain for the cookie variables so that is shared between the different sub-domains.
     *
     * @return string
     */
    private function getNakedDomain(): string
    {
        $masterRequest = $this->requestStack->getMainRequest();
        $host = $masterRequest->getHost();
        $parts = explode('.', (string) $host);
        return implode('.', array_slice($parts, -2, 2));
    }
}
