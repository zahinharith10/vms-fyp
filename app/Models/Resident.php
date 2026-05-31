<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class Resident extends Authenticatable implements CanResetPasswordContract
{
    use HasFactory, Notifiable, CanResetPassword;

    protected $fillable = [
        'house_unit_id',
        'name',
        'phone',
        'email',
        'ic_number',
        'type',
        'status',
        'password',
        'auto_approve_deliveries',
        'verification_token',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'auto_approve_deliveries' => 'boolean',
    ];

    public function houseUnit()
    {
        return $this->belongsTo(HouseUnit::class);
    }
}
