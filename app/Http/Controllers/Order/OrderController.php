<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

use App\Models\User;
use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\Cart;

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

    public function confirmOrder(Request $request, $reg)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized user',
            ], 401);
        }

        $cartItems = Cart::where('reg', $reg)->get();
        if (!$cartItems->count()) {
            return response()->json([
                'success' => false,
                'message' => "Cart items is empty.",
            ]);
        }

        $order = Order::where('reg', $reg)->first();
        if ($order) {
            return response()->json([
                'success' => false,
                'message' => "Order already created.",
            ]);
        }

        $amount = $cartItems->sum(fn($item) => $item->price * $item->quantity);
        $point = $cartItems->sum(fn($item) => $item->point * $item->quantity);
        $discount = $cartItems->sum(fn($item) => $item->discount * $item->quantity);

        $tran_id = uniqid('SSLCZ_');

        $order = Order::create([
            'reg' => $reg,
            'date' => now()->toDateString(),
            'user_id' => $user->id,

            'amount' => $amount,
            'discount' => $discount,
            'payable_amount' => $amount,
            'currency' => 'BDT',
            'point' => (int) $point,

            'payment_method' => "Cash",
            'transaction_id' => $tran_id,
            'is_paid' => false,
            'paid_at' => NULL,

            'status' => 'Pending',

            'contact_number' => $user->phone,
            'shipping_address' => $user->present_address,
        ]);

        return response()->json([
            'success' => false,
            'message' => "Your order is confirmed. Thanks You.",
        ]);

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
            $statusInput = trim($request->status);

            $validStatuses = [
                'pending'          => 'Pending',
                'confirmed'        => 'Confirmed',
                'processing'       => 'Processing',
                'picked'           => 'Picked',
                'shipped'          => 'Shipped',
                'out for delivery' => 'Out for Delivery',
                'delivered'        => 'Delivered',
                'cancelled'        => 'Cancelled',
                'failed'           => 'Failed',
                'returned'         => 'Returned',
            ];

            $lowerInput = strtolower($statusInput);
            $normalizedStatus = $validStatuses[$lowerInput] ?? $statusInput;

            $request->merge(['status' => $normalizedStatus]);

            $validated = $request->validate([
                'status' => [
                    'required',
                    'string',
                    'in:' . implode(',', array_values($validStatuses))
                ]
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
                    'message' => 'Order status is already ' . $order->status,
                ], 200);
            }

            DB::beginTransaction();

            $currentStatus = $validated['status'];
            $statusKey = strtolower($currentStatus);

            $timestampMapping = [
                'confirmed' => 'confirmed_at',
                'shipped'   => 'shipped_at',
                'delivered' => 'delivered_at',
                'cancelled' => 'cancelled_at',
            ];

            $updateData = ['status' => $currentStatus];

            if (isset($timestampMapping[$statusKey])) {
                $column = $timestampMapping[$statusKey];
                $updateData[$column] = now()->toDateString();
            }

            if ($statusKey === 'delivered') {
                $updateData['paid_at'] = now()->toDateString();
                $updateData['is_paid'] = 1;
            }

            $order->update($updateData);

            if ($statusKey === 'delivered') {
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
                'data' => $order->fresh()
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status selected.',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('ORDER STATUS ERROR', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error. Please try again.',
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
