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
        'role',
        'cve_ent',
        'cve_mun',
        'national_admin_id',
        'cve_seccion',
        'semaforo_config',
    ];

    // Ocultar el password en respuestas JSON
    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'semaforo_config' => 'array',
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

    public function assignedLayers()
    {
        return $this->belongsToMany(Layer::class, 'layer_assignments', 'user_id', 'layer_id');
    }

    public function nationalAdmin()
    {
        return $this->belongsTo(User::class, 'national_admin_id', 'iduserId');
    }

    public function subordinates()
    {
        return $this->hasMany(User::class, 'national_admin_id', 'iduserId');
    }
}