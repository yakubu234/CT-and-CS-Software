<?php

namespace App\Services\DataBackup;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use RuntimeException;

class GoogleDriveOAuthService
{
    public function __construct(protected BackupSettingsService $settings)
    {
    }

    public function authorizationUrl(string $state): string
    {
        $client = $this->baseClient();
        $client->setState($state);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setIncludeGrantedScopes(true);

        return $client->createAuthUrl();
    }

    public function connect(string $authorizationCode): array
    {
        $client = $this->baseClient();
        $token = $client->fetchAccessTokenWithAuthCode($authorizationCode);

        if (isset($token['error'])) {
            throw new RuntimeException($token['error_description'] ?? 'Google rejected the authorization request.');
        }

        $refreshToken = (string) ($token['refresh_token'] ?? '');

        if ($refreshToken === '') {
            throw new RuntimeException('Google did not return an offline refresh token. Please connect again and approve access.');
        }

        $client->setAccessToken($token);
        $drive = new Drive($client);
        $about = $drive->about->get(['fields' => 'user(displayName,emailAddress)']);
        $folderName = (string) config('data_backup.google.folder_name', 'System Backups');
        $folder = $this->findOrCreateFolder($drive, $folderName);

        return $this->settings->storeGoogleConnection([
            'refresh_token' => $refreshToken,
            'account_email' => $about->getUser()?->getEmailAddress(),
            'account_name' => $about->getUser()?->getDisplayName(),
            'folder_id' => $folder->getId(),
            'folder_name' => $folder->getName(),
        ]);
    }

    public function authorizedClient(): Client
    {
        $configuration = $this->settings->get();
        $refreshToken = (string) $configuration['google_refresh_token'];

        if ($refreshToken === '') {
            throw new RuntimeException('Google Drive is not connected. Connect a Google account first.');
        }

        $client = $this->baseClient();
        $token = $client->fetchAccessTokenWithRefreshToken($refreshToken);

        if (isset($token['error'])) {
            throw new RuntimeException('Google Drive authorization has expired or been revoked. Reconnect the Google account.');
        }

        return $client;
    }

    public function disconnect(): void
    {
        $configuration = $this->settings->get();
        $refreshToken = (string) $configuration['google_refresh_token'];

        if ($refreshToken !== '') {
            try {
                $this->baseClient()->revokeToken($refreshToken);
            } catch (\Throwable) {
                // Always remove the local credential, even if Google is unavailable.
            }
        }

        $this->settings->clearGoogleConnection();
    }

    private function baseClient(): Client
    {
        $clientId = trim((string) config('data_backup.google.client_id'));
        $clientSecret = trim((string) config('data_backup.google.client_secret'));

        if ($clientId === '' || $clientSecret === '') {
            throw new RuntimeException('Google Drive OAuth is not configured on the server. Add the client ID and client secret first.');
        }

        $client = new Client();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri(
            trim((string) config('data_backup.google.redirect_uri')) ?: route('data-backups.google.callback')
        );
        $client->setScopes([Drive::DRIVE_FILE]);

        return $client;
    }

    private function findOrCreateFolder(Drive $drive, string $folderName): DriveFile
    {
        $escapedName = str_replace(["\\", "'"], ["\\\\", "\\'"], $folderName);
        $folders = $drive->files->listFiles([
            'q' => "name = '{$escapedName}' and mimeType = 'application/vnd.google-apps.folder' and trashed = false",
            'spaces' => 'drive',
            'fields' => 'files(id,name)',
            'pageSize' => 1,
        ]);

        if ($folders->getFiles() !== []) {
            return $folders->getFiles()[0];
        }

        return $drive->files->create(
            new DriveFile([
                'name' => $folderName,
                'mimeType' => 'application/vnd.google-apps.folder',
            ]),
            ['fields' => 'id,name']
        );
    }
}
