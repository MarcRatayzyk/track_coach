<?php

namespace App\Http\Requests\Concerns;

use App\Support\MatchPlanData;
use Illuminate\Validation\Validator;

trait ValidatesMatchPlanData
{
    protected function matchPlanDataRules(): array
    {
        return [
            'match_plan_data' => ['nullable', 'array'],
            'match_plan_data.mode' => ['nullable', 'in:text,structured'],
            'match_plan_data.text' => ['nullable', 'string', 'max:5000'],
            'match_plan_data.scenarios' => ['nullable', 'array', 'max:10'],
            'match_plan_data.scenarios.*.id' => ['nullable', 'string', 'max:64'],
            'match_plan_data.scenarios.*.name' => ['required_with:match_plan_data.scenarios', 'string', 'max:120'],
            'match_plan_data.scenarios.*.lifts' => ['nullable', 'array'],
            'match_plan_data.scenarios.*.lifts.squat.attempt1' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'match_plan_data.scenarios.*.lifts.squat.attempt2' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'match_plan_data.scenarios.*.lifts.squat.attempt3' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'match_plan_data.scenarios.*.lifts.bench.attempt1' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'match_plan_data.scenarios.*.lifts.bench.attempt2' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'match_plan_data.scenarios.*.lifts.bench.attempt3' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'match_plan_data.scenarios.*.lifts.deadlift.attempt1' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'match_plan_data.scenarios.*.lifts.deadlift.attempt2' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'match_plan_data.scenarios.*.lifts.deadlift.attempt3' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'match_plan_data.warmups' => ['nullable', 'array'],
            'match_plan_data.warmups.squat' => ['nullable', 'array', 'max:10'],
            'match_plan_data.warmups.squat.*.weight' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'match_plan_data.warmups.squat.*.reps' => ['nullable', 'integer', 'min:1', 'max:50'],
            'match_plan_data.warmups.bench' => ['nullable', 'array', 'max:10'],
            'match_plan_data.warmups.bench.*.weight' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'match_plan_data.warmups.bench.*.reps' => ['nullable', 'integer', 'min:1', 'max:50'],
            'match_plan_data.warmups.deadlift' => ['nullable', 'array', 'max:10'],
            'match_plan_data.warmups.deadlift.*.weight' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'match_plan_data.warmups.deadlift.*.reps' => ['nullable', 'integer', 'min:1', 'max:50'],
            'match_plan' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $data = $this->input('match_plan_data');
            if ($data === null) {
                return;
            }

            $mode = $data['mode'] ?? 'text';
            if ($mode === 'structured' && empty($data['scenarios'])) {
                $validator->errors()->add(
                    'match_plan_data.scenarios',
                    __('messages.validation.match_plan_scenario_required'),
                );
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function competitionPayload(): array
    {
        $validated = $this->validated();
        $planData = isset($validated['match_plan_data'])
            ? MatchPlanData::normalize($validated['match_plan_data'])
            : null;

        $payload = collect($validated)
            ->except(['match_plan_data', 'match_plan'])
            ->all();

        if ($planData !== null) {
            $payload['match_plan_data'] = $planData;
            $payload['match_plan'] = MatchPlanData::toText($planData);
        } elseif (array_key_exists('match_plan', $validated)) {
            $payload['match_plan'] = $validated['match_plan'];
        }

        return $payload;
    }

    public function messages(): array
    {
        return [
            'match_plan_data.scenarios.*.name.required_with' => __('messages.validation.scenario_name_required'),
            'match_plan_data.scenarios.*.lifts.*.attempt1.numeric' => __('messages.validation.attempt_numeric', ['n' => 1]),
            'match_plan_data.scenarios.*.lifts.*.attempt2.numeric' => __('messages.validation.attempt_numeric', ['n' => 2]),
            'match_plan_data.scenarios.*.lifts.*.attempt3.numeric' => __('messages.validation.attempt_numeric', ['n' => 3]),
            'match_plan_data.scenarios.*.lifts.*.attempt1.min' => __('messages.validation.attempt_min', ['n' => 1]),
            'match_plan_data.scenarios.*.lifts.*.attempt2.min' => __('messages.validation.attempt_min', ['n' => 2]),
            'match_plan_data.scenarios.*.lifts.*.attempt3.min' => __('messages.validation.attempt_min', ['n' => 3]),
            'match_plan_data.scenarios.*.lifts.*.attempt1.max' => __('messages.validation.attempt_max', ['n' => 1]),
            'match_plan_data.scenarios.*.lifts.*.attempt2.max' => __('messages.validation.attempt_max', ['n' => 2]),
            'match_plan_data.scenarios.*.lifts.*.attempt3.max' => __('messages.validation.attempt_max', ['n' => 3]),
        ];
    }
}
