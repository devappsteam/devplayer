<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Core\Traits\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(): JsonResponse
    {
        $validated = request()->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = auth('api')->login($user);

        return $this->successResponse([
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ], 'User registered successfully');
    }

    public function login(): JsonResponse
    {
        $validated = request()->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!$token = auth('api')->attempt($validated)) {
            return $this->errorResponse('Invalid credentials', 401);
        }

        return $this->successResponse([
            'user' => auth('api')->user(),
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ], 'Login successful');
    }

    public function me(): JsonResponse
    {
        return $this->successResponse(auth('api')->user());
    }

    public function refresh(): JsonResponse
    {
        return $this->successResponse([
            'token' => auth('api')->refresh(),
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ], 'Token refreshed');
    }

    public function logout(): JsonResponse
    {
        auth('api')->logout();
        return $this->successResponse(null, 'Logout successful');
    }
}
