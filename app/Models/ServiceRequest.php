<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRequest extends Model
{
    protected $fillable = [
        'customer_id', 
        'plan_id', 
        'zone_id', 
        'neighborhood', 
        'address', 
        'coordinates', 
        'notes', 
        'status', 
        'router_id', 
        'technician_id', 
        'scheduled_at'
    ];

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function plan(): BelongsTo { return $this->belongsTo(Plan::class); }
    public function router(): BelongsTo { return $this->belongsTo(Router::class); }
    public function zone(): BelongsTo { return $this->belongsTo(Zone::class); }
}