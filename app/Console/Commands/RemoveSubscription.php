<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class RemoveSubscription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:remove-subscription';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes subscriptions for users whose removal is today';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
       $this->info('Removing subscriptions for users whose removal is today ' . now()->toDateString());
       $users = $this->getUsersToRemove();
       $this->info(count($users) . ':users found to remove subscriptions.');

         foreach ($users as $user) {
             $user->unsubscribe();
              $this->info('Removed subscription for user ID: ' . $user->id);
         }
    }

    private function getUsersToRemove(): Collection
    {
        return User::toRemoveSubscribedToday()->get();
    }
}
