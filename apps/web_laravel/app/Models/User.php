<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $attributes = ['mfa_version' => 0];

    protected $fillable = ['name', 'email', 'password', 'tenant_id', 'role'];

    protected $hidden = ['password', 'remember_token', 'mfa_secret', 'mfa_recovery_codes', 'mfa_last_step', 'mfa_version'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'mfa_secret' => 'encrypted', 'mfa_recovery_codes' => 'encrypted:array', 'mfa_confirmed_at' => 'immutable_datetime', 'mfa_last_step' => 'integer', 'mfa_version' => 'integer'];
    }
}
