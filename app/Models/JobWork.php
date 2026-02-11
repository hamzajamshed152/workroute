<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobWork extends Model
{
    protected $fillable = [
        'tradie_id',
        'call_id',
        'customer_name',
        'customer_phone',
        'service_type',
        'location',
        'urgency',
        'raw_transcript'
    ];
}
