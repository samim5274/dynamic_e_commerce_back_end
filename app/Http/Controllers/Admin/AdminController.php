<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Exception;
use Throwable;

use App\Models\User;
use App\Models\PointTransaction;
use App\Models\Transaction;
use App\Services\PointService;
use App\Services\RegGenerator;
use App\Models\Order;
use App\Models\Product;
use App\Models\Cart;
use App\Models\ProductVariant;

class AdminController extends Controller
{

    public function index()
    {
        try {

            $data = User::where('role', '!=', 'super_admin')->get();

            return response()->json([
                'success' => true,
                'message' => 'Users data fetched successfully.',
                'data' => $data,
            ], 200);

        } catch (Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while fetching users data.',
                'error'   => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function transaction()
    {
        try {
            // Get logged in user
            $userId = Auth::id();

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized user.'
                ], 401);
            }

            $data = Transaction::with('user')->latest()->get();

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal data fetched successfully.',
                'data' => $data,
            ], 200);

        } catch (Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while fetching withdrawal data.',
                'error'   => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function starClubUsers()
    {
        try {

            $starUsers = Cache::remember("star_club_users_global", 60, function () {
                return User::query()
                    ->whereNull('designation')
                    ->withCount('referrals')
                    ->has('referrals', '>=', 10) // ডাটাবেস লেভলেই ১০ বা তার বেশি রেফারাল ফিল্টার করবে
                    ->orderByDesc('referrals_count') // ডাটাবেস থেকেই সর্ট হয়ে আসবে
                    ->get();
            });

            return response()->json([
                'success' => true,
                'message' => 'Star club users fetched successfully.',
                'data' => $starUsers,
            ]);

        } catch (Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function addMoneyStarClub(Request $request, $user_id)
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            DB::beginTransaction();

            $user = User::findOrFail($user_id);

            // Create transaction
            $transaction = new PointTransaction();
            $transaction->user_id = $user->id;
            $transaction->type = 'bonus';
            $transaction->points = 0;
            $transaction->bonus_amount = $request->amount;
            $transaction->bonus_status = 'credit';
            $transaction->source = 'star_club';
            $transaction->note = 'Star Club bonus amount added';
            $transaction->save();

            // Update user designation
            $user->designation = 'star_club';
            $user->wallet_balance = ($user->wallet_balance ?? 0) + $request->amount;
            $user->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Money added successfully.',
                'data' => [
                    'user_id' => $user->id,
                    'amount' => $transaction->bonus_amount,
                    'designation' => $user->designation,
                ]
            ], 200);

        } catch (Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function dynamicClubUsers()
    {
        try {

            $starUsers = Cache::remember("dynamic_star_users", 60, function () {
                return User::query()
                    ->select('users.*')
                    // ১. মূল ইউজারের ডেজিগনেশন 'star club' হতে হবে
                    ->where('designation', 'star_club')

                    // ২. শুধুমাত্র সেই স্টার ক্লাব রেফারালগুলো কাউন্ট হবে যাদের ডেজিগনেশনও 'star club'
                    ->withCount(['referrals as star_referrals_count' => function ($q) {
                        $q->where('designation', 'star_club');
                    }])

                    // ৩. ডাটাবেস লেভেলেই ফিল্টার: এমন ইউজার যাদের এই নির্দিষ্ট রেফারাল সংখ্যা ১০ বা তার বেশি
                    ->whereHas('referrals', function ($q) {
                        $q->where('designation', 'star_club');
                    }, '>=', 10)

                    // ৪. ডাটাবেস থেকেই বড় থেকে ছোট ক্রমানুসারে সাজিয়ে আনা
                    ->orderByDesc('star_referrals_count')
                    ->get();
            });

            return response()->json([
                'success' => true,
                'message' => 'Dynamic users fetched successfully.',
                'data' => $starUsers,
                'count_refer' => $starUsers->count(), // মোট কতজন এমন ডাইনামিক ইউজার পাওয়া গেল
            ]);

        } catch (Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function addMoneyDynamicClub(Request $request, $user_id)
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            DB::beginTransaction();

            $user = User::findOrFail($user_id);

            // Create transaction
            $transaction = new PointTransaction();
            $transaction->user_id = $user->id;
            $transaction->type = 'bonus';
            $transaction->points = 0;
            $transaction->bonus_amount = $request->amount;
            $transaction->bonus_status = 'credit';
            $transaction->source = 'dynamic_club';
            $transaction->note = 'Dynamic Club bonus amount added';
            $transaction->save();

            // Update user designation
            $user->designation = 'dynamic_club';
            $user->wallet_balance = ($user->wallet_balance ?? 0) + $request->amount;
            $user->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Money added successfully.',
                'data' => [
                    'user_id' => $user->id,
                    'amount' => $transaction->bonus_amount,
                    'designation' => $user->designation,
                ]
            ], 200);

        } catch (Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function addMoney(Request $request, $user_id)
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            DB::beginTransaction();

            $user = User::findOrFail($user_id);

            // Create transaction
            $transaction = new PointTransaction();
            $transaction->user_id = $user->id;
            $transaction->type = 'spend';
            $transaction->points = 0;
            $transaction->bonus_amount = $request->amount;
            $transaction->bonus_status = 'credit';
            $transaction->source = 'add_money_from_super_admin';
            $transaction->note = 'Add money from DBMBL';
            $transaction->save();

            // Update user designation
            $user->wallet_balance = ($user->wallet_balance ?? 0) + $request->amount;
            $user->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Money added successfully.',
                'data' => [
                    'user_id' => $user->id,
                    'amount' => $transaction->bonus_amount,
                    'designation' => $user->designation,
                ]
            ], 200);

        } catch (Exception $e) {

            DB::rollBack();

            \Log::error('Add Money Error', [
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

    public function getUserDetails($user_id){
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

    public function changeUserRole(Request $request)
    {
        try {

            $request->validate([
                'user_id'   => ['required', 'integer', 'exists:users,id'],
                'role'      => ['required', 'string', 'in:customer,admin,super_admin'],
            ]);

            $user = User::where('id', $request->user_id)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User profile not found.',
                ], 404);
            }

            $user->update([
                'role' => $request->role,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User role updated successfully.',
                'data' => [
                    'user_id' => $user->user_id,
                    'role'    => $user->role,
                ]
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Throwable $e) {

            Log::error('User role update failed', [
                'user_id' => $request->user_id ?? null,
                'role'    => $request->role ?? null,
                'error'   => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while updating user role.',
            ], 500);
        }
    }
}
