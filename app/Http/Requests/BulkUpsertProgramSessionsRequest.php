<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesProgramSessionDay;
use Illuminate\Foundation\Http\FormRequest;

class BulkUpsertProgramSessionsRequest extends FormRequest
{
    use ValidatesProgramSessionDay;

    public function authorize(): bool
    {
        return $this->user()?->role === 'coach';
    }

    protected function prepareForValidation(): void
    {
        $this->prepareProgramSessionDecimalInputs();
    }

    public function rules(): array
    {
        $rules = [
            'operations' => ['required', 'array', 'min:1', 'max:84'],
            'operations.*.week_number' => ['required', 'integer', 'min:1'],
            'operations.*.weekday' => ['required', 'integer', 'min:1', 'max:7'],
        ];

        foreach ($this->programSessionDayRules() as $key => $rule) {
            $rules["operations.*.{$key}"] = $rule;
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $operations = $this->input('operations', []);
            if (! is_array($operations)) {
                return;
            }

            foreach ($operations as $opIndex => $operation) {
                if (! is_array($operation)) {
                    continue;
                }

                foreach ($operation['items'] ?? [] as $itemIndex => $item) {
                    if (! is_array($item)) {
                        continue;
                    }
                    $this->assertSetSchemeValid($validator, "operations.{$opIndex}.items.{$itemIndex}", $item);
                }

                foreach ($operation['blocks'] ?? [] as $blockIndex => $block) {
                    if (! is_array($block)) {
                        continue;
                    }
                    foreach (['topset', 'backoff'] as $key) {
                        if (empty($block[$key]) || ! is_array($block[$key])) {
                            continue;
                        }
                        $this->assertSetSchemeValid(
                            $validator,
                            "operations.{$opIndex}.blocks.{$blockIndex}.{$key}",
                            $block[$key],
                        );
                    }
                    foreach ($block['accessories'] ?? [] as $accIndex => $accessory) {
                        if (! is_array($accessory)) {
                            continue;
                        }
                        $this->assertSetSchemeValid(
                            $validator,
                            "operations.{$opIndex}.blocks.{$blockIndex}.accessories.{$accIndex}",
                            $accessory,
                        );
                    }
                }
            }
        });
    }
}
