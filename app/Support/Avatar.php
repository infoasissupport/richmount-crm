<?php

namespace App\Support;

/**
 * Initials + colour for the round avatars in list tables (same rules as the prototype's avatar() helper).
 */
class Avatar
{
    public const TONES = ['pink', 'blue', 'amber', 'purple', 'green', 'gray'];

    /** First letters of the first two words that start with a letter, e.g. "Skyline Builders" → "SB". */
    public static function initials(?string $name): string
    {
        $letters = collect(preg_split('/\s+/', trim((string) $name)))
            ->filter(fn ($word) => preg_match('/^[A-Za-z]/', $word))
            ->take(2)
            ->map(fn ($word) => $word[0])
            ->implode('');

        return strtoupper($letters) ?: '—';
    }

    /** Stable colour per name: the .av-* class suffix. */
    public static function tone(?string $name): string
    {
        return self::TONES[array_sum(array_map('ord', str_split((string) $name))) % count(self::TONES)];
    }
}
