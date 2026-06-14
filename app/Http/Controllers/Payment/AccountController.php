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

            $pointTransactions = PointTransaction::with(['user', 'referenceUser'])
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

    public function adminStatement()
    {
        try {
            $pointTransactions = PointTransaction::with(['user', 'referenceUser'])
                ->latest()
                ->paginate(50);

            $totalCredit = PointTransaction::where('bonus_status', 'credit')->sum('bonus_amount');
            $totalDebit  = PointTransaction::where('bonus_status', 'debit')->sum('bonus_amount');
            // $netTotal    = $totalCredit - $totalDebit;

            if ($pointTransactions->total() === 0) {
                return response()->json([
                    'status'       => 'success',
                    'message'      => 'No transactions found.',
                    'data'         => [],
                    'total_credit' => 0,
                    'total_debit'  => 0,
                    // 'net_total'    => 0,
                ], 200);
            }

            return response()->json([
                'status'        => 'success',
                'message'       => 'Transactions retrieved successfully.',
                'data'          => $pointTransactions->items(),
                'current_page'  => $pointTransactions->currentPage(),
                'last_page'     => $pointTransactions->lastPage(),
                'total'         => $pointTransactions->total(),
                'per_page'      => $pointTransactions->perPage(),
                'from'          => $pointTransactions->firstItem(),
                'to'            => $pointTransactions->lastItem(),

                'total_credit'  => (float) $totalCredit,
                'total_debit'   => (float) $totalDebit,
                // 'net_total'     => (float) $netTotal,
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
