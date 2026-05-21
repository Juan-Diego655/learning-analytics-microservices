<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * En el API Gateway el User NO se persiste en la DB local.
 * Solo existe como representación en memoria del usuario decodificado del JWT
 * que emitió Identity & Access. Por eso no tiene table ni fillable de DB.
 */
class User extends Authenticatable implements JWTSubject
{
    /**
     * El token tiene 'sub' = user_id. Lo retornamos tal cual.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * No emitimos tokens desde el Gateway, así que esto está vacío.
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
