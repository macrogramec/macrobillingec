<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;


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

    // Validación de scopes
    public function hasScopes(array $requiredScopes)
    {
        $userScopes = $this->scopes ?? [];
        return count(array_intersect($requiredScopes, $userScopes)) === count($requiredScopes);
    }
    
}
