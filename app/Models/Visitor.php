<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;

class Visitor extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'phone', 'vehicle_number', 'ic_number', 'photo', 'face_descriptor'];

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

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }
}
