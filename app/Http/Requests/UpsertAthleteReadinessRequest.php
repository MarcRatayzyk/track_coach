<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\ReadinessFormSupport;
use Illuminate\Foundation\Http\FormRequest;

class UpsertAthleteReadinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        $athlete = $this->route('athlete');

        return $this->user()?->id === $athlete?->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $athlete */
        $athlete = $this->route('athlete');
        $form = ReadinessFormSupport::ensureAthleteHasForm($athlete);
        $fields = ReadinessFormSupport::normalizeFields($form->fields ?? []);

        return ReadinessFormSupport::entryValueRules($fields);
    }
}
