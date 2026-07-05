<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Programme — {{ $block['name'] ?? 'Bloc' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .meta { color: #555; margin-bottom: 16px; }
        .week { margin-top: 18px; page-break-inside: avoid; }
        .week h2 { font-size: 14px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        .day { margin-top: 10px; }
        .day h3 { font-size: 12px; margin: 0 0 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        th, td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>{{ $block['name'] ?? 'Programme' }}</h1>
    <p class="meta">
        Athlète : {{ $block['athlete_name'] ?? '—' }}<br>
        Période : {{ $block['date_start'] ?? '—' }} → {{ $block['date_end'] ?? '—' }}<br>
        1RM : S {{ $block['athlete_one_rm']['squat'] ?? 0 }} · B {{ $block['athlete_one_rm']['bench'] ?? 0 }} · D {{ $block['athlete_one_rm']['deadlift'] ?? 0 }} kg
    </p>

    @php
        $sessions = $block['sessions'] ?? [];
        $byWeek = [];
        foreach ($sessions as $key => $session) {
            [$week, $day] = explode('-', $key) + [null, null];
            $byWeek[(int) $week][(int) $day] = $session;
        }
        ksort($byWeek);
    @endphp

    @foreach ($byWeek as $weekNumber => $days)
        <div class="week">
            <h2>Semaine {{ $weekNumber }}</h2>
            @foreach ($days as $dayNumber => $session)
                <div class="day">
                    <h3>Jour {{ $dayNumber }} — {{ $session['session_label'] ?? 'Séance' }}</h3>
                    @if (!empty($session['session_notes']))
                        <p><em>{{ $session['session_notes'] }}</em></p>
                    @endif
                    <table>
                        <thead>
                            <tr>
                                <th>Exercice</th>
                                <th>Séries</th>
                                <th>Reps</th>
                                <th>Charge</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($session['exercises'] ?? [] as $exercise)
                                <tr>
                                    <td>{{ $exercise['exercise_name'] ?? '—' }}</td>
                                    <td>{{ $exercise['sets'] ?? '—' }}</td>
                                    <td>{{ $exercise['reps'] ?? '—' }}</td>
                                    <td>
                                        @if (!empty($exercise['load']))
                                            {{ $exercise['load'] }} kg
                                        @elseif (!empty($exercise['load_percent']))
                                            {{ $exercise['load_percent'] }} %
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>
    @endforeach
</body>
</html>
