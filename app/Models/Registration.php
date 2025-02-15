<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Registration extends Model
{
    use HasFactory;

    public $timestamps = false; // Disable timestamps
    protected $table = 'registrations';
    protected $primaryKey = 'registration_id';

    protected $fillable = [
        'user_id',
        'club_id',
        'club_type',
    ];

    public function club()
    {
        return $this->belongsTo(Club::class, 'club_id', 'club_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function assessments()
    {
        return $this->hasMany(Assessment::class, 'club_id', 'club_id')
            ->where('user_id', $this->user_id);
    }

    // Define the relationship with the Activity model
    public function activities()
    {
        return $this->hasMany(Activity::class, 'registration_id', 'registration_id');
    }
}
