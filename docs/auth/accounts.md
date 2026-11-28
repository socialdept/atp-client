# Account management

Part of the [atp-client docs](../introduction.md#documentation). This page covers the `com.atproto.server` calls for an account's email, password, app passwords and deletion.

They live on `$client->atproto->server`. On the reference PDS, several of them need a session from the account's main password: an app password session or an OAuth session is refused. Those are marked below.

## Email

```php
$server = Atp::as($did)->atproto->server;

// Confirm the address: the PDS emails a code.
$server->requestEmailConfirmation();
$server->confirmEmail('alice@example.com', $code);

// Change it. Once the current address is confirmed, a code is emailed there first.
$update = $server->requestEmailUpdate();           // main password
$server->updateEmail('new@example.com', $update->tokenRequired ? $code : null);

// Turn the email second factor on or off.
$server->updateEmail('alice@example.com', $code, emailAuthFactor: true);
```

Under OAuth, confirming the address needs `account:email?action=manage`.

## App passwords

```php
$created = $server->createAppPassword('Laptop');   // main password
$created->password;                                 // returned only now

$server->listAppPasswords()->passwords;             // names and dates, no passwords
$server->revokeAppPassword('Laptop');               // also signs out its sessions
```

Pass `privileged: true` for an app password that may also reach direct messages.

## Password reset

No session is needed, so call these on a public client for the account's PDS:

```php
$pds = Atp::public('https://pds.example.com')->atproto->server;

$pds->requestPasswordReset('alice@example.com');
$pds->resetPassword($code, $newPassword);           // signs out every session
```

## Deleting the account

```php
$server->requestAccountDelete();                    // main password, emails a code

Atp::public($pdsUrl)->atproto->server->deleteAccount($did, $password, $code);
```

Deletion cannot be undone.
