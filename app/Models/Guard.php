<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class Guard extends Authenticatable implements CanResetPasswordContract
{
    use HasFactory, Notifiable, CanResetPassword;

    protected $fillable = [
        'name',
        'employee_id',
        'ic_number',
        'phone',
        'address',
        'photo',
        'shift',
        'status',
        'email',
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];
}
