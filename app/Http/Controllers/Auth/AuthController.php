<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Jobs\UpdateLastLoginJob;

use App\Models\User;
use App\Mail\OTPMail;
use Mail;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6|confirmed',
            ], [
                'email.unique' => 'This email is already registered. Please use another email.',
            ]);

            $user = User::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => Hash::make($request->password)
            ]);

            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'user'  => $user->only(['id','name','email','role','is_active','created_at']),
                'token' => $token,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    // Old Login
    // public function login(Request $request)
    // {

    //     // 1) Validate (stronger)

    //     $credentials = $request->validate([

    //         'email' => ['required', 'email:rfc,dns', 'max:255'],

    //         'password' => ['required', 'string', 'min:8', 'max:72'],

    //         'device_name' => ['nullable', 'string', 'max:64'], // token name

    //         'remember' => ['nullable', 'boolean'],

    //     ]);


    //     // Normalize email

    //     $email = Str::lower(trim($credentials['email']));


    //     // 2) Rate limit keys (email + ip)

    //     $emailKey = 'login:email:' . $email;

    //     $ipKey    = 'login:ip:' . $request->ip();


    //     // 3) Check limits

    //     if (

    //         RateLimiter::tooManyAttempts($emailKey, 3) ||

    //         RateLimiter::tooManyAttempts($ipKey, 20)

    //     ) {

    //         return response()->json([

    //             'message' => "Too many login attempts. Please try again later.",

    //         ], 429);

    //     }


    //     // 4) Find user (no info leak)

    //     $user = User::where('email', $email)->first();


    //     // If user invalid OR password invalid → hit limit + generic error

    //     if (! $user || ! Hash::check($credentials['password'], $user->password)) {

    //         RateLimiter::hit($emailKey, 60);

    //         RateLimiter::hit($ipKey, 60);


    //         return response()->json([

    //             'message' => "Invalid login credentials.",

    //         ], 401);

    //     }


    //     // Optional: block/inactive check (if you have this column)

    //     if ($user->is_active === 0) {

    //         return response()->json([

    //             'message' => "Your account is disabled.",

    //         ], 403);

    //     }


    //     // 5) Successful login → clear limits

    //     RateLimiter::clear($emailKey);

    //     RateLimiter::clear($ipKey);


    //     // Optional: update last login data (add columns if needed)

    //     // $user->forceFill([

    //     //     'last_login_at' => now(),

    //     //     'last_login_ip' => $request->ip(),

    //     // ])->save();


    //     $remember = (bool)($credentials['remember'] ?? false);

    //     $user->setRememberToken($remember ? Str::random(60) : null);

    //     $user->saveQuietly();


    //     UpdateLastLoginJob::dispatch($user->id, $request->ip());


    //     $remember = (bool)($credentials['remember'] ?? false);

    //     $user->setRememberToken($remember ? Str::random(60) : null);

    //     $user->saveQuietly(); // avoid events & slow triggers


    //     // Optional: revoke old tokens (single-device login)

    //     // $user->tokens()->delete();



    //     // 6) Create token with abilities

    //     // $deviceName = $credentials['device_name'] ?? 'api-token';

    //     $deviceName = $request->userAgent() ?? 'unknown-device';

    //     $token = $user->createToken($deviceName, ['*'])->plainTextToken;


    //     return response()->json([

    //         'message' => 'Login successful.',

    //         'user' => $user,

    //         'token' => $token,

    //     ], 200);

    // }

    public function login(Request $request)
    {
        // 1) Validate (stronger)
        $validated = $request->validate([
            'email'       => ['required', 'string', 'email:rfc,dns', 'max:255'],
            'password'    => ['required', 'string', 'min:8'],
            'remember'    => ['nullable', 'boolean'],
        ]);

        // Normalize email
        $email = Str::lower(trim($validated['email']));

        // 2) Rate limit keys (email + ip)
        $emailKey = 'login:email:' . sha1($email);
        $ipKey    = 'login:ip:' . $request->ip();

        $emailMaxAttempts = 5;
        $ipMaxAttempts    = 30;

        // 3) Check limits
        if (
            RateLimiter::tooManyAttempts($emailKey, $emailMaxAttempts) ||
            RateLimiter::tooManyAttempts($ipKey, $ipMaxAttempts)
        ) {
            $seconds = max(
                RateLimiter::availableIn($emailKey),
                RateLimiter::availableIn($ipKey)
            );

            return response()->json([
                'success' => false,
                'message' => 'Too many login attempts. Please try again later.',
                'retry_after_seconds' => $seconds,
            ], 429);
        }

        // 4) Find user (no info leak)
        $user = User::where('email', $email)->first();

        // If user invalid OR password invalid → hit limit + generic error
        if (
            ! $user ||
            ! Hash::check($validated['password'], $user->password)
        ) {

            RateLimiter::hit($emailKey, 60); // 1 min
            RateLimiter::hit($ipKey, 300);

            return response()->json([
                'success' => false,
                'message' => 'Invalid login credentials.',
            ], 401);
        }

        // Optional: block/inactive check (if you have this column)
        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is disabled.',
            ], 403);
        }

        // Optional: Email Verification Check
        // if (is_null($user->email_verified_at)) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Please verify your email first.',
        //     ], 403);
        // }

        // 5) Successful login → clear limits
        RateLimiter::clear($emailKey);
        RateLimiter::clear($ipKey);

        $user->setRememberToken(($validated['remember'] ?? false) ? Str::random(60) : null);
        $user->saveQuietly();

        // last login at and login ip
        UpdateLastLoginJob::dispatch($user->id, $request->ip());

        // Optional: revoke old tokens (single-device login)
        // $user->tokens()->delete();


        // 6) Create token with abilities
        $deviceName = Str::limit($request->userAgent() ?? 'unknown-device', 100);
        $token = $user->createToken($deviceName,['*'],now()->addDays(30))->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'token_type' => 'Bearer',
            'token' => $token,
            'user' => [
                'id'       => $user->id,
                'name'     => $user->name,
                'email'    => $user->email,
                'username' => $user->username,
                'phone'    => $user->phone,
                'image'    => $user->image,
                'role'     => $user->role,
            ],
            
        ], 200);
    }

    public function findAccount(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        $otp = rand(100000, 999999);
        $user->otp = $otp;
        $user->otp_expires_at = Carbon::now()->addMinutes(10);
        $user->save();

        Mail::to($user->email)->send(new OTPMail($otp, $user));

        session(['reset_email' => $user->email]);

        return response()->json([
            'message' => 'Account found.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ], 200);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user->otp !== $request->otp) {
            return response()->json(['message' => 'Invalid OTP.'], 400);
        }

        if (Carbon::now()->greaterThan($user->otp_expires_at)) {
            return response()->json(['message' => 'OTP has expired.'], 400);
        }

        return response()->json([
            'message' => 'OTP verified. You can now reset your password.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]
        ], 200);
    }

    public function resetPassword(Request $request)
    {
        // Validate request
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Find user
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $user->password = Hash::make($request->password);

        $user->otp = null;
        $user->otp_expires_at = null;
        $user->save();

        return response()->json([
            'message' => 'Password reset successful. You can now log in with your new password.'
        ], 200);
    }

    public function getAdminUser(){
        try {
            $users = User::all();

            return response()->json([
                'success' => true,
                'message' => 'Fetched all admin users',
                'data' => $users,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    // Logout Section
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Logged out successfully (current device).'
        ], 200);
    }

    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json([
            'message' => 'Logged out from all devices.'
        ], 200);
    }

    public function logoutDevice(Request $request)
    {
        $request->validate([
            'token_id' => 'required|exists:personal_access_tokens,id'
        ]);
        $request->user()->tokens()
            ->where('id', $request->token_id)
            ->delete();

        return response()->json([
            'message' => 'Device logged out successfully.'
        ]);
    }

    public function devices(Request $request)
    {
        $tokens = $request->user()->tokens->map(function ($token) {
            return [
                'id' => $token->id,
                'device' => $token->name,
                'last_used_at' => $token->last_used_at,
                'created_at' => $token->created_at,
            ];
        });

        return response()->json([
            'devices' => $tokens
        ]);
    }
}
