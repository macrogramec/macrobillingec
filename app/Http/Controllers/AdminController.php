<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use App\Services\UserService;
use App\Http\Requests\CreateFirstAdminRequest;
use App\Http\Requests\CreateUserRequest;


/**
 * @OA\Tag(
 *     name="Administración",
 *     description="API Endpoints de administración"
 * )
 */
class AdminController extends Controller
{
    use ApiResponse;

    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    /**
     * @OA\PathItem(path="/api/create-first-admin")
     * @OA\Post(
     *     path="/api/create-first-admin",
     *     operationId="createFirstAdmin",
     *     tags={"Administración"},
     *     summary="Crear primer administrador",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="password_confirmation", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Admin creado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     )
     *     @OA\Response(
     *         response=400,
     *         description="Ya existe un usuario con este correo"
     *     )
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor"
     *     )
     * )
     */
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

    /**
     * @OA\PathItem(path="/api/create-user")
     * @OA\Post(
     *     path="/api/create-user",
     *     operationId="createUser",
     *     tags={"Administración"},
     *     security={{"passport": {"admin"}}},
     *     summary="Crear nuevo usuario",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation","scopes"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="password_confirmation", type="string"),
     *             @OA\Property(property="scopes", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Usuario creado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     )
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor"
     *     )
     * )
     */
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