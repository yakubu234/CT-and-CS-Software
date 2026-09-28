<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\DataBackup\BackupSettingsService;
use App\Services\DataBackup\GoogleDriveOAuthService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GoogleDriveOAuthSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 191)->unique();
            $table->longText('value');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('settings');

        parent::tearDown();
    }

    public function test_multiple_google_connections_are_encrypted_and_only_the_last_disconnect_disables_backups(): void
    {
        $settings = app(BackupSettingsService::class);
        $settings->update([
            'enabled' => true,
            'formats' => ['csv'],
            'modules' => ['members'],
            'recipient_emails' => [],
        ]);
        $settings->storeGoogleConnection([
            'refresh_token' => 'secret-refresh-token',
            'account_email' => 'backups@example.com',
            'account_name' => 'Backup Account',
            'folder_id' => 'folder-123',
            'folder_name' => 'System Backups',
        ]);
        $settings->storeGoogleConnection([
            'refresh_token' => 'second-secret-token',
            'account_email' => 'archive@example.com',
            'account_name' => 'Archive Account',
            'folder_id' => 'folder-456',
            'folder_name' => 'System Backups',
        ]);

        $storedValue = Setting::query()->where('name', 'data_backup.configuration')->value('value');
        $connections = $settings->googleConnections();

        $this->assertTrue($settings->isGoogleConnected());
        $this->assertCount(2, $connections);
        $this->assertStringNotContainsString('secret-refresh-token', $storedValue);
        $this->assertStringNotContainsString('second-secret-token', $storedValue);

        $firstId = array_key_first($connections);
        $oneRemaining = $settings->removeGoogleConnection($firstId);

        $this->assertTrue($oneRemaining['enabled']);
        $this->assertCount(1, $oneRemaining['google_connections']);
        $this->assertTrue($settings->isGoogleConnected());

        $lastId = array_key_first($oneRemaining['google_connections']);
        $disconnected = $settings->removeGoogleConnection($lastId);

        $this->assertFalse($disconnected['enabled']);
        $this->assertSame([], $disconnected['google_connections']);
        $this->assertFalse($settings->isGoogleConnected());
    }

    public function test_authorization_url_requests_offline_drive_file_access_and_state(): void
    {
        config()->set('data_backup.google', [
            'client_id' => 'client-id.apps.googleusercontent.com',
            'client_secret' => 'client-secret',
            'redirect_uri' => 'https://example.com/data-backups/google/callback',
            'folder_name' => 'System Backups',
        ]);

        $url = app(GoogleDriveOAuthService::class)->authorizationUrl('known-state');
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->assertSame('offline', $query['access_type']);
        $this->assertSame('consent select_account', $query['prompt']);
        $this->assertSame('known-state', $query['state']);
        $this->assertStringContainsString('https://www.googleapis.com/auth/drive.file', $query['scope']);
    }

    public function test_legacy_single_account_settings_are_exposed_as_a_destination(): void
    {
        Setting::query()->create([
            'name' => 'data_backup.configuration',
            'value' => Crypt::encryptString(json_encode([
                'enabled' => true,
                'google_refresh_token' => 'legacy-token',
                'google_account_email' => 'legacy@example.com',
                'google_folder_id' => 'legacy-folder',
                'google_folder_name' => 'System Backups',
            ], JSON_THROW_ON_ERROR)),
        ]);

        $configuration = app(BackupSettingsService::class)->get();

        $this->assertCount(1, $configuration['google_connections']);
        $this->assertSame('legacy@example.com', array_values($configuration['google_connections'])[0]['account_email']);
        $this->assertArrayNotHasKey('google_refresh_token', $configuration);
    }
}
