<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
    protected $table = 'companies';

    protected $fillable = [
        'name', 'nit', 'email', 'phone', 'address', 'city',
        'domain', 'website', 'logo_path', 'slogan',
        'currency_symbol', 'time_zone'
    ];

    // Helper para obtener el logo o uno por defecto
    public function getLogoUrlAttribute()
    {
        return $this->logo_path 
            ? Storage::url($this->logo_path) 
            : null;
    }
}