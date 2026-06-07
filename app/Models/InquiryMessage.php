<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InquiryMessage extends Model
{
    protected $fillable = [
        'inquiry_id',
        'sender_type',
        'sender_name',
        'message',
    ];

    public function inquiry()
    {
        return $this->belongsTo(Inquiry::class);
    }
}
