<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_personnel_id',
        'delivery_run_id',
        'status',
        'entry_time',
        'exit_time',
        'destination',
        'host_name',
        'approved_by',
    ];

    protected $casts = [
        'entry_time' => 'datetime',
        'exit_time' => 'datetime',
    ];

    public function personnel()
    {
        return $this->belongsTo(DeliveryPersonnel::class, 'delivery_personnel_id');
    }

    public function run()
    {
        return $this->belongsTo(DeliveryRun::class, 'delivery_run_id');
    }
}
