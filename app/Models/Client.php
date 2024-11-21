<?php

namespace App\Models;

use Laravel\Passport\Client as PassportClient;


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
