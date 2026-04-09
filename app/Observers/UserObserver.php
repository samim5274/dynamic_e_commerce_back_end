<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    public function updating(User $user){

        if($user->point >= 2500000){
            $user->rank = "Platinum";
        }
        elseif($user->point >= 100000){
            $user->rank = "Diamond";
        }
        elseif($user->point >= 50000){
            $user->rank = "Gold";
        } elseif ($user->points >= 10000) {
            $user->rank = 'Silver';
        } else {
            $user->rank = 'Bronze';
        }

        // is match condition added here
        if($user->left_child_id && $user->right_child_id){
            $user->is_match = true;
        }else {
            $user->is_match = false;
        }
    }
}
