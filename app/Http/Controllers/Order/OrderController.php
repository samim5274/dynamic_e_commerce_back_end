<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\User;
use App\Models\Order;
use App\Models\PointTransaction;

class OrderController extends Controller
{
    public function index(){
        try{
            $orders = Order::with('user')
                ->where('status', '!=' , 'Delivered')
                ->where('status', '!=' , 'Cancelled')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Orders fetched successfully.',
                'data' => $orders,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders. Please try again later.',
            ], 500);
        }
    }

    public function statusFilter(){
        try{
            $orders = Order::with('user')->get();

            return response()->json([
                'success' => true,
                'message' => 'Orders fetched successfully.',
                'data' => $orders,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders. Please try again later.',
            ], 500);
        }
    }

    public function getOrderDetails($reg){
        try{
            $order = Order::with('user')
                ->where('reg', $reg)
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found.',
                    'data' => null,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Order fetched successfully.',
                'data' => $order,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch order. Please try again later.',
            ], 500);
        }
    }

    public function updateStatus(Request $request, $reg)
    {
        try {
            $validated = $request->validate([
                'status' => ['required', 'string', 'in:pending,processing,delivered,cancelled']
            ]);

            $order = Order::where('reg', $reg)->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found.',
                ], 404);
            }

            if ($order->status === $validated['status']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order status already updated.',
                ], 200);
            }

            DB::beginTransaction();

            $order->update([
                'status' => $validated['status']
            ]);

            if (strtolower(trim($validated['status'])) === 'delivered') {

                $exists = PointTransaction::where('reference_id', $order->reg)
                    ->where('source', 'purchase')
                    ->exists();

                if (!$exists) {
                    PointTransaction::create([
                        'user_id'        => $order->user_id,
                        'type'           => 'earn',
                        'points'         => (int) $order->point,
                        'bonus_amount'   => 0,
                        'bonus_status'   => 'deposit',
                        'source'         => 'purchase',
                        'reference_id'   => $order->reg,
                        'note'           => 'Points added for delivered order',
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully.',
                'data' => $order
            ], 200);

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('ORDER STATUS ERROR', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getCustomerDetails($user_id){
        try{
            $customer = User::where('user_id', $user_id)->first();

            return response()->json([
                'success' => true,
                'message' => 'Customer Details fetched successfully.',
                'data' => $customer,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch customer details. Please try again later.',
            ], 500);
        }
    }
}
