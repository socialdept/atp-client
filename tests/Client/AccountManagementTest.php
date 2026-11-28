<?php

namespace SocialDept\AtpClient\Tests\Client;

use Orchestra\Testbench\TestCase;
use SocialDept\AtpClient\AtpClientServiceProvider;
use SocialDept\AtpClient\Data\Responses\Atproto\Server\AppPassword;
use SocialDept\AtpClient\Enums\Nsid\AtprotoServer;
use SocialDept\AtpClient\Facades\Atp;

class AccountManagementTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [AtpClientServiceProvider::class];
    }

    public function test_app_passwords_are_created_listed_and_revoked(): void
    {
        $fake = Atp::fake();
        $fake->stub(AtprotoServer::CreateAppPassword, ['name' => 'Laptop', 'password' => 'abcd-efgh-ijkl-mnop', 'createdAt' => '2026-11-28T00:00:00.000Z', 'privileged' => true]);
        $fake->stub(AtprotoServer::ListAppPasswords, ['passwords' => [['name' => 'Laptop', 'createdAt' => '2026-11-28T00:00:00.000Z']]]);
        $fake->stub(AtprotoServer::RevokeAppPassword, []);

        $server = Atp::as('did:plc:alice')->atproto->server;

        $created = $server->createAppPassword('Laptop', privileged: true);
        $listed = $server->listAppPasswords();
        $server->revokeAppPassword('Laptop');

        $this->assertSame('abcd-efgh-ijkl-mnop', $created->password);
        $this->assertTrue($created->privileged);
        $this->assertEquals([new AppPassword('Laptop', '2026-11-28T00:00:00.000Z')], $listed->passwords);
        $fake->assertCalledWith(AtprotoServer::CreateAppPassword, fn ($request) => $request->hasBody('name', 'Laptop') && $request->hasBody('privileged', true));
        $fake->assertCalledWith(AtprotoServer::RevokeAppPassword, fn ($request) => $request->hasBody('name', 'Laptop'));
    }

    public function test_the_email_is_confirmed_and_changed(): void
    {
        $fake = Atp::fake();
        $fake->stub(AtprotoServer::RequestEmailConfirmation, []);
        $fake->stub(AtprotoServer::ConfirmEmail, []);
        $fake->stub(AtprotoServer::RequestEmailUpdate, ['tokenRequired' => true]);
        $fake->stub(AtprotoServer::UpdateEmail, []);

        $server = Atp::as('did:plc:alice')->atproto->server;

        $server->requestEmailConfirmation();
        $server->confirmEmail('alice@mail.example', 'ABCDE-12345');
        $update = $server->requestEmailUpdate();
        $server->updateEmail('new@mail.example', 'FGHIJ-67890');
        $server->updateEmail('new@mail.example', emailAuthFactor: true);

        $this->assertTrue($update->tokenRequired);
        $fake->assertCalledAuthenticated(AtprotoServer::RequestEmailConfirmation, 'did:plc:alice');
        $fake->assertCalledWith(AtprotoServer::ConfirmEmail, fn ($request) => $request->hasBody('email', 'alice@mail.example') && $request->hasBody('token', 'ABCDE-12345'));
        $fake->assertCalledWith(AtprotoServer::UpdateEmail, fn ($request) => $request->hasBody('token', 'FGHIJ-67890'));
        $fake->assertCalledWith(AtprotoServer::UpdateEmail, fn ($request) => $request->hasBody('emailAuthFactor', true) && ! $request->hasBody('token'));
    }

    public function test_the_account_is_deleted_and_the_password_reset(): void
    {
        $fake = Atp::fake();
        $fake->stub(AtprotoServer::RequestAccountDelete, []);
        $fake->stub(AtprotoServer::DeleteAccount, []);
        $fake->stub(AtprotoServer::RequestPasswordReset, []);
        $fake->stub(AtprotoServer::ResetPassword, []);

        $server = Atp::as('did:plc:alice')->atproto->server;

        $server->requestAccountDelete();
        $server->deleteAccount('did:plc:alice', 'secret', 'ABCDE-12345');
        $server->requestPasswordReset('alice@mail.example');
        $server->resetPassword('ABCDE-12345', 'new-secret');

        $fake->assertCalledWith(AtprotoServer::DeleteAccount, fn ($request) => $request->hasBody('did', 'did:plc:alice') && $request->hasBody('password', 'secret') && $request->hasBody('token', 'ABCDE-12345'));
        $fake->assertCalledWith(AtprotoServer::RequestPasswordReset, fn ($request) => $request->hasBody('email', 'alice@mail.example'));
        $fake->assertCalledWith(AtprotoServer::ResetPassword, fn ($request) => $request->hasBody('token', 'ABCDE-12345') && $request->hasBody('password', 'new-secret'));
    }
}
