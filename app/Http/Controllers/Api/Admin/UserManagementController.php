<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    // ================= GET ALL USERS =================
    public function index(Request $request)
    {
        return User::latest()->paginate(10);
    }

    // ================= GET SINGLE USER =================
    public function show($id)
    {
        return User::findOrFail($id);
    }

    // ================= UPDATE USER =================
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'nullable|min:6',
            'role' => 'sometimes|in:user,admin'
        ]);

        $user = User::findOrFail($id);

        $data = $request->only(['name', 'email', 'role']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user
        ]);
    }

    // ================= GET USER ORDERS =================
    public function orders($id)
    {
        $user = User::findOrFail($id);

        return $user->orders()
            ->with(['cart.items.ebook'])
            ->latest()
            ->paginate(10);
    }
}
