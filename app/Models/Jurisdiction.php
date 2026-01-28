<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Jurisdiction extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'name',
        'code',
        'department',
        'city',
        'latitude',
        'longitude',
        'billing_day',
        'due_day',
        'suspension_day',
        'suspend_after_invoices',
        'auto_generate_invoices',
        'auto_send_invoices',
        'auto_suspend_services',
        'auto_send_sms',
        'auto_send_email',
        'is_active',
    ];

    public function routers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Router::class);
    }

    public function plans()
    {
        return $this->hasMany(Plan::class);
    }

    
}

