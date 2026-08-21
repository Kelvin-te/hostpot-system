<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NetworkClient extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'wingufi_core';

    protected $fillable = [
        'tenant_id',
        'uuid',
        'username',
        'display_name',
        'email',
        'phone',
        'status',
        'password_hash',
        'password_type',
        'mac_address',
        'static_ip',
        'notes',
        'external_id',
        'external_type',
        'source_system',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function authorizations()
    {
        return $this->hasMany(NetworkAuthorization::class, 'client_id');
    }

    public function sessions()
    {
        return $this->hasMany(RadiusSession::class, 'client_id');
    }

    public function accounting()
    {
        return $this->hasMany(RadiusAccounting::class, 'client_id');
    }

    public function authLogs()
    {
        return $this->hasMany(RadiusAuthLog::class, 'client_id');
    }
}
