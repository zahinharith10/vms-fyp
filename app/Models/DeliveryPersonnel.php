<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class DeliveryPersonnel extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'delivery_personnels';

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

    protected $casts = [
        'face_descriptor' => 'array',
    ];

    public function logs()
    {
        return $this->hasMany(DeliveryLog::class);
    }

    public function runs()
    {
        return $this->hasMany(DeliveryRun::class, 'delivery_personnel_id');
    }
}
