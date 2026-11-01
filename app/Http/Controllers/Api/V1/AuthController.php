<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = User::forceCreate([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'user',
        ]);

        $token = $user->createToken('mobile')->plainTextToken;

        return $this->respond([
            'user' => UserResource::make($user)->resolve(),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Akun berhasil dibuat.', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return $this->respond(null, 'Email atau password tidak sesuai.', 401);
        }

        if (! $user->isUser()) {
            return $this->respond(null, 'Akun ini tidak diizinkan menggunakan API mobile.', 403);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return $this->respond([
            'user' => UserResource::make($user)->resolve(),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Login berhasil.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->respond(null, 'Token aktif berhasil dicabut.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->respond(
            UserResource::make($request->user())->resolve(),
            'Profil berhasil diambil.',
        );
    }
}
