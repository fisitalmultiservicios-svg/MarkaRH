<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['tenant_id', 'user_id', 'action', 'subject_id', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime'];
    }

    public static function record(?User $user, string $action, int|string|null $subject = null, ?int $tenant = null): void
    {
        static::create(['tenant_id' => $tenant ?? $user?->tenant_id, 'user_id' => $user?->id, 'action' => $action, 'subject_id' => $subject, 'created_at' => now()]);
    }
}
