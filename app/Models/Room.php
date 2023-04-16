<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
  
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'capacity',
        'availability',
    ];

    public function reservations()
    {
        return $this->hasMany(Reservation::class,"room_id","id");
    }
}
