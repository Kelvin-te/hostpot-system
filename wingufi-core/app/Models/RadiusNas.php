<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RadiusNas extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'wingufi_core';

    protected $fillable = [
        'tenant_id',
        'name',
        'nasname',
        'shortname',
        'type',
        'identifier',
        'description',
        'status',
        'radius_secret_encrypted',
        'auth_port',
        'acct_port',
        'coa_port',
        'management_ip',
        'external_id',
        'source_system',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function sessions()
    {
        return $this->hasMany(RadiusSession::class, 'nas_id');
    }

    public function accounting()
    {
        return $this->hasMany(RadiusAccounting::class, 'nas_id');
    }

    public function authLogs()
    {
        return $this->hasMany(RadiusAuthLog::class, 'nas_id');
    }
}
