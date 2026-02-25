<?php

namespace App\Services\Connectors;

use InvalidArgumentException;

class ConnectorFactory
{
    private static array $connectors = [
        'instagram' => InstagramConnector::class,
        'tiktok' => TikTokConnector::class,
        'youtube' => YouTubeConnector::class,
        'x' => XConnector::class,
        'linkedin' => LinkedInConnector::class,
    ];

    public static function make(string $platform): PlatformConnector
    {
        $class = self::$connectors[$platform] ?? null;

        if (!$class) {
            throw new InvalidArgumentException("Unknown platform: {$platform}");
        }

        return new $class();
    }

    public static function platforms(): array
    {
        return array_keys(self::$connectors);
    }
}
