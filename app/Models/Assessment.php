<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Assessment extends Model
{
    use HasFactory;

    protected $table = 'assessments';
    protected $primaryKey = 'assessment_id'; // Specify your primary key here

    protected $fillable = [
        'user_id',
        'club_id',
        'position',
        'engagement',
        'achievement',
        'commitment',
        'contribution',
        'attendance',
        'comment',
        'total_mark', // Add this line
    ];

    protected $casts = [
        'engagement' => 'array', // Cast to array
        'achievement' => 'array', // Cast to array
        'commitment' => 'array', // Cast to array
        'contribution' => 'array', // Cast to array
    ];

    public function user()
    {
        return $this->belongsTo(User::class,  'user_id', "id");
    }

    public function club()
    {
        return $this->belongsTo(Club::class, 'club_id');
    }
}
