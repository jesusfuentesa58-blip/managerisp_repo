<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Router extends Model
{
    use SoftDeletes;
    
    public const STATUS_UNKNOWN = 'unknown';
    public const STATUS_ONLINE = 'online';
    public const STATUS_DEGRADED = 'degraded';
    public const STATUS_OFFLINE = 'offline';
    public const STATUS_AUTH_ERROR = 'auth_error'; 

    protected $fillable = [
        'jurisdiction_id',
        'name',
        'host',
        'port',
        'username',
        'password',
        'api_type',
        'suspension_method',
        'is_active',
        'provisioned_address_list',
        'provisioned_at',
        'host','port','username','password','is_active','suspension_method',
        // provision flags
        'provisioned_address_list','provisioned_at',
        // nuevo
        'status','last_checked_at',
        // NUEVO: IP de interfaz LAN
        'lan_ip',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'provisioned_address_list' => 'boolean',
        'provisioned_at' => 'datetime',
        'last_checked_at' => 'datetime',
    ];
    
    public function jurisdiction(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Jurisdiction::class);
    }

    public function provisions(): HasMany
    {
        return $this->hasMany(\App\Models\RouterProvision::class);
    }

    public function zones(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Zone::class);
    }

    public function plans(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Plan::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }
}
