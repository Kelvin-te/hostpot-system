<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RadiusAccounting extends Model
{
    use HasFactory;

    protected $connection = 'wingufi_core';

    protected $table = 'radius_accounting';

    protected $fillable = [
        'tenant_id',
        'nas_id',
        'client_id',
        'username',
        'acct_session_id',
        'acct_status_type',
        'session_time',
        'input_octets',
        'output_octets',
        'input_packets',
        'output_packets',
        'client_ip',
        'client_mac',
        'framed_ip',
        'event_time',
        'terminate_cause',
        'raw_attributes',
    ];

    protected $casts = [
        'event_time' => 'datetime',
        'acct_status_type' => 'string',
        'raw_attributes' => 'array',
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
