<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

use App\Models\User;
use App\Models\PointTransaction;


class AccountController extends Controller
{
    public function index(){
        try {
            $userId = Auth::id();

            $pointTransactions = PointTransaction::with('user')
                ->where('user_id', $userId)
                ->latest()
                ->get();

            if ($pointTransactions->isEmpty()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'No transactions found.',
                    'data'    => [],
                ], 200);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Transactions retrieved successfully.',
                'data'    => $pointTransactions,
            ], 200);

        } catch (Exception $e) {
            Log::error("Point Fetch Error: " . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Something went wrong while fetching transactions.',
            ], 500);
        }
    }
}
