<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    protected $fillable = [
        'customer_id', 
        'plan_id', 
        'router_id', 
        'zone_id', 
        'pppoe_user', 
        'pppoe_password', 
        'remote_address', 
        'status', 
        'installation_date',
        // Ubicación específica del servicio
        'address',
        'neighborhood',
        'coordinates'
    ];

    protected $casts = [
        'installation_date' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($service) {
            if (empty($service->pppoe_user)) {
                $service->pppoe_user = $service->customer->document_number;
            }
        });
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function plan(): BelongsTo { return $this->belongsTo(Plan::class); }
    public function router(): BelongsTo { return $this->belongsTo(Router::class); }
    public function zone(): BelongsTo { return $this->belongsTo(Zone::class); }
}