<?php

namespace App\Support;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;

class AppSettingsRepository
{
    public const KEY_WRAPPED_ATHLETE_THEME = 'wrapped_athlete_theme';

    public const KEY_ROSTER_AWARDS_THEME = 'roster_awards_theme';

    public const KEY_ROSTER_AWARDS_COPY = 'roster_awards_copy';

    public const KEY_WRAPPED_COPY = 'wrapped_copy';

    private const CACHE_TTL_SECONDS = 60;

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            self::KEY_WRAPPED_ATHLETE_THEME => [
                'default_accent' => '#7c3aed',
                'squat' => '#3b82f6',
                'bench' => '#f59e0b',
                'deadlift' => '#f43f5e',
            ],
            self::KEY_ROSTER_AWARDS_THEME => [
                'default_accent' => '#7c3aed',
                'steps' => '#10b981',
                'kcal' => '#f59e0b',
                'sommeil' => '#0ea5e9',
            ],
            self::KEY_ROSTER_AWARDS_COPY => [
                'steps' => [
                    'eyebrow' => 'Hall of Fame · Pas',
                    'title' => 'Le podomètre a fondu',
                    'punchline' => '{name} a fait marcher le groupe… littéralement.',
                ],
                'kcal' => [
                    'eyebrow' => 'Hall of Fame · Assiette',
                    'title' => 'Le roi / la reine de la fourchette',
                    'punchline' => '{name} a mis le plus de carburant dans le réservoir.',
                ],
                'sommeil' => [
                    'eyebrow' => 'Hall of Fame · Oreiller',
                    'title' => 'Le plus gros dormeur',
                    'punchline' => '{name} collectionne les heures de lit comme des médailles.',
                ],
                'intro_hint' => null,
                'outro_title' => null,
                'outro_subtitle' => null,
            ],
            self::KEY_WRAPPED_COPY => [
                'brand_label' => null,
                'keep_going' => null,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function get(string $key): array
    {
        $defaults = self::defaults()[$key] ?? [];

        $stored = Cache::remember(
            self::cacheKey($key),
            self::CACHE_TTL_SECONDS,
            function () use ($key) {
                $setting = AppSetting::query()->where('key', $key)->first();

                return is_array($setting?->value) ? $setting->value : [];
            },
        );

        return array_replace_recursive($defaults, $stored);
    }

    /**
     * @param  array<string, mixed>  $value
     */
    public static function set(string $key, array $value): void
    {
        AppSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );

        Cache::forget(self::cacheKey($key));
    }

    /**
     * @return array{
     *     wrapped_athlete_theme: array<string, mixed>,
     *     roster_awards_theme: array<string, mixed>,
     *     roster_awards_copy: array<string, mixed>,
     *     wrapped_copy: array<string, mixed>
     * }
     */
    public static function allDesignSettings(): array
    {
        return [
            self::KEY_WRAPPED_ATHLETE_THEME => self::get(self::KEY_WRAPPED_ATHLETE_THEME),
            self::KEY_ROSTER_AWARDS_THEME => self::get(self::KEY_ROSTER_AWARDS_THEME),
            self::KEY_ROSTER_AWARDS_COPY => self::get(self::KEY_ROSTER_AWARDS_COPY),
            self::KEY_WRAPPED_COPY => self::get(self::KEY_WRAPPED_COPY),
        ];
    }

    /**
     * Shared Inertia payload for story modals.
     *
     * @return array{wrapped: array<string, mixed>, awards: array<string, mixed>, wrappedCopy: array<string, mixed>, awardsCopy: array<string, mixed>}
     */
    public static function storyThemesPayload(): array
    {
        return [
            'wrapped' => self::get(self::KEY_WRAPPED_ATHLETE_THEME),
            'awards' => self::get(self::KEY_ROSTER_AWARDS_THEME),
            'wrappedCopy' => self::get(self::KEY_WRAPPED_COPY),
            'awardsCopy' => self::get(self::KEY_ROSTER_AWARDS_COPY),
        ];
    }

    private static function cacheKey(string $key): string
    {
        return 'app_settings.'.$key;
    }
}
