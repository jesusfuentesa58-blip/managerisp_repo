<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RouterProvision extends Model
{
    protected $fillable = ['router_id', 'method', 'status', 'message'];
}
