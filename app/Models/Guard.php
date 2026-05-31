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
        'password'  => 'hashed',
        'ic_number' => 'encrypted',
    ];

    public function getShiftAttribute($value)
    {
        if (empty($value)) {
            return [];
        }

        // If it starts with [ it is JSON
        if (str_starts_with($value, '[')) {
            return json_decode($value, true) ?: [$value];
        }

        // Otherwise it could be comma-separated or a single string
        return array_map('trim', explode(',', $value));
    }

    public function setShiftAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['shift'] = json_encode(array_values(array_filter($value)));
        } else {
            $this->attributes['shift'] = $value;
        }
    }
}
