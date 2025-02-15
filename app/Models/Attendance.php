<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $primaryKey = 'attendance_id'; // Set custom primary key

    protected $fillable = [
        'user_id',  // References the user (student)
        'club_id',
        'week_number',
        'status'
    ];

    // A student (user) can have multiple attendance records for a club
    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // A club can have multiple attendance records
    public function club()
    {
        return $this->belongsTo(Club::class, 'club_id');
    }
}
