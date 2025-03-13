<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

/**
 * @OA\Schema(
 *     schema="User",
 *     required={"name", "email", "password"},
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="johndoe@example.com"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="scopes",
 *         type="array",
 *         @OA\Items(type="string", enum={"admin", "user", "desarrollo", "produccion"}),
 *         example={"user", "desarrollo"}
 *     )
 * )
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'email',
        'password',
        'scopes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
        // Mutator para manejar scopes
    public function setScopesAttribute($value)
    {
        $this->attributes['scopes'] = json_encode($value);
    }

    // Accessor para obtener scopes
    public function getScopesAttribute($value)
    {
        return json_decode($value, true);
    }

    // Validaci�n de scopes
    /*
    public function hasScopes(array $requiredScopes)
    {
        $userScopes = $this->scopes ?? [];
        return count(array_intersect($requiredScopes, $userScopes)) === count($requiredScopes);
    }
    */
    // App/Models/User.php

    public function hasScopes(array $requiredScopes): bool
    {
        $userScopes = $this->scopes ?? [];

        // Expandir los scopes que contengan |
        $expandedScopes = [];
        foreach ($requiredScopes as $scope) {
            $expandedScopes = array_merge(
                $expandedScopes,
                explode('|', $scope)
            );
        }

        // Verificar si el usuario tiene al menos uno de los scopes requeridos
        foreach ($userScopes as $userScope) {
            if (in_array($userScope, $expandedScopes)) {
                return true;
            }
        }

        return false;
    }

}
