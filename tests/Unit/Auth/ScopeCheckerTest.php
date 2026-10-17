<?php

namespace SocialDept\AtpClient\Tests\Unit\Auth;

use Orchestra\Testbench\TestCase;
use SocialDept\AtpClient\AtpClientServiceProvider;
use SocialDept\AtpClient\Auth\ScopeChecker;
use SocialDept\AtpClient\Enums\Scope;
use SocialDept\AtpClient\Enums\ScopeEnforcementLevel;
use SocialDept\AtpClient\Exceptions\MissingScopeException;
use SocialDept\AtpClient\Session\Session;

class ScopeCheckerTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [AtpClientServiceProvider::class];
    }

    public function test_a_single_collection_repo_scope_covers_its_listed_actions(): void
    {
        $session = $this->sessionWith(['atproto', 'repo:app.example.post?action=create']);

        $this->assertTrue($this->checker()->matchesGranular($session, 'repo:app.example.post?action=create'));
        $this->assertFalse($this->checker()->matchesGranular($session, 'repo:app.example.post?action=delete'));
        $this->assertFalse($this->checker()->matchesGranular($session, 'repo:app.example.publication?action=create'));
    }

    /**
     * A permission set covering several collections expands into the named
     * collection= form when the authorization server issues the token.
     */
    public function test_a_multi_collection_repo_scope_from_an_expanded_permission_set_is_understood(): void
    {
        $session = $this->sessionWith(['atproto', 'repo?collection=app.example.post&collection=app.example.publication&action=create&action=update']);

        $this->assertTrue($this->checker()->matchesGranular($session, 'repo:app.example.post?action=create'));
        $this->assertTrue($this->checker()->matchesGranular($session, 'repo:app.example.publication?action=update'));
        $this->assertFalse($this->checker()->matchesGranular($session, 'repo:app.example.post?action=delete'));
        $this->assertFalse($this->checker()->matchesGranular($session, 'repo:app.example.comment?action=create'));
    }

    public function test_a_repo_scope_without_actions_grants_every_action(): void
    {
        // The formatter leaves action out when all three are granted.
        $session = $this->sessionWith(['atproto', 'repo?collection=app.example.post&collection=app.example.publication']);

        foreach (['create', 'update', 'delete'] as $action) {
            $this->assertTrue($this->checker()->matchesGranular($session, "repo:app.example.publication?action={$action}"));
        }
    }

    public function test_a_wildcard_collection_covers_any_collection(): void
    {
        $session = $this->sessionWith(['atproto', 'repo:*?action=create']);

        $this->assertTrue($this->checker()->matchesGranular($session, 'repo:app.example.post?action=create'));
        $this->assertFalse($this->checker()->matchesGranular($session, 'repo:app.example.post?action=delete'));
    }

    public function test_percent_encoded_values_are_decoded(): void
    {
        $session = $this->sessionWith(['atproto', 'repo?collection=app.example.post&action=create']);

        $this->assertTrue($this->checker()->matchesGranular($session, 'repo:app.example.post?action=create'));
    }

    public function test_strict_enforcement_refuses_a_write_the_expanded_set_does_not_cover(): void
    {
        $session = $this->sessionWith(['atproto', 'repo?collection=app.example.post&collection=app.example.publication&action=create']);
        $checker = new ScopeChecker(ScopeEnforcementLevel::Strict);

        $checker->checkRepoScopeOrFail($session, 'app.example.post', 'create');

        $this->expectException(MissingScopeException::class);

        $checker->checkRepoScopeOrFail($session, 'app.example.post', 'delete');
    }

    public function test_include_builds_a_permission_set_scope(): void
    {
        $this->assertSame('include:com.example.authFull', Scope::include('com.example.authFull'));
        $this->assertSame(
            'include:com.example.authFull?aud=did:web:api.example.com%23svc_appview',
            Scope::include('com.example.authFull', 'did:web:api.example.com#svc_appview'),
        );
    }

    protected function checker(): ScopeChecker
    {
        return new ScopeChecker(ScopeEnforcementLevel::Permissive);
    }

    /**
     * @param  array<int, string>  $scopes
     */
    protected function sessionWith(array $scopes): Session
    {
        $session = $this->createStub(Session::class);
        $session->method('did')->willReturn('did:plc:alice');
        $session->method('scopes')->willReturn($scopes);
        $session->method('hasScope')->willReturnCallback(fn (string $scope) => in_array($scope, $scopes, true));

        return $session;
    }
}
