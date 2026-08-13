<?php

namespace App\Support;

use Throwable;

class VersionedAsset
{
    public static function url(string $path): string
    {
        $path = ltrim($path, '/');
        $url = asset($path);

        if (file_exists(public_path('mix-manifest.json'))) {
            try {
                $url = mix($path);
            } catch (Throwable $exception) {
                // Fall back to the regular public asset URL when the manifest is stale.
            }
        }

        $absolutePath = public_path($path);

        if (!is_file($absolutePath)) {
            return $url;
        }

        $separator = strpos($url, '?') === false ? '?' : '&';

        return $url . $separator . 'v=' . filemtime($absolutePath);
    }
}
