<?php

namespace App\Support;

class PublicImageUrl
{
    public static function for(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $encodedPath = collect(explode('/', ltrim($path, '/')))
            ->map(fn (string $segment): string => rawurlencode($segment))
            ->implode('/');

        // A relative URL avoids broken images when APP_URL or a reverse proxy
        // reports a different HTTP scheme/host from the visitor's request.
        return rtrim(request()->getBaseUrl(), '/') . '/media/' . $encodedPath;
    }
}
