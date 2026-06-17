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
        'host_name',
        'approved_by',
        'approved_at',
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
        'approved_at' => 'datetime',
    ];

    protected $appends = ['total_duration_minutes', 'sessions_count', 'is_expired'];

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

    public function getTotalDurationMinutesAttribute()
    {
        $totalMins = 0;
        
        if ($this->sessions->count() > 0) {
            foreach ($this->sessions as $session) {
                $start = $session->check_in_time;
                $end = $session->check_out_time 
                    ?? ($this->status === 'Checked In' ? now() : $session->check_in_time);
                if ($start && $end) {
                    $totalMins += $start->diffInMinutes($end);
                }
            }
        } else {
            // Fallback to legacy first/second columns
            if ($this->first_check_in_time) {
                $start1 = $this->first_check_in_time;
                $end1 = $this->first_check_out_time 
                    ?? ($this->status === 'Checked In' ? now() : $start1);
                $totalMins += $start1->diffInMinutes($end1);
            }
            if ($this->second_check_in_time) {
                $start2 = $this->second_check_in_time;
                $end2 = $this->second_check_out_time 
                    ?? ($this->status === 'Checked In' ? now() : $start2);
                $totalMins += $start2->diffInMinutes($end2);
            }
            if (!$this->first_check_in_time && $this->check_in_time) {
                $start = $this->check_in_time;
                $end = $this->check_out_time ?? now();
                $totalMins = $start->diffInMinutes($end);
            }
        }
        
        return (int) round($totalMins);
    }

    public function getSessionsCountAttribute()
    {
        if ($this->sessions->count() > 0) {
            return $this->sessions->count();
        }

        if ($this->first_check_in_time || $this->check_in_time) {
            return 1;
        }

        return 0;
    }
}
