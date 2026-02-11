<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tradie extends Model
{
    protected $fillable= [
        'name',
        'personal_phone',
        'virtual_phone',
    ];

    public function calls()
    {
        return $this->hasMany(Call::class);
    }

    public function jobs()
    {
        return $this->hasMany(Job::class);
    }
}
