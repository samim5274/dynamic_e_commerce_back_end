<?php

namespace App\Services;

use App\Models\User;
use App\Models\PointTransaction;

class PointService
{
    // 1. Referral Bonus
    public function referralBonus($user, $points = 100)
    {
        // start from direct referrer (A)
        $parent = $user->parentUser;

        // if (!$parent) return;

        while ($parent) {

            // prevent duplicate
            $exists = PointTransaction::where('user_id', $parent->id)
                ->where('source', 'referral')->where('reference_id', $user->id)->exists();


            if(!$exists){
                PointTransaction::create([
                    'user_id' => $parent->id,
                    'type' => 'earn',
                    'points' => $points,
                    'source' => 'referral',
                    'reference_id' => $user->id,
                    'note' => 'Referral bonus from user ID - ' . $user->id,
                ]);
            }

            // move up chain
            $parent = $parent->parentUser;
        }
    }

    // 2. Update Binary Count
    public function updateCounts($user)
    {
        $parent = $user->parentUser;
        $current = $user;

        while ($parent) {

            if ($current->id == $parent->left_child_id) {
                $parent->increment('left_count');
            }

            if ($current->id === $parent->right_child_id) {
                $parent->increment('right_count');
            }

            $parent->refresh();

            $this->checkMatching($parent);

            $current = $parent;
            $parent = $parent->parentUser;
        }
    }

    // 3. Matching Bonus
    public function checkMatching($user)
    {
        $user->refresh();

        $pairs = min($user->left_count, $user->right_count);

        if ($pairs <= 0) return;

        $bonus = $pairs * 100;

        PointTransaction::create([
            'user_id' => $user->id,
            'type' => 'matching',
            'points' => 0,
            'bonus_amount' => $bonus,
            'bonus_status' => 'deposit',
            'source' => 'matching',
            'reference_id' => $user->id,
            'note' => 'Matching bonus for user ID - ' . $user->id,
        ]);

        $user->decrement('left_count', $pairs);
        $user->decrement('right_count', $pairs);
    }
}
