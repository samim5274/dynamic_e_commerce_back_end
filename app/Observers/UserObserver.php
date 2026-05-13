<?php

namespace App\Observers;

use App\Models\User;

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
        if ($total >= 2500000) {
            $user->rank = "Platinum";
        } elseif ($total >= 100000) {
            $user->rank = "Diamond";
        } elseif ($total >= 50000) {
            $user->rank = "Gold";
        } elseif ($total >= 10000) {
            $user->rank = 'Silver';
        } else {
            $user->rank = 'Bronze';
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
