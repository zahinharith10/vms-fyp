<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HouseUnit extends Model
{
    use HasFactory;

    protected $fillable = ['block', 'floor', 'unit_number'];

    protected $appends = ['formatted_unit'];

    protected static function boot()
    {
        parent::boot();

        $normalize = fn($val) => is_numeric($val) ? (string)(int)$val : trim($val);

        static::saving(function ($unit) use ($normalize) {
            $unit->block = $normalize($unit->block);
            $unit->floor = $normalize($unit->floor);
            $unit->unit_number = $normalize($unit->unit_number);
        });
    }

    public function getFormattedUnitAttribute()
    {
        return "{$this->block}-{$this->floor}-{$this->unit_number}";
    }

    public function residents()
    {
        return $this->hasMany(Resident::class);
    }
}

