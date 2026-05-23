<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'visitor_id',
        'unit_number',
        'purpose',
        'status',
        'check_in_time',
        'check_out_time',
        'qr_code_token',
        'parking_lot_number',
        'first_check_in_time',
        'first_check_out_time',
        'second_check_in_time',
        'second_check_out_time',
    ];

    protected $casts = [
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'first_check_in_time' => 'datetime',
        'first_check_out_time' => 'datetime',
        'second_check_in_time' => 'datetime',
        'second_check_out_time' => 'datetime',
    ];

    public function visitor()
    {
        return $this->belongsTo(Visitor::class);
    }

    /**
     * All individual check-in/check-out sessions for this visit.
     * Supports unlimited temporary leaves (Approach A).
     */
    public function sessions()
    {
        return $this->hasMany(VisitSession::class)->orderBy('check_in_time');
    }
}
