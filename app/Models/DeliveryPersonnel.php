<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;

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
        'ic_number'       => 'encrypted',
    ];

    protected function faceDescriptor(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? json_decode(Crypt::decryptString($value), true) : null,
            set: fn ($value) => $value ? Crypt::encryptString(is_array($value) ? json_encode($value) : $value) : null,
        );
    }

    public function logs()
    {
        return $this->hasMany(DeliveryLog::class);
    }

    public function runs()
    {
        return $this->hasMany(DeliveryRun::class, 'delivery_personnel_id');
    }
}
