<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RadiusAuthLog extends Model
{
    use HasFactory;

    protected $connection = 'wingufi_core';

    protected $fillable = [
        'tenant_id',
        'nas_id',
        'client_id',
        'username',
        'client_ip',
        'client_mac',
        'request_type',
        'result',
        'failure_reason',
        'event_time',
        'request_id',
    ];

    protected $casts = [
        'event_time' => 'datetime',
        'result' => 'string',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function nas()
    {
        return $this->belongsTo(RadiusNas::class, 'nas_id');
    }

    public function client()
    {
        return $this->belongsTo(NetworkClient::class);
    }
}
