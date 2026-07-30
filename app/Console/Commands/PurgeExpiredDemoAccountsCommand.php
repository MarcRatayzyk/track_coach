<?php

namespace App\Console\Commands;

use App\Actions\DeleteUserAccountAction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeExpiredDemoAccountsCommand extends Command
{
    protected $signature = 'demo:purge-expired';

    protected $description = 'Delete expired demo sandbox coaches and their demo athletes';

    public function handle(DeleteUserAccountAction $deleteAccount): int
    {
        $expiredCoaches = User::query()
            ->where('role', 'coach')
            ->where('is_demo', true)
            ->whereNotNull('demo_expires_at')
            ->where('demo_expires_at', '<', now())
            ->get();

        $purged = 0;

        foreach ($expiredCoaches as $coach) {
            DB::transaction(function () use ($coach, $deleteAccount, &$purged): void {
                $athleteIds = $coach->athletes()->pluck('users.id');

                $deleteAccount->execute($coach);

                User::query()
                    ->whereIn('id', $athleteIds)
                    ->where('is_demo', true)
                    ->each(function (User $athlete) use ($deleteAccount): void {
                        $deleteAccount->execute($athlete);
                    });

                $purged++;
            });
        }

        $this->info("Purged {$purged} expired demo coach account(s).");

        return self::SUCCESS;
    }
}
