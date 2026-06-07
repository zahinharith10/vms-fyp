<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_type',
        'user_id',
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
    ];

    /**
     * Get the owning user model (polymorphic relationship).
     */
    public function user()
    {
        return $this->morphTo(null, 'user_type', 'user_id');
    }
}
