<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Installation extends Model
{
    protected $fillable = [
        'service_request_id', 'technician_id', 'status', 
        'scheduled_at', 'event_log', 'signal_dbm', 'onu_serial', 'completed_at',
        'router_id', 'pppoe_user', 'zone_id', 'pppoe_password', 'remote_address'
    ];

    // Importante: Decirle a Laravel que event_log es un Array/JSON
    protected $casts = [
        'event_log' => 'array',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function router(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}