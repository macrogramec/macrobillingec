<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use App\Services\UserService;
use App\Http\Requests\CreateFirstAdminRequest;
use App\Http\Requests\CreateUserRequest;

class AdminController extends Controller
{
    use ApiResponse;

    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function createFirstAdmin(CreateFirstAdminRequest $request)
    {
        if ($this->userService->checkExistingAdmin($request->email)) {
            return $this->errorResponse(
                'Ya existe un usuario con este correo',
                400
            );
        }

        try {
            $user = $this->userService->createAdmin($request->validated());
            return $this->successResponse(
                $user,
                'Usuario administrador creado exitosamente',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al crear el usuario administrador',
                500
            );
        }
    }

    public function createUser(CreateUserRequest $request)
    {
        try {
            $user = $this->userService->createUser($request->validated());
            return $this->successResponse(
                $user,
                'Usuario creado exitosamente',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al crear el usuario',
                500
            );
        }
    }
}