<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class UserService
{
    public function createAdmin(array $data): User
    {
        return DB::transaction(function () use ($data) {
            return User::create([
                'uuid' => (string) Str::uuid(),
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'scopes' => ['admin', 'user', 'desarrollo', 'produccion']
            ]);
        });
    }

    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            return User::create([
                'uuid' => (string) Str::uuid(),
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'scopes' => $data['scopes']
            ]);
        });
    }

    public function checkExistingAdmin(string $email): bool
    {
        return User::where('email', $email)->exists();
    }
}
