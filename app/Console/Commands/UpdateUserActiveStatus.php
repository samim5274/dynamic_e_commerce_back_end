<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\PointTransaction;

class UpdateUserActiveStatus extends Command
{

    protected $signature = 'users:update-active-status';
    protected $description = 'Update users active status based on monthly purchase';

    public function handle()
    {
        User::chunk(100, function ($users) {

            foreach ($users as $user) {

                $total = $user->total_calculation;

                $monthlyPurchase = PointTransaction::where('user_id', $user->id)
                    ->where('source', 'purchase')
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->sum('points');

                $isActive = (
                    $total >= 100 &&
                    $monthlyPurchase >= 100
                ) ? 1 : 0;

                // Observer trigger না করে update
                $user->updateQuietly([
                    'is_active' => $isActive,
                ]);
            }
        });

        $this->info('User active status updated successfully.');
    }
}
