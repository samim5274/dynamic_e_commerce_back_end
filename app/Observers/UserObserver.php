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

        $newRank = "Bronze";
        $cashBonus = 0;

        // ইমেজ প্ল্যান অনুযায়ী র্যাঙ্ক এবং ক্যাশ বোনাস ম্যাপিং
        if ($left >= 50000000 && $right >= 50000000) {
            $newRank = "Crown Diamond";
            $cashBonus = 2500000; // 2.5-Core (25 Lakh)
        } elseif ($left >= 20000000 && $right >= 20000000) {
            $newRank = "Royal Diamond";
            $cashBonus = 10000000; // 1-Core (1 Crore)
        } elseif ($left >= 10000000 && $right >= 10000000) {
            $newRank = "Elite Diamond";
            $cashBonus = 5000000; // 50 Lakh
        } elseif ($left >= 5000000 && $right >= 5000000) {
            $newRank = "Black Diamond";
            $cashBonus = 2500000; // 25 Lakh
        } elseif ($left >= 2000000 && $right >= 2000000) {
            $newRank = "Red Diamond";
            $cashBonus = 1000000; // 10 Lakh
        } elseif ($left >= 1000000 && $right >= 1000000) {
            $newRank = "Purple Diamond";
            $cashBonus = 500000;  // 5 Lakh
        } elseif ($left >= 500000 && $right >= 500000) {
            $newRank = "Green Diamond";
            $cashBonus = 250000;  // 2.5 Lakh
        } elseif ($left >= 200000 && $right >= 200000) {
            $newRank = "Blue Diamond";
            $cashBonus = 100000;  // 1 Lakh
        } elseif ($left >= 100000 && $right >= 100000) {
            $newRank = "Diamond";
            $cashBonus = 50000;   // 50,000 Tk
        } elseif ($left >= 50000 && $right >= 50000) {
            $newRank = "Platinum";
            $cashBonus = 25000;   // 25,000 Tk
        } elseif ($left >= 20000 && $right >= 20000) {
            $newRank = "Gold";
            $cashBonus = 10000;   // 10,000 Tk
        } elseif ($left >= 10000 && $right >= 10000) {
            $newRank = "Silver";
            $cashBonus = 5000;    // 5000 Tk
        }

        // র্যাঙ্ক যদি পরিবর্তন হয় (আগের চেয়ে আপগ্রেড হয়)
        if ($user->rank !== $newRank) {

            $oldRank = $user->rank;
            $user->rank = $newRank;

            // ব্রোঞ্জ বা কোনো বোনাস না থাকলে স্কিপ করবে
            if ($cashBonus > 0 && $newRank !== "Bronze") {

                $bonusExists = PointTransaction::where('user_id', $user->id)
                    ->where('source', 'rank_bonus')
                    ->where('note', 'like', "%Rank: {$newRank}%")
                    ->exists();

                if (!$bonusExists) {

                    // ১. লেজার ডিস্ট্রিবিউশন লগ ইনসার্ট
                    PointTransaction::create([
                        'user_id'        => $user->id,
                        'type'           => 'bonus',
                        'points'         => 0,
                        'bonus_amount'   => $cashBonus,
                        'bonus_status'   => 'credit',
                        'source'         => 'rank_bonus',
                        'reference_id'   => null,
                        'note'           => "Rank Reward Cash Bonus for achieving Rank: {$newRank}",
                    ]);

                    // ২. ইউজারের মেইন ওয়ালেটে টাকা রিফ্লেক্ট করা
                    $user->wallet_balance += $cashBonus;
                }
            }
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
        | - Current month purchase must be >= 100
        |
        */
        if (is_null($user->is_active)) {
            $user->is_active = (
                $total >= 100 &&
                $monthlyPurchase >= 100
            ) ? 1 : 0;
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Match Condition
        |--------------------------------------------------------------------------
        */
        $user->is_match = (
            $user->left_child_id &&
            $user->right_child_id
        ) ? true : false;


        /*
        |--------------------------------------------------------------------------
        | 5. Designation Calculation
        |--------------------------------------------------------------------------
        |
        | Dynamic Star  = Direct Referral >= 10
        | Dynamic Club  = Directly Referred Dynamic Star >= 10
        |
        */

        // $totalReferrals = User::where('refer_id', $user->id)->count();

        // if ($totalReferrals >= 10) {
        //     $user->designation = 'Dynamic Star';
        // } else {
        //     $user->designation = null;
        // }


        /*
        |--------------------------------------------------------------------------
        | 6. Dynamic Club
        |--------------------------------------------------------------------------
        */

        // $dynamicStarCount = User::where('refer_id', $user->id)
        //     ->where('designation', 'Dynamic Star')
        //     ->count();

        // if ($dynamicStarCount >= 10) {
        //     $user->designation = 'Dynamic Club';
        // }
    }
}
