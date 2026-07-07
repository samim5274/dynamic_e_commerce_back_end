<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

use App\Models\User;
use App\Models\PointTransaction;

class ReportController extends Controller
{
    public function pointStatement()
    {
        try{
            $points = PointTransaction::with('user')->latest()->paginate(20);

            $totalPoint = PointTransaction::sum('points');

            return response()->json([
                'success' => true,
                'message' => 'Points Report fetched successfully.',
                'data' => $points,
                'total_point' => (float) $totalPoint,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch points. Please try again later.',
            ], 500);
        }
    }
}
