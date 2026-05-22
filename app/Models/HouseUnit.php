<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HouseUnit extends Model
{
    use HasFactory;

    protected $fillable = ['block', 'floor', 'unit_number'];

    protected $appends = ['formatted_unit'];

    public function getFormattedUnitAttribute()
    {
        return "{$this->block} - {$this->floor} - {$this->unit_number}";
    }

    public function residents()
    {
        return $this->hasMany(Resident::class);
    }
}
