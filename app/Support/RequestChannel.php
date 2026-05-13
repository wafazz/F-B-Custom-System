<?php

namespace App\Support;

use Illuminate\Http\Request;

class RequestChannel
{
    public const WEB = 'web';

    public const PWA = 'pwa';

    public const MOBILE = 'mobile';

    public static function detect(Request $request): string
    {
        $header = strtolower((string) $request->header('X-Channel', ''));
        if ($header === self::MOBILE) {
            return self::MOBILE;
        }
        if ($header === self::PWA) {
            return self::PWA;
        }

        return self::WEB;
    }

    public static function availableColumn(string $channel): string
    {
        return match ($channel) {
            self::PWA => 'available_pwa',
            self::MOBILE => 'available_mobile',
            default => 'available_web',
        };
    }
}
