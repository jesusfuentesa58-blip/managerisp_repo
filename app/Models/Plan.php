<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'jurisdiction_id', // Nueva relación
        'name', 'description', 'price', 
        'pppoe_profile_name', 'upload_speed', 'download_speed',
        'tax_type', 'discount_value', 'discount_duration_months', 'is_active'
    ];

    public function jurisdiction(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Jurisdiction::class);
    }

    public function routers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Router::class);
    }

    /**
     * Calcula la velocidad en formato MikroTik (ej: 10M/50M)
     */
    public function getMikrotikRateAttribute(): string
    {
        return "{$this->upload_speed}/{$this->download_speed}";
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class);
    }
}