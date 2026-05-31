<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Visitor extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'phone', 'vehicle_number', 'ic_number', 'photo', 'face_descriptor'];

    protected $casts = [
        'ic_number'       => 'encrypted',
        'face_descriptor' => 'encrypted',
    ];

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }
}
