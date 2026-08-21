<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NetworkAuthorization extends Model
{
    use HasFactory;

    protected $connection = 'wingufi_core';

    protected $fillable = [
        'tenant_id',
        'client_id',
        'package_id',
        'source_type',
        'source_id',
        'username',
        'status',
        'starts_at',
        'expires_at',
        'session_timeout',
        'download_speed',
        'upload_speed',
        'data_limit_bytes',
        'data_used_bytes',
        'simultaneous_sessions',
        'external_id',
        'external_type',
        'source_system',
        'revoked_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'status' => 'string',
        'data_limit_bytes' => 'integer',
        'data_used_bytes' => 'integer',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function client()
    {
        return $this->belongsTo(NetworkClient::class);
    }

    public function package()
    {
        return $this->belongsTo(NetworkPackage::class);
    }
}
