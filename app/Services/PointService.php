<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Product;
use App\Models\PointTransaction;

class PointService
{
    // 1. Referral Bonus
    public function referralBonus($user)
    {
        DB::transaction(function () use ($user) {
            
            $referrerId = $user->referrer_id ?? ($user->referrer ? $user->referrer->id : null);

            if (!$referrerId) {
                Log::warning("Referral system: No referrer found for user ID - " . $user->id);
                return; 
            }

            $referrer = User::lockForUpdate()->find($referrerId);

            if ($referrer) {
                
                $exists = PointTransaction::where('user_id', $referrer->id)
                    ->where('source', 'referral')
                    ->where('reference_id', $user->id)
                    ->exists();

                if (!$exists) {
                    
                    PointTransaction::create([
                        'user_id'      => $referrer->id,
                        'type'         => 'bonus',
                        'points'       => 0, 
                        'bonus_amount' => 200,
                        'bonus_status' => 'credit',
                        'source'       => 'referral',
                        'reference_id' => $user->id,
                        'note'         => 'Direct referral bonus from User ID: ' . $user->id,
                    ]);

                    $referrer->increment('wallet_balance', 200);
                }
            }
        });
    }

    // 2. Update Count
    public function updateCounts($user, $productId)
    {
        DB::transaction(function () use ($user, $productId) {

            $user = User::lockForUpdate()->find($user->id);
            if (!$user) return;

            $points = Product::find($productId);
            if (!$points) return;

            $this->addPointsToUpline($user, $points->point);
            $this->addReferralPointsToUpline($user, $points->point);
        });
    }

    // 4. Add point to upline
    public function addPointsToUpline($user, $points)
    {
        $current = $user;

        while ($current->parent_id) {

            $parent = User::lockForUpdate()->find($current->parent_id);
            if (!$parent) break;
            // INACTIVE USER হলে skip করবে
            if (!$parent->isActive()) {
                $current = $parent;
                continue;
            }

            // LEFT SIDE
            if ($current->id == $parent->left_child_id) {
                $parent->left_total_point += $points;
                $parent->left_carry_point += $points;
            }

            // RIGHT SIDE
            if ($current->id == $parent->right_child_id) {
                $parent->right_total_point += $points;
                $parent->right_carry_point += $points;
            }

            // SAVE FIRST
            $parent->save();

            // MATCH AFTER SAVE
            $this->processMatching($parent);

            $current = $parent;
        }
    }

    // 5. Propagation Match
    public function processMatching(User $user)
    {
        $user = User::lockForUpdate()->find($user->id);

        if (!$user) return;

        $left  = $user->left_carry_point;
        $right = $user->right_carry_point;

        // কত pair possible
        $matches = intdiv(min($left, $right), 100);

        if ($matches <= 0) return;

        $usedPoints = $matches * 100;
        $bonus = $matches * 100;

        // Deduct carry (VERY IMPORTANT)
        $user->left_carry_point  -= $usedPoints;
        $user->right_carry_point -= $usedPoints;

        // Update stats
        $user->total_match += $matches;
        // $user->own_total_point += $usedPoints * 2;

        // Wallet update
        $user->wallet_balance += $bonus;

        $user->save();

        // Transaction log
        PointTransaction::create([
            'user_id'      => $user->id,
            'type'         => 'matching',
            'points'       => 0,
            'bonus_amount' => $bonus,
            'bonus_status' => 'credit',
            'source'       => 'matching',
            'reference_id' => null,
            'note'         => "Matching Bonus",
        ]);
    }

    public function addReferralPointsToUpline($user, $points)
    {
        $current = $user;

        while ($current->parent_id) {

            $parent = User::lockForUpdate()->find($current->parent_id);

            if (!$parent) break;

            // 1. Update own total point
            // $parent->own_total_point += $points;
            // $parent->save();

            // 2. Insert transaction log
            PointTransaction::create([
                'user_id'      => $parent->id,
                'type'         => 'earn',
                'points'       => $points,
                'bonus_amount' => 0,
                'bonus_status' => 'credit',
                'source'       => 'referral',
                'reference_id' => $user->id,
                'note'         => 'Referral point from user ID: ' . $user->id,
            ]);

            $current = $parent;
        }
    }

    // 7. Distribute order point
    public function distributeOrderPoints(User $user, $points, $orderReg)
    {
        DB::transaction(function () use ($user, $points, $orderReg) 
        {
            $user = User::lockForUpdate()->find($user->id);
            $user->increment('own_total_point', $points);

            PointTransaction::create([
                'user_id'        => $user->id,
                'type'           => 'earn',
                'points'         => $points,
                'bonus_amount'   => 0,
                'bonus_status'   => 'credit',
                'source'         => 'purchase',
                'reference_id'   => $orderReg,
                'note'           => 'Own purchase points for order: ' . $orderReg,
            ]);
        });
    }
}