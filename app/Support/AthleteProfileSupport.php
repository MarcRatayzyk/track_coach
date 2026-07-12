<?php

namespace App\Support;

class AthleteProfileSupport
{
    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function attributesFromValidated(array $validated): array
    {
        $fields = [
            'birth_date',
            'height_cm',
            'sex',
            'weight_category',
            'level',
            'injuries_notes',
            'bio',
            'profession',
            'years_training',
        ];

        $attributes = [];

        foreach ($fields as $field) {
            if (! array_key_exists($field, $validated)) {
                continue;
            }

            $value = $validated[$field];
            $attributes[$field] = in_array($field, ['sex', 'weight_category', 'level', 'injuries_notes', 'bio', 'profession'], true)
                ? ($value ?? null ?: null)
                : $value;
        }

        return $attributes;
    }
}
