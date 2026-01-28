<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'name', 
        'document_type', 
        'document_number', 
        'phone', 
        'email', 
        'status',
        // Nuevos campos de ubicación principal
        'address',
        'neighborhood',
        'coordinates'
    ];

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }
}