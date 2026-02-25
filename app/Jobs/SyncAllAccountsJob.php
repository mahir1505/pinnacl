<?php

namespace App\Jobs;

use App\Models\SocialAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncAllAccountsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        SocialAccount::with('user')->chunk(50, function ($accounts) {
            foreach ($accounts as $account) {
                SyncSocialAccountJob::dispatch($account)
                    ->delay(now()->addSeconds(rand(0, 30)));
            }
        });
    }
}
