<?php

namespace SocialDept\AtpClient\Tests\Console;

use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;
use SocialDept\AtpClient\AtpClientServiceProvider;
use SocialDept\AtpClient\Facades\Atp;
use SocialDept\AtpClient\Testing\FakeAtpManager;
use SocialDept\AtpClient\Testing\RecordedRequest;
use SocialDept\AtpSchema\Authoring\LexiconFiles;
use SocialDept\AtpSchema\Authoring\NetworkLexicons;
use SocialDept\AtpSchema\Authoring\Templates;
use SocialDept\AtpSchema\SchemaServiceProvider;
use SocialDept\AtpSupport\AtpSupportServiceProvider;
use SocialDept\AtpSupport\Facades\Resolver;

class LexiconCommandsTest extends TestCase
{
    protected const DID = 'did:plc:authority';

    protected string $dir;

    protected FakeAtpManager $atp;

    /**
     * TXT names that resolve.
     *
     * @var array<string, string>
     */
    protected array $dns = ['_lexicon.example.com' => self::DID];

    /**
     * What the authority's repo already publishes, by NSID.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $published = [];

    protected function getPackageProviders($app): array
    {
        return [AtpSupportServiceProvider::class, SchemaServiceProvider::class, AtpClientServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir().'/atp-client-lexicons-'.bin2hex(random_bytes(4));
        mkdir($this->dir);
        config()->set('atp-schema.lexicon_path', $this->dir);

        $this->app->instance(NetworkLexicons::class, new NetworkLexicons(fn (string $name) => $this->dns[$name] ?? null));
        Resolver::shouldReceive('resolvePds')->andReturn('https://pds.example');
        Http::fake(function ($request) {
            $nsid = $request['rkey'] ?? null;

            return isset($this->published[$nsid])
                ? Http::response(['uri' => 'at://'.self::DID.'/com.atproto.lexicon.schema/'.$nsid, 'cid' => 'bafy-old', 'value' => ['$type' => 'com.atproto.lexicon.schema', ...$this->published[$nsid]]])
                : Http::response(['error' => 'RecordNotFound', 'message' => 'Could not locate record'], 400);
        });

        $this->atp = Atp::fake();
        $this->atp->stub('com.atproto.repo.putRecord', ['uri' => 'at://'.self::DID.'/com.atproto.lexicon.schema/x', 'cid' => 'bafy-new']);
        $this->atp->stub('com.atproto.repo.deleteRecord', []);
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->dir));

        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $lexicon
     */
    protected function local(array $lexicon): void
    {
        LexiconFiles::fromConfig()->write($lexicon['id'], $lexicon);
    }

    public function test_a_new_lexicon_is_published_to_the_authority_repo(): void
    {
        $this->local(Templates::make('record', 'com.example.thing'));

        $this->artisan('lexicon:publish', ['--as' => self::DID])
            ->expectsOutputToContain('published')
            ->assertSuccessful();

        $this->atp->assertCalledAuthenticated('com.atproto.repo.putRecord', self::DID);
        $this->atp->assertCalled('com.atproto.repo.putRecord', fn (RecordedRequest $request) => $request->body['repo'] === self::DID
            && $request->body['collection'] === 'com.atproto.lexicon.schema'
            && $request->body['rkey'] === 'com.example.thing'
            && $request->body['record']['$type'] === 'com.atproto.lexicon.schema'
            && $request->body['record']['id'] === 'com.example.thing'
            && $request->body['record']['defs']['main']['type'] === 'record');
    }

    public function test_an_identical_published_lexicon_is_left_alone(): void
    {
        $this->published['com.example.thing'] = Templates::make('record', 'com.example.thing');
        $this->local(Templates::make('record', 'com.example.thing'));

        $this->artisan('lexicon:publish', ['nsid' => ['com.example.thing'], '--as' => self::DID])
            ->expectsOutputToContain('unchanged')
            ->assertSuccessful();

        $this->atp->assertNotCalled('com.atproto.repo.putRecord');
    }

    public function test_an_update_needs_the_update_flag_and_swaps_on_the_published_version(): void
    {
        $this->published['com.example.thing'] = Templates::make('record', 'com.example.thing');
        $edited = Templates::make('record', 'com.example.thing');
        $edited['defs']['main']['record']['properties']['note'] = ['type' => 'string', 'maxLength' => 100];
        $this->local($edited);

        $this->artisan('lexicon:publish', ['--as' => self::DID])->expectsOutputToContain('pass --update')->assertFailed();
        $this->atp->assertNotCalled('com.atproto.repo.putRecord');

        $this->artisan('lexicon:publish', ['--as' => self::DID, '--update' => true])->expectsOutputToContain('updated')->assertSuccessful();
        $this->atp->assertCalled('com.atproto.repo.putRecord', fn (RecordedRequest $request) => $request->body['swapRecord'] === 'bafy-old');
    }

    public function test_a_breaking_update_is_refused_unless_allowed(): void
    {
        $this->published['com.example.thing'] = Templates::make('record', 'com.example.thing');
        $edited = Templates::make('record', 'com.example.thing');
        $edited['defs']['main']['key'] = 'any';
        $this->local($edited);

        $this->artisan('lexicon:publish', ['--as' => self::DID, '--update' => true])
            ->expectsOutputToContain('record-key-type')
            ->assertFailed();
        $this->atp->assertNotCalled('com.atproto.repo.putRecord');

        $this->artisan('lexicon:publish', ['--as' => self::DID, '--update' => true, '--allow-breaking' => true])->assertSuccessful();
        $this->atp->assertCalled('com.atproto.repo.putRecord');
    }

    public function test_dns_must_name_the_publishing_account(): void
    {
        $this->dns = ['_lexicon.example.com' => 'did:plc:someone-else'];
        $this->local(Templates::make('record', 'com.example.thing'));

        $this->artisan('lexicon:publish', ['--as' => self::DID])
            ->expectsOutputToContain('names did:plc:someone-else')
            ->assertFailed();
        $this->atp->assertNotCalled('com.atproto.repo.putRecord');

        $this->artisan('lexicon:publish', ['--as' => self::DID, '--skip-dns-check' => true])->assertSuccessful();
    }

    public function test_a_lexicon_with_lint_errors_is_not_published(): void
    {
        $broken = Templates::make('record', 'com.example.thing');
        $broken['defs']['other'] = $broken['defs']['main'];
        $this->local($broken);

        $this->artisan('lexicon:publish', ['--as' => self::DID])->expectsOutputToContain('non-main-primary')->assertFailed();
        $this->atp->assertNotCalled('com.atproto.repo.putRecord');
    }

    public function test_a_dry_run_writes_nothing(): void
    {
        $this->local(Templates::make('record', 'com.example.thing'));

        $this->artisan('lexicon:publish', ['--as' => self::DID, '--dry-run' => true])->expectsOutputToContain('would publish')->assertSuccessful();
        $this->atp->assertNotCalled('com.atproto.repo.putRecord');
    }

    public function test_publishing_needs_an_account(): void
    {
        $this->local(Templates::make('record', 'com.example.thing'));

        $this->artisan('lexicon:publish')->expectsOutputToContain('Pass --as')->assertFailed();
    }

    public function test_unpublish_deletes_the_record_after_confirming(): void
    {
        $this->published['com.example.thing'] = Templates::make('record', 'com.example.thing');

        $this->artisan('lexicon:unpublish', ['nsid' => ['com.example.thing'], '--as' => self::DID])
            ->expectsConfirmation('Unpublish com.example.thing? Anyone resolving them will no longer find them.', 'no')
            ->assertFailed();
        $this->atp->assertNotCalled('com.atproto.repo.deleteRecord');

        $this->artisan('lexicon:unpublish', ['nsid' => ['com.example.thing', 'com.example.never'], '--as' => self::DID, '--force' => true])
            ->expectsOutputToContain('unpublished')
            ->expectsOutputToContain('not published')
            ->assertSuccessful();
        $this->atp->assertCalledTimes('com.atproto.repo.deleteRecord', 1);
        $this->atp->assertCalled('com.atproto.repo.deleteRecord', fn (RecordedRequest $request) => $request->body['rkey'] === 'com.example.thing');
    }

    public function test_status_compares_local_files_with_the_network(): void
    {
        $this->published['com.example.synced'] = Templates::make('record', 'com.example.synced');
        $this->published['com.example.edited'] = Templates::make('record', 'com.example.edited');
        $edited = Templates::make('record', 'com.example.edited');
        $edited['defs']['main']['description'] = 'Changed here.';

        $this->local(Templates::make('record', 'com.example.synced'));
        $this->local($edited);
        $this->local(Templates::make('record', 'com.example.fresh'));
        $this->local(Templates::make('record', 'org.elsewhere.thing'));

        $this->artisan('lexicon:status')
            ->expectsOutputToContain('in sync')
            ->expectsOutputToContain('local copy differs')
            ->expectsOutputToContain('not published')
            ->expectsOutputToContain('_lexicon.elsewhere.org is missing')
            ->assertFailed();

        $this->artisan('lexicon:status', ['nsid' => ['com.example.synced']])->assertSuccessful();
        $this->artisan('lexicon:status', ['nsid' => ['com.example.synced'], '--did' => 'did:plc:other'])->expectsOutputToContain('not did:plc:other')->assertFailed();
    }
}
