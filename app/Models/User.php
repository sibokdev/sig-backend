<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable; // Importante para Auth
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'iduserId';
    public $incrementing = true;
    public $timestamps = true;

    // Campos que se pueden asignar en masa
    protected $fillable = [
        'username',
        'email',
        'password',
        'role'
    ];

    // Ocultar el password en respuestas JSON
    protected $hidden = [
        'password',
    ];

    /**
     * Métodos requeridos por JWTSubject
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}