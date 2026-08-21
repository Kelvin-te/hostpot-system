<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NetworkPackage extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'wingufi_core';

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'description',
        'status',
        'download_speed',
        'upload_speed',
        'session_timeout',
        'validity_seconds',
        'data_limit_bytes',
        'simultaneous_sessions',
        'price',
        'currency',
        'external_id',
        'external_type',
        'source_system',
    ];

    protected $casts = [
        'status' => 'string',
        'price' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function authorizations()
    {
        return $this->hasMany(NetworkAuthorization::class, 'package_id');
    }
}
