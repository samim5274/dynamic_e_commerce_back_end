<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Order;

class OrderController extends Controller
{
    public function index(){
        try{
            $orders = Order::with('user')
                // ->where('status', '!=' , 'Delivered')
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
            // Validate request
            $validated = $request->validate([
                'status' => ['required', 'string', 'in:pending,processing,delivered,cancelled']
            ]);

            // Find order
            $order = Order::where('reg', $reg)->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found.',
                ], 404);
            }

            // Check if already same status
            if ($order->status === $validated['status']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order status already updated.',
                ], 200);
            }

            // Update
            $order->update([
                'status' => $validated['status']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully.',
                'data' => $order
            ], 200);

        } catch (\Throwable $e) {

            \Log::error('Order status update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update order status. Please try again later.',
            ], 500);
        }
    }
}
