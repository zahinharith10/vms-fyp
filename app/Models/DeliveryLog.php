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
        'approved_at',
    ];

    protected $casts = [
        'entry_time' => 'datetime',
        'exit_time' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected $appends = ['total_duration_minutes', 'is_expired'];

    public function getIsExpiredAttribute()
    {
        if ($this->getRawOriginal('status') !== 'Approved') {
            return false;
        }
        $approvalTime = $this->approved_at ?? $this->created_at;
        return $approvalTime && $approvalTime->addHours(24)->isPast();
    }

    public function getStatusAttribute($value)
    {
        if ($value === 'Approved' && $this->is_expired) {
            return 'Expired';
        }
        return $value;
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereIn('status', ['Pending', 'Checked In', 'Temporarily Out'])
              ->orWhere(function ($sub) {
                  $sub->where('status', 'Approved')
                      ->where(function ($timeCheck) {
                          $timeCheck->where(function ($sub2) {
                              $sub2->whereNotNull('approved_at')
                                   ->where('approved_at', '>=', now()->subHours(24));
                          })->orWhere(function ($sub2) {
                              $sub2->whereNull('approved_at')
                                   ->where('created_at', '>=', now()->subHours(24));
                          });
                      });
              });
        });
    }

    public function getTotalDurationMinutesAttribute()
    {
        if ($this->entry_time) {
            $end = $this->exit_time ?? ($this->status === 'Checked In' ? now() : $this->entry_time);
            return (int) round($this->entry_time->diffInMinutes($end));
        }
        return null;
    }

    public function personnel()
    {
        return $this->belongsTo(DeliveryPersonnel::class, 'delivery_personnel_id');
    }

    public function run()
    {
        return $this->belongsTo(DeliveryRun::class, 'delivery_run_id');
    }
}
