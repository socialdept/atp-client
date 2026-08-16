<?php

namespace SocialDept\AtpClient\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use SocialDept\AtpClient\Auth\ScopeChecker;
use SocialDept\AtpClient\Contracts\HasAtpSession;
use SocialDept\AtpClient\Enums\ScopeAuthorizationFailure;
use SocialDept\AtpClient\Exceptions\ScopeAuthorizationException;
use SocialDept\AtpClient\Session\SessionManager;
use Symfony\Component\HttpFoundation\Response;

class RequiresScopeMiddleware
{
    public function __construct(
        protected SessionManager $sessions,
        protected ScopeChecker $checker,
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param  string  ...$scopes
     */
    public function handle(Request $request, Closure $next, string ...$scopes): Response
    {
        $user = $request->user();

        // Ensure user is authenticated
        if (! $user) {
            return $this->handleFailure(
                new ScopeAuthorizationException($scopes, [], 'User not authenticated.')
            );
        }

        // Ensure user implements HasAtpSession
        if (! $user instanceof HasAtpSession) {
            return $this->handleFailure(
                new ScopeAuthorizationException($scopes, [], 'User model must implement HasAtpSession interface.')
            );
        }

        $did = $user->getAtpDid();

        if (! $did) {
            return $this->handleFailure(
                new ScopeAuthorizationException($scopes, [], 'User has no ATP session.')
            );
        }

        try {
            $session = $this->sessions->session($did);
        } catch (\Exception $e) {
            return $this->handleFailure(
                new ScopeAuthorizationException($scopes, [], 'Could not retrieve ATP session: '.$e->getMessage())
            );
        }

        // Check ALL scopes (AND logic)
        if (! $this->checker->check($session, $scopes)) {
            $granted = $session->scopes();
            $missing = array_diff($scopes, $granted);

            return $this->handleFailure(
                new ScopeAuthorizationException($missing, $granted)
            );
        }

        return $next($request);
    }

    protected function handleFailure(ScopeAuthorizationException $exception): Response
    {
        $action = config('atp-client.scope_authorization.failure_action', ScopeAuthorizationFailure::Abort);

        return match ($action) {
            ScopeAuthorizationFailure::Redirect => redirect(
                config('atp-client.scope_authorization.redirect_to', '/login')
            ),
            ScopeAuthorizationFailure::Exception => throw $exception,
            default => abort(403, $exception->getMessage()),
        };
    }
}
