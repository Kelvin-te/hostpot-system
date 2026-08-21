<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RadiusSession extends Model
{
    use HasFactory;

    protected $connection = 'wingufi_core';

    protected $fillable = [
        'tenant_id',
        'nas_id',
        'client_id',
        'username',
        'acct_session_id',
        'client_mac',
        'client_ip',
        'framed_ip',
        'start_time',
        'last_update_time',
        'stop_time',
        'session_time',
        'input_octets',
        'output_octets',
        'input_packets',
        'output_packets',
        'terminate_cause',
        'status',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'last_update_time' => 'datetime',
        'stop_time' => 'datetime',
        'status' => 'string',
        'input_octets' => 'integer',
        'output_octets' => 'integer',
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
