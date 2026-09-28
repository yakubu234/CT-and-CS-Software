<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\DataBackup\BackupSettingsService;
use App\Services\DataBackup\GoogleDriveOAuthService;
use Illuminate\Database\Schema\Blueprint;
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

    public function test_google_connection_is_encrypted_and_disconnect_disables_automatic_backups(): void
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

        $storedValue = Setting::query()->where('name', 'data_backup.configuration')->value('value');

        $this->assertTrue($settings->isGoogleConnected());
        $this->assertStringNotContainsString('secret-refresh-token', $storedValue);

        $disconnected = $settings->clearGoogleConnection();

        $this->assertFalse($disconnected['enabled']);
        $this->assertSame('', $disconnected['google_refresh_token']);
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
        $this->assertSame('consent', $query['prompt']);
        $this->assertSame('known-state', $query['state']);
        $this->assertStringContainsString('https://www.googleapis.com/auth/drive.file', $query['scope']);
    }
}
