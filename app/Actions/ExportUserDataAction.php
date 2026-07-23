<?php

namespace App\Actions;

use App\Models\Message;
use App\Models\MessageThread;
use App\Models\User;

class ExportUserDataAction
{
    /**
     * Build a structured, portable snapshot of everything we hold about a user
     * (GDPR right to data portability).
     *
     * @return array<string, mixed>
     */
    public function execute(User $user): array
    {
        $data = [
            'exported_at' => now()->toIso8601String(),
            'account' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'email_verified_at' => optional($user->email_verified_at)->toIso8601String(),
                'created_at' => optional($user->created_at)->toIso8601String(),
            ],
        ];

        if ($user->role === 'athlete') {
            $data['athlete'] = $this->athleteData($user);
        }

        if ($user->role === 'coach') {
            $data['coach'] = $this->coachData($user);
        }

        $data['messages'] = $this->messagesData($user);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function athleteData(User $user): array
    {
        return [
            'profile' => $user->profile,
            'personal_records' => $user->personalRecords()->get(),
            'training_sessions' => $user->trainingSessions()->get(),
            'competitions' => $user->competitions()->get(),
            'readiness_entries' => $user->readinessEntries()->get(),
            'program_assignments' => $user->programAssignments()->with('template:id,name,goal,level')->get(),
            'session_feedbacks' => $user->sessionFeedbacksAsAthlete()
                ->with('athleteVideos:id,session_feedback_id,kind,original_name,created_at')
                ->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function coachData(User $user): array
    {
        return [
            'profile' => $user->coachProfile,
            'athletes' => $user->athletes()->get(['users.id', 'users.name', 'users.email']),
            'program_templates' => $user->hasMany(\App\Models\ProgramTemplate::class, 'coach_id')->get(),
            'calendar_reminders' => $user->calendarReminders()->get(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function messagesData(User $user): array
    {
        $threadIds = MessageThread::query()
            ->where('coach_id', $user->id)
            ->orWhere('athlete_id', $user->id)
            ->pluck('id');

        return Message::query()
            ->whereIn('thread_id', $threadIds)
            ->orderBy('created_at')
            ->get(['id', 'thread_id', 'sender_id', 'content', 'created_at'])
            ->toArray();
    }
}
