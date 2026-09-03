<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicImageController extends Controller
{
    private const ALLOWED_DIRECTORIES = [
        'blogs/featured/',
        'branches/logos/',
        'branches/signatures/',
        'members/pictures/',
        'members/signatures/',
    ];

    public function __invoke(string $path): StreamedResponse|Response
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        abort_if(str_contains($path, '..'), 404);
        abort_unless(
            collect(self::ALLOWED_DIRECTORIES)->contains(
                fn (string $directory): bool => str_starts_with($path, $directory)
            ),
            404
        );

        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        $mimeType = $disk->mimeType($path) ?: 'application/octet-stream';
        abort_unless(str_starts_with($mimeType, 'image/'), 404);

        return $disk->response($path, basename($path), [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
