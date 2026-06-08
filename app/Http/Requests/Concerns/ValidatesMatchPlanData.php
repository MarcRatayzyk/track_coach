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
                    'Ajoute au moins un scénario pour le plan structuré.',
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
            'match_plan_data.scenarios.*.name.required_with' => 'Le nom du scénario est requis.',
            'match_plan_data.scenarios.*.lifts.*.attempt1.numeric' => 'L\'essai 1 doit être un nombre.',
            'match_plan_data.scenarios.*.lifts.*.attempt2.numeric' => 'L\'essai 2 doit être un nombre.',
            'match_plan_data.scenarios.*.lifts.*.attempt3.numeric' => 'L\'essai 3 doit être un nombre.',
            'match_plan_data.scenarios.*.lifts.*.attempt1.min' => 'L\'essai 1 doit être supérieur ou égal à :min.',
            'match_plan_data.scenarios.*.lifts.*.attempt2.min' => 'L\'essai 2 doit être supérieur ou égal à :min.',
            'match_plan_data.scenarios.*.lifts.*.attempt3.min' => 'L\'essai 3 doit être supérieur ou égal à :min.',
            'match_plan_data.scenarios.*.lifts.*.attempt1.max' => 'L\'essai 1 doit être inférieur ou égal à :max.',
            'match_plan_data.scenarios.*.lifts.*.attempt2.max' => 'L\'essai 2 doit être inférieur ou égal à :max.',
            'match_plan_data.scenarios.*.lifts.*.attempt3.max' => 'L\'essai 3 doit être inférieur ou égal à :max.',
        ];
    }
}
