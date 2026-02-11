<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Call extends Model
{
    protected $fillable = [
        'tradie_id',
        'from_number',
        'to_number',
        'status',
        'recording_url',
        'transcript'
    ];

    public function tradie()
    {
        return $this->belongsTo(Tradie::class);
    }

    public function job()
    {
        return $this->hasOne(Job::class);
    }
}
