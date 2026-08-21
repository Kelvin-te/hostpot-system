<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class TenantCredential extends Model
{
    use HasFactory;

    protected $connection = 'wingufi_core';

    protected $fillable = [
        'tenant_id',
        'name',
        'client_id',
        'client_secret_hash',
        'status',
        'last_used_at',
        'expires_at',
        'revoked_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'status' => 'string',
    ];

    protected $hidden = [
        'client_secret_hash',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->revoked_at !== null) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function verifyToken(string $secret): bool
    {
        return Hash::check($secret, $this->client_secret_hash);
    }
}
