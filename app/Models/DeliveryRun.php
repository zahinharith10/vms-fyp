<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_personnel_id',
        'type',
        'status',
        'entry_time',
        'exit_time',
    ];

    protected $casts = [
        'entry_time' => 'datetime',
        'exit_time' => 'datetime',
    ];

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(DeliveryPersonnel::class, 'delivery_personnel_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(DeliveryLog::class)->orderBy('id');
    }

    public function refreshStatus(): void
    {
        $logs = $this->logs()->get();

        if ($logs->isEmpty()) {
            return;
        }

        if ($logs->contains(fn (DeliveryLog $log) => $log->status === 'Pending')) {
            $this->update(['status' => 'Pending']);

            return;
        }

        if ($logs->contains(fn (DeliveryLog $log) => in_array($log->status, ['Approved', 'Checked In'], true))) {
            $checkedIn = $logs->contains(fn (DeliveryLog $log) => $log->entry_time !== null && $log->exit_time === null);
            $this->update(['status' => $checkedIn ? 'Checked In' : 'Approved']);

            return;
        }

        if ($logs->every(fn (DeliveryLog $log) => $log->status === 'Checked Out')) {
            $this->update(['status' => 'Checked Out']);

            return;
        }

        if ($logs->contains(fn (DeliveryLog $log) => $log->status === 'Rejected')) {
            $this->update(['status' => 'Rejected']);
        }
    }
}
