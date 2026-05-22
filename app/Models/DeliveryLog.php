<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_personnel_id',
        'status',
        'entry_time',
        'exit_time',
        'destination',
    ];

    public function personnel()
    {
        return $this->belongsTo(DeliveryPersonnel::class, 'delivery_personnel_id');
    }
}
