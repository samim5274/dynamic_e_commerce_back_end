<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

use App\Models\User;

class CustomerController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'              => ['required','string','max:255'],
            'phone'             => ['nullable','string','max:30', Rule::unique('users','phone')->ignore($user->id)],
            'dob'               => ['nullable','date'],
            'gender'            => ['nullable','in:male,female,other'],
            'blood_group'       => ['nullable','string','max:10'],
            'present_address'   => ['nullable','string','max:500'],
            'permanent_address' => ['nullable','string','max:500'],
            'national_id'       => ['nullable','string','max:50'],
            'religion'          => ['nullable','string','max:50'],
            'photo'             => ['nullable','image','max:2048'],
        ]);

        if ($request->hasFile('photo')) {
            // old photo delete
            if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                Storage::disk('public')->delete($user->photo);
            }

            $path = $request->file('photo')->store('users', 'public');
            $data['photo'] = $path;
        }

        // profile completed simple rule
        $data['is_profile_completed'] = !empty(trim($data['name'] ?? $user->name ?? '')) && !empty(trim($data['phone'] ?? $user->phone ?? ''));

        $user->update($data);

        $fresh = $user->fresh();
        $fresh->photo_url = $fresh->photo ? asset('storage/'.$fresh->photo) : null;

        return response()->json([
            'success' => true,
            'data' => $fresh,
        ]);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated.',
        ]);
    }

    public function treeUser(){
        $root = User::with(['leftChild', 'rightChild'])
                ->where('role', 'super_admin')
                ->first();

        if(!$root){
            return response()->json([
                'success' => false,
                'message' => 'No root user found',
                'data' => null
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tree fetched successfully',
            'data' => $root
        ]);
    }

    public function getUsers(Request $request){
        try {
            $authUser = $request->user();
            $users = User::with(['referrer', 'leftChild', 'rightChild'])
                    ->where('is_match', 0)
                    ->where('refer_id', $authUser->id)
                    ->latest()
                    ->get();

            return response()->json([
                'success' => true,
                'message' => 'Fetched all admin users',
                'data' => $users,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function createUser(Request $request)
    {
        // Validate input
        $data = $request->validate([
            'name'              => ['required','string','max:255'],
            'email'             => ['required','email','max:255','unique:users,email'],
            'phone'             => ['nullable','string','max:30','unique:users,phone'],
            'dob'               => ['nullable','date'],
            'gender'            => ['nullable','in:male,female,other'],
            'blood_group'       => ['nullable','string','max:10'],
            'present_address'   => ['nullable','string','max:500'],
            'permanent_address' => ['nullable','string','max:500'],
            'national_id'       => ['nullable','string','max:50'],
            'religion'          => ['nullable','string','max:50'],
            'photo'             => ['nullable','image','max:2048'],
            'refer_id'          => ['required','string'],

            'root_user_id'      => ['required','exists:users,id'],
            'position'          => ['required','in:left,right'],
        ]);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('users', 'public');
            $data['photo'] = $path;
        }

        // Check reference user
        if (!empty($data['refer_id'])) {
            $referUser = User::where('user_id', $data['refer_id'])->first();
            if (!$referUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reference not found. Please try again.',
                ], 422);
            }

            $data['refer_id'] = $referUser->id;
        }

        // Profile completed simple rule
        $data['is_profile_completed'] = !empty(trim($data['name'])) && !empty(trim($data['phone'] ?? ''));


        // Create new user
        $user = User::create($data);

        // Add photo_url for frontend
        $user->photo_url = $user->photo ? asset('storage/'.$user->photo) : null;









        // After create user then assign in tree
        $userId = $user->id;
        $userRootId = $data['root_user_id'];
        $position = $data['position'];

        // prevent self assignment
        if ($userId == $userRootId) {
            return response()->json([
                'success' => false,
                'message' => 'User and Root User cannot be the same.',
            ], 422);
        }

        try{
            DB::transaction(function () use ($data, $userId, $userRootId) {

                $user = User::where('id', $userId)->lockForUpdate()->firstOrFail();
                $rootUser = User::where('id', $userRootId)->lockForUpdate()->firstOrFail();

                // already has parent
                if ($user->parent_id) {
                    throw new \Exception('User already has a parent.');
                }

                if ($this->isDescendant($rootUser, $user)) {
                    throw new \Exception('Circular reference detected.');
                }

                // position check
                if ($data['position'] === 'left' && $rootUser->left_child_id) {
                    throw new \Exception('Left position already occupied.');
                }
                if ($data['position'] === 'right' && $rootUser->right_child_id) {
                    throw new \Exception('Right position already occupied.');
                }

                // assign parent
                $user->parent_id = $rootUser->id;

                // assign child to root user
                if ($data['position'] == 'left') {
                    $rootUser->left_child_id = $user->id;
                } else {
                    $rootUser->right_child_id = $user->id;
                }

                // save both
                $user->save();
                $rootUser->save();

            });

            return response()->json([
                'success' => true,
                'message' => 'User assigned successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }










        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'User created successfully'
        ]);
    }

    public function getAuthUser(Request $request)
    {
        try {
            $authUser = $request->user();

            return response()->json([
                'success' => true,
                'message' => 'Fetched logged in user',
                'data' => $authUser,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getRootUsers(Request $request){
        try {
            $authUser = $request->user();

            // $users = User::whereKeyNot($authUser->id)
            //         ->where(function ($query) {
            //             $query->whereNull('left_child_id')
            //                 ->orWhereNull('right_child_id');
            //         })
            //         ->latest()
            //         ->get();

            $users = User::where('is_match', 0)->latest()->get();

            return response()->json([
                'success' => true,
                'message' => 'Fetched all admin users',
                'data' => $users,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function assignTree(Request $request)
    {
        $data = $request->validate([
            'user_id'      => ['required','exists:users,id'],
            'root_user_id' => ['required','exists:users,id'],
            'position'     => ['required','in:left,right'],
        ]);

        // prevent self assignment
        if ($data['user_id'] == $data['root_user_id']) {
            return response()->json([
                'success' => false,
                'message' => 'User and Root User cannot be the same.',
            ], 422);
        }

        try{
            DB::transaction(function () use ($data) {

                $user = User::where('id', $data['user_id'])->lockForUpdate()->firstOrFail();
                $rootUser = User::where('id', $data['root_user_id'])->lockForUpdate()->firstOrFail();

                // already has parent
                if ($user->parent_id) {
                    throw new \Exception('User already has a parent.');
                }

                if ($this->isDescendant($rootUser, $user)) {
                    throw new \Exception('Circular reference detected.');
                }

                // position check
                if ($data['position'] === 'left' && $rootUser->left_child_id) {
                    throw new \Exception('Left position already occupied.');
                }
                if ($data['position'] === 'right' && $rootUser->right_child_id) {
                    throw new \Exception('Right position already occupied.');
                }

                // assign parent
                $user->parent_id = $rootUser->id;

                // assign child to root user
                if ($data['position'] == 'left') {
                    $rootUser->left_child_id = $user->id;
                } else {
                    $rootUser->right_child_id = $user->id;
                }

                // save both
                $user->save();
                $rootUser->save();

            });

            return response()->json([
                'success' => true,
                'message' => 'User assigned successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function isDescendant($rootUser, $user)
    {
        if (!$rootUser->parent_id) return false;

        if ($rootUser->parent_id == $user->id) return true;

        $parent = User::find($rootUser->parent_id);

        return $parent ? $this->isDescendant($parent, $user) : false;
    }
}
