<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\order;

class RegGenerator
{
    public static function generateOrderReg(int $userId): string
    {
        return DB::transaction(function () use ($userId) {

            $prefix = now()->format('Ymd') . $userId;

            $lastReg = order::where('user_id', $userId)
                ->whereDate('created_at', today())
                ->lockForUpdate()
                ->latest('id')
                ->value('reg');

            if ($lastReg && str_starts_with($lastReg, $prefix)) {
                $lastSeq = (int) substr($lastReg, -3);
                $nextSeq = str_pad($lastSeq + 1, 3, '0', STR_PAD_LEFT);
                return $prefix . $nextSeq;
            }

            return $prefix . '001';
        });
    }
}
