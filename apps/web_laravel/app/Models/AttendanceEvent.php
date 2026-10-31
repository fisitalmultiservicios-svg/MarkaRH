<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class AttendanceEvent extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['id', 'tenant_id', 'branch_id', 'device_id', 'employee_id', 'sequence', 'action', 'local_occurred_at', 'occurred_at', 'received_at', 'payload_hash'];

    protected $hidden = ['payload_hash'];

    protected function casts(): array
    {
        return ['occurred_at' => 'immutable_datetime', 'received_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Los eventos originales no se modifican.'));
        static::deleting(fn () => throw new LogicException('Los eventos originales no se eliminan.'));
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
