<?php

namespace Database\Seeders;

use App\Models\AthleteReadinessEntry;
use App\Models\AthleteReadinessForm;
use App\Models\User;
use App\Support\ReadinessFormSupport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Renomme les athlètes démo @trackcoach.dev avec des noms américains
 * et pose 7 jours de facteurs externes négatifs sur un seul athlète.
 */
class SeedAmericanAthletesAndNegativeReadinessSeeder extends Seeder
{
    /** @var array<string, string> email => American display name */
    private const AMERICAN_NAMES = [
        'daily@trackcoach.dev' => 'Madison Brooks',
        'athlete@trackcoach.dev' => 'Jake Thompson',
        'return@trackcoach.dev' => 'Olivia Parker',
        'thomas@trackcoach.dev' => 'Ethan Carter',
        'sarah@trackcoach.dev' => 'Sarah Mitchell',
        'nicolas@trackcoach.dev' => 'Noah Reynolds',
        'emma@trackcoach.dev' => 'Emma Sullivan',
        'antoine@trackcoach.dev' => 'Anthony Garcia',
        'julie@trackcoach.dev' => 'Julia Bennett',
        'maxime@trackcoach.dev' => 'Max Foster',
        'chloe@trackcoach.dev' => 'Chloe Bryant',
        'lucas@trackcoach.dev' => 'Lucas Price',
        'ines@trackcoach.dev' => 'Iris Ramirez',
    ];

    private const NEGATIVE_ATHLETE_EMAIL = 'athlete@trackcoach.dev';

    public function run(): void
    {
        DB::transaction(function (): void {
            $renamed = 0;
            foreach (self::AMERICAN_NAMES as $email => $name) {
                $updated = User::query()
                    ->where('email', $email)
                    ->where('role', 'athlete')
                    ->update(['name' => $name]);
                $renamed += $updated;
                if ($updated) {
                    $this->command?->line("  {$email} → {$name}");
                }
            }

            $this->command?->info("Noms américains appliqués : {$renamed} athlète(s).");

            $athlete = User::query()
                ->where('email', self::NEGATIVE_ATHLETE_EMAIL)
                ->where('role', 'athlete')
                ->first();

            if ($athlete === null) {
                $this->command?->warn('Athlète cible introuvable : '.self::NEGATIVE_ATHLETE_EMAIL);

                return;
            }

            $this->seedNegativeReadiness($athlete);
            $this->command?->info(
                "Facteurs externes négatifs (7 j) pour {$athlete->name} <{$athlete->email}>.",
            );
        });
    }

    private function seedNegativeReadiness(User $athlete): void
    {
        $today = now()->copy()->startOfDay();
        $windowStart = $today->copy()->subDays(6);

        AthleteReadinessEntry::query()
            ->where('athlete_id', $athlete->id)
            ->whereDate('entry_date', '>=', $windowStart->toDateString())
            ->whereDate('entry_date', '<=', $today->toDateString())
            ->delete();

        $form = AthleteReadinessForm::query()->where('athlete_id', $athlete->id)->first();
        if ($form === null) {
            ReadinessFormSupport::copyToAthlete($athlete);
            $form = AthleteReadinessForm::query()->where('athlete_id', $athlete->id)->first();
        }

        $fields = collect($form?->fields ?? ReadinessFormSupport::defaultFields())->keyBy('preset_key');

        for ($offset = 0; $offset < 7; $offset++) {
            $date = $today->copy()->subDays($offset);
            AthleteReadinessEntry::query()->create([
                'athlete_id' => $athlete->id,
                'entry_date' => $date->toDateString(),
                'values' => $this->negativeValues($fields, $offset),
                'score' => 0,
                'notes' => 'Low recovery / high fatigue (demo negative stretch).',
            ]);
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<string, array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    private function negativeValues($fields, int $offset): array
    {
        $values = [];
        $sommeil = ['lt_5h', '5_6h', 'lt_5h', '5_6h', 'lt_5h', '5_6h', 'lt_5h'];
        $alimentation = ['mauvaise', 'mauvaise', 'moyenne', 'mauvaise', 'mauvaise', 'moyenne', 'mauvaise'];
        $hydratation = ['faible', 'faible', 'moyenne', 'faible', 'faible', 'faible', 'moyenne'];
        $stress = ['eleve', 'eleve', 'eleve', 'moyen', 'eleve', 'eleve', 'eleve'];
        $motivation = ['faible', 'faible', 'moyenne', 'faible', 'faible', 'faible', 'moyenne'];
        $forme = ['1', '1', '2', '1', '2', '1', '1'];

        if ($field = $fields->get('steps')) {
            $values[$field['id']] = 1800 + ($offset * 90) + (($offset % 3) * 40);
        }
        if ($field = $fields->get('kcal')) {
            $values[$field['id']] = (string) (1350 + ($offset * 35));
        }
        if ($field = $fields->get('sommeil')) {
            $values[$field['id']] = $sommeil[$offset];
        }
        if ($field = $fields->get('alimentation')) {
            $values[$field['id']] = $alimentation[$offset];
        }
        if ($field = $fields->get('hydratation')) {
            $values[$field['id']] = $hydratation[$offset];
        }
        if ($field = $fields->get('stress_global')) {
            $values[$field['id']] = $stress[$offset];
        }
        if ($field = $fields->get('motivation')) {
            $values[$field['id']] = $motivation[$offset];
        }
        if ($field = $fields->get('forme_physique')) {
            $values[$field['id']] = $forme[$offset];
        }
        if ($field = $fields->get('forme_mentale')) {
            $values[$field['id']] = $forme[($offset + 1) % 7];
        }

        return $values;
    }
}
