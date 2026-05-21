<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'institution_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // =========================================================================
    // JWTSubject contract
    // =========================================================================

    /**
     * El identificador que va en el "sub" del JWT (típicamente el ID).
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Claims adicionales que se incluyen en el JWT.
     * Esto es lo que otros microservicios leerán para autorizar peticiones
     * sin tener que llamar de vuelta al servicio de Identity en cada request.
     */
    public function getJWTCustomClaims()
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'institution_id' => $this->institution_id,
        ];
    }
}
