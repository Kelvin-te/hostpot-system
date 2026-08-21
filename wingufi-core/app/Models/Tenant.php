<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'wingufi_core';

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'code',
        'status',
        'timezone',
        'currency',
        'contact_email',
        'contact_phone',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function credentials()
    {
        return $this->hasMany(TenantCredential::class);
    }

    public function nasDevices()
    {
        return $this->hasMany(RadiusNas::class);
    }

    public function clients()
    {
        return $this->hasMany(NetworkClient::class);
    }

    public function packages()
    {
        return $this->hasMany(NetworkPackage::class);
    }

    public function authorizations()
    {
        return $this->hasMany(NetworkAuthorization::class);
    }

    public function sessions()
    {
        return $this->hasMany(RadiusSession::class);
    }

    public function accounting()
    {
        return $this->hasMany(RadiusAccounting::class);
    }

    public function authLogs()
    {
        return $this->hasMany(RadiusAuthLog::class);
    }
}
