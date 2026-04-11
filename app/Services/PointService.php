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
        $parent = $user->referrer;

        while ($parent) {

            // prevent duplicate
            $exists = PointTransaction::where('user_id', $parent->id)
                ->where('source', 'referral')
                ->where('reference_id', $user->id)
                ->exists();

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
            $parent = $parent->referrer;
        }
    }

    // 2. Update Count
    public function updateCounts($user)
    {
        $this->updateUpLineCounts($user);
        $this->checkMatching($user);
    }

    // 3. Update Up lines Count
    public function updateUpLineCounts($user)
    {
        $parent = $user->parentUser;
        $current = $user;

        while ($parent) {

            if ($current->id == $parent->left_child_id) {
                $parent->increment('left_count', 1);
            }

            if ($current->id == $parent->right_child_id) {
                $parent->increment('right_count', 1);
            }

            $this->checkMatching($parent);

            $current = $parent;
            $parent = $parent->parentUser;
        }
    }

    // 4. Check matching
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
        ]);

        // own match
        $user->increment('own_match', $pairs);

        // reduce matched counts
        $user->decrement('left_count', $pairs);
        $user->decrement('right_count', $pairs);

        // propagate SAME pairs to up lines
        $this->propagateMatch($user, $pairs);
    }

    // 5. Propagation Match
    public function propagateMatch($user, $pairs)
    {
        $parent = $user->parentUser;
        $current = $user;

        while ($parent) {

            if ($current->id == $parent->left_child_id) {
                $parent->increment('left_match', $pairs);
            }

            if ($current->id == $parent->right_child_id) {
                $parent->increment('right_match', $pairs);
            }

            // ADD MATCHING BONUS TRANSACTION FOR EACH UP LINE
            // $exists = PointTransaction::where('user_id', $parent->id)
            //     ->where('source', 'matching_propagation')
            //     ->where('reference_id', $user->id)
            //     ->exists();

            // if (!$exists) {
            //     PointTransaction::create([
            //         'user_id' => $parent->id,
            //         'type' => 'matching',
            //         'points' => 0,
            //         'bonus_amount' => $pairs * 100,
            //         'bonus_status' => 'deposit',
            //         'source' => 'matching_propagation',
            //         'reference_id' => $user->id,
            //         'note' => 'Matching bonus propagated from user ID - ' . $user->id,
            //     ]);
            // }

            $current = $parent;
            $parent = $parent->parentUser;
        }
    }

}
