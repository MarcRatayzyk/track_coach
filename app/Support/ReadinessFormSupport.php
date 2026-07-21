<?php

namespace App\Support;

use App\Models\AthleteReadinessForm;
use App\Models\CoachReadinessForm;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ReadinessFormSupport
{
    public const TYPE_NUMBER = 'number';

    public const TYPE_TEXT = 'text';

    public const TYPE_SELECT = 'select';

    /**
     * @return list<string>
     */
    public static function allowedTypes(): array
    {
        return [
            self::TYPE_NUMBER,
            self::TYPE_TEXT,
            self::TYPE_SELECT,
        ];
    }

    /**
     * @return list<array{key: string, label: string, type: string, options?: list<array{value: string, label: string, color: string}>}>
     */
    public static function presetCatalog(): array
    {
        return [
            [
                'key' => 'steps',
                'label' => 'STEPS',
                'type' => self::TYPE_NUMBER,
            ],
            [
                'key' => 'kcal',
                'label' => 'KCAL',
                'type' => self::TYPE_TEXT,
            ],
            [
                'key' => 'sommeil',
                'label' => 'SOMMEIL',
                'type' => self::TYPE_SELECT,
                'options' => [
                    ['value' => 'lt_5h', 'label' => '- 5H', 'color' => '#991b1b'],
                    ['value' => '5_6h', 'label' => '5-6H', 'color' => '#ea580c'],
                    ['value' => '6_7h', 'label' => '6-7H', 'color' => '#ca8a04'],
                    ['value' => '7_8h', 'label' => '7-8H', 'color' => '#4ade80'],
                    ['value' => '8_9h', 'label' => '8-9H', 'color' => '#7dd3fc'],
                ],
            ],
            [
                'key' => 'alimentation',
                'label' => 'ALIMENTATION',
                'type' => self::TYPE_SELECT,
                'options' => [
                    ['value' => 'mauvaise', 'label' => 'MAUVAISE', 'color' => '#991b1b'],
                    ['value' => 'moyenne', 'label' => 'MOYENNE', 'color' => '#ca8a04'],
                    ['value' => 'bonne', 'label' => 'BONNE', 'color' => '#4ade80'],
                ],
            ],
            [
                'key' => 'hydratation',
                'label' => 'HYDRATATION',
                'type' => self::TYPE_SELECT,
                'options' => [
                    ['value' => 'faible', 'label' => 'FAIBLE <1.5L', 'color' => '#991b1b'],
                    ['value' => 'moyenne', 'label' => 'MOYENNE ~1.5-2L', 'color' => '#ca8a04'],
                    ['value' => 'bonne', 'label' => 'BON ~2L', 'color' => '#4ade80'],
                    ['value' => 'excellente', 'label' => 'EXCELLENTE +2.5L', 'color' => '#7dd3fc'],
                ],
            ],
            [
                'key' => 'stress_global',
                'label' => 'STRESS GLOBAL',
                'type' => self::TYPE_SELECT,
                'options' => [
                    ['value' => 'eleve', 'label' => 'ÉLEVÉ', 'color' => '#991b1b'],
                    ['value' => 'moyen', 'label' => 'MOYEN', 'color' => '#4ade80'],
                    ['value' => 'bas', 'label' => 'BAS', 'color' => '#7dd3fc'],
                ],
            ],
            [
                'key' => 'motivation',
                'label' => 'MOTIVATION',
                'type' => self::TYPE_SELECT,
                'options' => [
                    ['value' => 'faible', 'label' => 'FAIBLE', 'color' => '#991b1b'],
                    ['value' => 'moyenne', 'label' => 'MOYENNE', 'color' => '#ca8a04'],
                    ['value' => 'bonne', 'label' => 'BONNE', 'color' => '#4ade80'],
                    ['value' => 'excellente', 'label' => 'EXCELLENTE', 'color' => '#7dd3fc'],
                ],
            ],
            [
                'key' => 'forme_physique',
                'label' => 'FORME PHYSIQUE',
                'type' => self::TYPE_SELECT,
                'options' => [
                    ['value' => '1', 'label' => '1', 'color' => '#991b1b'],
                    ['value' => '2', 'label' => '2', 'color' => '#ea580c'],
                    ['value' => '3', 'label' => '3', 'color' => '#ca8a04'],
                    ['value' => '4', 'label' => '4', 'color' => '#4ade80'],
                    ['value' => '5', 'label' => '5', 'color' => '#7dd3fc'],
                ],
            ],
            [
                'key' => 'forme_mentale',
                'label' => 'FORME MENTALE',
                'type' => self::TYPE_SELECT,
                'options' => [
                    ['value' => '1', 'label' => '1', 'color' => '#991b1b'],
                    ['value' => '2', 'label' => '2', 'color' => '#ea580c'],
                    ['value' => '3', 'label' => '3', 'color' => '#ca8a04'],
                    ['value' => '4', 'label' => '4', 'color' => '#4ade80'],
                    ['value' => '5', 'label' => '5', 'color' => '#7dd3fc'],
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function defaultFields(): array
    {
        $fields = [];

        foreach (self::presetCatalog() as $index => $preset) {
            $fields[] = self::fieldFromPreset($preset, $index);
        }

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $preset
     * @return array<string, mixed>
     */
    public static function fieldFromPreset(array $preset, int $sortOrder = 0): array
    {
        $type = (string) ($preset['type'] ?? self::TYPE_TEXT);
        $field = [
            'id' => 'preset-'.(string) ($preset['key'] ?? Str::uuid()->toString()),
            'preset_key' => (string) ($preset['key'] ?? ''),
            'label' => (string) ($preset['label'] ?? 'Champ'),
            'type' => $type,
            'required' => true,
            'sort_order' => $sortOrder,
            'options' => [],
        ];

        if ($type === self::TYPE_SELECT) {
            $field['options'] = self::normalizeOptions($preset['options'] ?? []);
        }

        return $field;
    }

    public static function ensureCoachHasDefaultForm(User $coach): CoachReadinessForm
    {
        $existing = CoachReadinessForm::query()
            ->where('coach_id', $coach->id)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return CoachReadinessForm::query()->create([
            'coach_id' => $coach->id,
            'fields' => self::defaultFields(),
        ]);
    }

    /**
     * @param  list<array<string, mixed>>|null  $fields
     */
    public static function copyToAthlete(User $athlete, ?User $coach = null, ?array $fields = null): AthleteReadinessForm
    {
        $existing = AthleteReadinessForm::query()
            ->where('athlete_id', $athlete->id)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        if ($fields === null) {
            $coach ??= $athlete->primaryCoach();
            $sourceFields = $coach !== null
                ? self::ensureCoachHasDefaultForm($coach)->fields
                : self::defaultFields();
            $fields = is_array($sourceFields) ? $sourceFields : self::defaultFields();
        }

        return AthleteReadinessForm::query()->create([
            'athlete_id' => $athlete->id,
            'fields' => self::normalizeFields($fields),
        ]);
    }

    public static function ensureAthleteHasForm(User $athlete): AthleteReadinessForm
    {
        $existing = AthleteReadinessForm::query()
            ->where('athlete_id', $athlete->id)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return self::copyToAthlete($athlete);
    }

    /**
     * @param  mixed  $fields
     * @return list<array<string, mixed>>
     */
    public static function normalizeFields(mixed $fields): array
    {
        if (! is_array($fields)) {
            return self::defaultFields();
        }

        $normalized = [];

        foreach (array_values($fields) as $index => $field) {
            if (! is_array($field)) {
                continue;
            }

            $type = (string) ($field['type'] ?? '');
            if (! in_array($type, self::allowedTypes(), true)) {
                continue;
            }

            $label = trim((string) ($field['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $id = trim((string) ($field['id'] ?? ''));
            if ($id === '') {
                $id = (string) Str::uuid();
            }

            $presetKey = $field['preset_key'] ?? null;
            $presetKey = is_string($presetKey) && $presetKey !== '' ? $presetKey : null;

            $item = [
                'id' => $id,
                'preset_key' => $presetKey,
                'label' => mb_substr($label, 0, 80),
                'type' => $type,
                'required' => (bool) ($field['required'] ?? true),
                'sort_order' => (int) ($field['sort_order'] ?? $index),
                'options' => [],
            ];

            if ($type === self::TYPE_SELECT) {
                $item['options'] = self::normalizeOptions($field['options'] ?? []);
                if ($item['options'] === []) {
                    continue;
                }
            }

            $normalized[] = $item;
        }

        usort($normalized, fn (array $a, array $b): int => $a['sort_order'] <=> $b['sort_order']);

        foreach ($normalized as $index => &$item) {
            $item['sort_order'] = $index;
        }
        unset($item);

        return $normalized !== [] ? $normalized : self::defaultFields();
    }

    /**
     * @param  mixed  $options
     * @return list<array{value: string, label: string, color: string}>
     */
    public static function normalizeOptions(mixed $options): array
    {
        if (! is_array($options)) {
            return [];
        }

        $normalized = [];

        foreach ($options as $option) {
            if (! is_array($option)) {
                continue;
            }

            $label = trim((string) ($option['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $value = trim((string) ($option['value'] ?? ''));
            if ($value === '') {
                $value = Str::slug($label) ?: (string) Str::uuid();
            }

            $color = trim((string) ($option['color'] ?? '#64748b'));
            if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                $color = '#64748b';
            }

            $normalized[] = [
                'value' => mb_substr($value, 0, 64),
                'label' => mb_substr($label, 0, 80),
                'color' => $color,
            ];
        }

        return $normalized;
    }

    /**
     * @return array{fields: list<array<string, mixed>>}
     */
    public static function formPayload(AthleteReadinessForm|CoachReadinessForm $form): array
    {
        return [
            'fields' => self::normalizeFields($form->fields ?? []),
        ];
    }

    /**
     * Validation rules for a fields payload (coach builder).
     *
     * @return array<string, mixed>
     */
    public static function fieldsValidationRules(): array
    {
        return [
            'fields' => ['required', 'array', 'min:1', 'max:30'],
            'fields.*.id' => ['nullable', 'string', 'max:64'],
            'fields.*.preset_key' => ['nullable', 'string', 'max:64'],
            'fields.*.label' => ['required', 'string', 'max:80'],
            'fields.*.type' => ['required', 'string', 'in:'.implode(',', self::allowedTypes())],
            'fields.*.required' => ['sometimes', 'boolean'],
            'fields.*.sort_order' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'fields.*.options' => ['nullable', 'array', 'max:20'],
            'fields.*.options.*.value' => ['nullable', 'string', 'max:64'],
            'fields.*.options.*.label' => ['required_with:fields.*.options', 'string', 'max:80'],
            'fields.*.options.*.color' => ['nullable', 'string', 'max:7'],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public static function normalizeEntryValues(array $fields, array $values): array
    {
        $normalized = [];

        foreach ($fields as $field) {
            $id = (string) ($field['id'] ?? '');
            if ($id === '') {
                continue;
            }

            $raw = $values[$id] ?? null;
            $type = (string) ($field['type'] ?? self::TYPE_TEXT);

            if ($raw === null || $raw === '') {
                $normalized[$id] = null;

                continue;
            }

            $normalized[$id] = match ($type) {
                self::TYPE_NUMBER => is_numeric($raw) ? 0 + $raw : null,
                self::TYPE_SELECT => (string) $raw,
                default => mb_substr(trim((string) $raw), 0, 500),
            };
        }

        return $normalized;
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    public static function entryValueRules(array $fields): array
    {
        $rules = [
            'entry_date' => ['nullable', 'date', 'before_or_equal:today'],
            'values' => ['required', 'array'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];

        foreach ($fields as $field) {
            $id = (string) ($field['id'] ?? '');
            if ($id === '') {
                continue;
            }

            $required = (bool) ($field['required'] ?? true);
            $type = (string) ($field['type'] ?? self::TYPE_TEXT);
            $key = 'values.'.$id;

            $fieldRules = [$required ? 'required' : 'nullable'];

            if ($type === self::TYPE_NUMBER) {
                $fieldRules[] = 'numeric';
            } elseif ($type === self::TYPE_SELECT) {
                $allowed = collect($field['options'] ?? [])
                    ->pluck('value')
                    ->filter(fn ($value) => $value !== null && $value !== '')
                    ->values()
                    ->all();
                $fieldRules[] = 'string';
                if ($allowed !== []) {
                    $fieldRules[] = Rule::in($allowed);
                }
            } else {
                $fieldRules[] = 'string';
                $fieldRules[] = 'max:500';
            }

            $rules[$key] = $fieldRules;
        }

        return $rules;
    }
}
