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
    ];

    public function visitor()
    {
        return $this->belongsTo(Visitor::class);
    }
}
