<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Zone extends Model
{
    use SoftDeletes;
    
    protected $fillable = ['router_id', 'name', 'code', 'is_active'];

    // Relación con el Router
    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    // Articulación: Obtener la Cobertura (Jurisdiction) a través del Router
    public function jurisdiction(): HasOneThrough
    {
        return $this->hasOneThrough(
            Jurisdiction::class,
            Router::class,
            'id',              // Local key en Router
            'id',              // Local key en Jurisdiction
            'router_id',       // Foreign key en Zone
            'jurisdiction_id'  // Foreign key en Router
        );
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function installations()
    {
        return $this->hasMany(Installation::class);
    }
}