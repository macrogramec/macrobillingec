<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use App\Services\UserService;
use App\Http\Requests\CreateFirstAdminRequest;
use App\Http\Requests\CreateUserRequest;


/**
 * @OA\Tag(
 *     name="Administracion",
 *     description="Endpoints para administracion intera "
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
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/create-user",
     *     summary="Crear un nuevo usuario",
     *     description="Crea un nuevo usuario en el sistema con los roles especificados",
     *     operationId="createUser",
     *     tags={"Administracion"},
     *     security={{"oauth2": {"admin"}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation","scopes"},
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 example="Juan Pérez",
     *                 description="Nombre completo del usuario"
     *             ),
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 example="juan@ejemplo.com",
     *                 description="Correo electrónico único del usuario"
     *             ),
     *             @OA\Property(
     *                 property="password",
     *                 type="string",
     *                 format="password",
     *                 example="password123",
     *                 description="Contraseña del usuario (mínimo 8 caracteres)"
     *             ),
     *             @OA\Property(
     *                 property="password_confirmation",
     *                 type="string",
     *                 format="password",
     *                 example="password123",
     *                 description="Confirmación de la contraseña"
     *             ),
     *             @OA\Property(
     *                 property="scopes",
     *                 type="array",
     *                 description="Roles asignados al usuario",
     *                 @OA\Items(
     *                     type="string",
     *                     enum={"admin", "user", "desarrollo", "produccion"},
     *                     example="user"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Usuario creado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 example="success"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Usuario creado exitosamente"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Juan Pérez"),
     *                 @OA\Property(property="email", type="string", example="juan@ejemplo.com"),
     *                 @OA\Property(
     *                     property="scopes",
     *                     type="array",
     *                     @OA\Items(type="string", example="user")
     *                 ),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Los datos proporcionados no son válidos"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="El correo electrónico ya está registrado")),
     *                 @OA\Property(property="password", type="array", @OA\Items(type="string", example="La contraseña debe tener al menos 8 caracteres"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Error al crear el usuario")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No tienes permisos para crear usuarios")
     *         )
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
    /**
     * @OA\Get(
     *     path="/user/info",
     *     summary="Obtener información del usuario autenticado",
     *     description="Retorna la información completa del usuario que está actualmente autenticado",
     *     operationId="getInfoUser",
     *     tags={"Administracion"},
     *     security={{"oauth2": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Información del usuario recuperada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Juan Pérez"),
     *             @OA\Property(property="email", type="string", format="email", example="juan@ejemplo.com"),
     *             @OA\Property(property="email_verified_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
     *             @OA\Property(property="scopes", type="array",
     *                 @OA\Items(type="string", example={"admin", "user", "desarrollo", "produccion"})
     *             ),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Prohibido",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No tienes permisos para acceder")
     *         )
     *     )
     * )
     */
    public function getInfoUser()
    {
        return auth()->user();
    }


}
