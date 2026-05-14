<?php

namespace App\Observers;

use App\Models\User;
use App\Models\PointTransaction;
use Carbon\Carbon;

class UserObserver
{
    public function updating(User $user)
    {
        $total = $user->total_calculation;

        /*
        |--------------------------------------------------------------------------
        | 1. Rank Condition
        |--------------------------------------------------------------------------
        */

        // 1. Rank Condition 
        $left  = $user->left_total_point ?? 0;
        $right = $user->right_total_point ?? 0;

        if ($left >= 50000000 && $right >= 50000000) {
            $user->rank = "Crown Diamond";

        } elseif ($left >= 20000000 && $right >= 20000000) {
            $user->rank = "Royal Diamond";

        } elseif ($left >= 10000000 && $right >= 10000000) {
            $user->rank = "Elite Diamond";

        } elseif ($left >= 5000000 && $right >= 5000000) {
            $user->rank = "Black Diamond";

        } elseif ($left >= 2000000 && $right >= 2000000) {
            $user->rank = "Red Diamond";

        } elseif ($left >= 1000000 && $right >= 1000000) {
            $user->rank = "Purple Diamond";

        } elseif ($left >= 500000 && $right >= 500000) {
            $user->rank = "Green Diamond";

        } elseif ($left >= 200000 && $right >= 200000) {
            $user->rank = "Blue Diamond";

        } elseif ($left >= 100000 && $right >= 100000) {
            $user->rank = "Diamond";

        } elseif ($left >= 50000 && $right >= 50000) {
            $user->rank = "Platinum";

        } elseif ($left >= 20000 && $right >= 20000) {
            $user->rank = "Gold";

        } elseif ($left >= 10000 && $right >= 10000) {
            $user->rank = "Silver";

        } else {
            $user->rank = "Bronze";
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Monthly Purchase Check
        |--------------------------------------------------------------------------
        */
        $monthlyPurchase = PointTransaction::where('user_id', $user->id)
            ->where('source', 'purchase')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('points');
        
        /*
        |--------------------------------------------------------------------------
        | 3. Active Condition
        |--------------------------------------------------------------------------
        |
        | Rule:
        | - Total point must be >= 100
        | - Current month purchase must be >= 50
        |
        */
        $user->is_active = (
            $total >= 100 &&
            $monthlyPurchase >= 50
        ) ? 1 : 0;

        /*
        |--------------------------------------------------------------------------
        | 4. Match Condition
        |--------------------------------------------------------------------------
        */
        $user->is_match = (
            $user->left_child_id &&
            $user->right_child_id
        ) ? true : false;
    }
}
