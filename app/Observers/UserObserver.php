<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    public function updating(User $user)
    {
        $total = $user->total_calculation;

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

        // 2. Is Active Condition
        $user->is_active = ($total < 100) ? 0 : 1;

        // is match condition added here
        if($user->left_child_id && $user->right_child_id){
            $user->is_match = true;
        }else {
            $user->is_match = false;
        }
    }
}
