<?php

namespace App\Models;

use Laravel\Passport\Client as PassportClient;

/**
 * @OA\Schema(
 *     schema="Client",
 *     required={"name"},
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="user_id", type="integer", format="int64", nullable=true, example=1),
 *     @OA\Property(property="name", type="string", example="API Client"),
 *     @OA\Property(property="secret", type="string", example="abcdef123456"),
 *     @OA\Property(
 *         property="environments",
 *         type="array",
 *         @OA\Items(type="string", enum={"desarrollo", "produccion"}),
 *         example={"desarrollo", "produccion"}
 *     ),
 *     @OA\Property(property="personal_access_client", type="boolean", example=false),
 *     @OA\Property(property="password_client", type="boolean", example=true),
 *     @OA\Property(property="revoked", type="boolean", example=false),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Client extends PassportClient
{
    protected $fillable = [
        'user_id', 
        'name', 
        'secret', 
        'redirect', 
        'personal_access_client', 
        'password_client', 
        'revoked',
        'environments'
    ];

    // Mutator para manejar ambientes
    public function setEnvironmentsAttribute($value)
    {
        $this->attributes['environments'] = json_encode($value);
    }

    // Accessor para obtener ambientes
    public function getEnvironmentsAttribute($value)
    {
        return json_decode($value, true);
    }
}
