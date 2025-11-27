<?php

namespace SocialDept\AtpClient\Exceptions;

use Illuminate\Http\Request;
use SocialDept\AtpClient\Enums\ScopeAuthorizationFailure;
use Symfony\Component\HttpFoundation\Response;

class ScopeAuthorizationException extends MissingScopeException
{
    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): Response
    {
        $action = config('atp-client.scope_authorization.failure_action', ScopeAuthorizationFailure::Abort);

        return match ($action) {
            ScopeAuthorizationFailure::Redirect => redirect(
                config('atp-client.scope_authorization.redirect_to', '/login')
            ),
            ScopeAuthorizationFailure::Exception => throw $this,
            default => abort(403, $this->getMessage()),
        };
    }
}
