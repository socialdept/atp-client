<?php

namespace SocialDept\AtpClient\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use SocialDept\AtpClient\Contracts\HasAtpSession;
use SocialDept\AtpClient\Enums\Scope;
use SocialDept\AtpClient\Enums\ScopeAuthorizationFailure;
use SocialDept\AtpClient\Exceptions\ScopeAuthorizationException;
use SocialDept\AtpClient\Session\Session;
use SocialDept\AtpClient\Session\SessionManager;

class ScopeGate
{
    protected ?Session $session = null;

    public function __construct(
        protected SessionManager $sessions,
        protected ScopeChecker $checker,
    ) {}

    /**
     * Set the session context directly.
     */
    public function forSession(Session $session): self
    {
        $instance = new self($this->sessions, $this->checker);
        $instance->session = $session;

        return $instance;
    }

    /**
     * Set the session context via actor (handle or DID).
     */
    public function forUser(string $actor): self
    {
        $instance = new self($this->sessions, $this->checker);
        $instance->session = $this->sessions->session($actor);

        return $instance;
    }

    /**
     * Check if the session has the given scope.
     */
    public function can(string|Scope $scope): bool
    {
        $session = $this->resolveSession();

        if (! $session) {
            return false;
        }

        return $this->checker->hasScope($session, $scope);
    }

    /**
     * Check if the session has any of the given scopes.
     *
     * @param  array<string|Scope>  $scopes
     */
    public function canAny(array $scopes): bool
    {
        $session = $this->resolveSession();

        if (! $session) {
            return false;
        }

        foreach ($scopes as $scope) {
            if ($this->checker->hasScope($session, $scope)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the session has all of the given scopes.
     *
     * @param  array<string|Scope>  $scopes
     */
    public function canAll(array $scopes): bool
    {
        $session = $this->resolveSession();

        if (! $session) {
            return false;
        }

        return $this->checker->check($session, $scopes);
    }

    /**
     * Check if the session does NOT have the given scope.
     */
    public function cannot(string|Scope $scope): bool
    {
        return ! $this->can($scope);
    }

    /**
     * Authorize the session has all given scopes, or handle failure.
     *
     * @param  string|Scope  ...$scopes
     *
     * @throws ScopeAuthorizationException
     */
    public function authorize(string|Scope ...$scopes): void
    {
        if ($this->canAll($scopes)) {
            return;
        }

        $session = $this->resolveSession();
        $granted = $session ? $session->scopes() : [];
        $required = array_map(
            fn ($scope) => $scope instanceof Scope ? $scope->value : $scope,
            $scopes
        );
        $missing = array_diff($required, $granted);

        $exception = new ScopeAuthorizationException($missing, $granted);

        $action = config('atp-client.scope_authorization.failure_action', ScopeAuthorizationFailure::Abort);

        if ($action === ScopeAuthorizationFailure::Exception) {
            throw $exception;
        }

        // For Abort and Redirect, let the exception render itself
        throw $exception;
    }

    /**
     * Get the granted scopes for the current session.
     */
    public function granted(): array
    {
        $session = $this->resolveSession();

        return $session ? $session->scopes() : [];
    }

    /**
     * Resolve the session from context.
     */
    protected function resolveSession(): ?Session
    {
        // If session was explicitly set, use it
        if ($this->session) {
            return $this->session;
        }

        // Try to resolve from authenticated user
        $user = auth()->user();

        if (! $user instanceof HasAtpSession) {
            return null;
        }

        $did = $user->getAtpDid();

        if (! $did) {
            return null;
        }

        try {
            return $this->sessions->session($did);
        } catch (\Exception) {
            return null;
        }
    }
}
