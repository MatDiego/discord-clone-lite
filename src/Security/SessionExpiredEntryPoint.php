<?php

declare(strict_types=1);

namespace App\Security;

use Override;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Redirects unauthenticated users to the login page.
 *
 * If the browser still carries a session cookie (meaning the session
 * expired server-side), the redirect includes ?expired=1 so the login
 * page can display an informative message.
 */
final readonly class SessionExpiredEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Override]
    public function start(Request $request, ?AuthenticationException $authException = null): RedirectResponse
    {
        $hasSessionCookie = $request->cookies->has($request->getSession()->getName());

        return new RedirectResponse(
            $this->urlGenerator->generate('app_login', $hasSessionCookie ? ['expired' => 1] : []),
        );
    }
}
