<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class DeliveryPersonnel extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'company',
        'vehicle_type',
        'vehicle_number',
        'phone',
        'face_descriptor',
        'ic_number',
        'photo',
        'status',
    ];

    public function logs()
    {
        return $this->hasMany(DeliveryLog::class);
    }
}
