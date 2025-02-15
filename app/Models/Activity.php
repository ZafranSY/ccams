<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $primaryKey = 'activity_id'; // Set activity_id as the primary key
    public $incrementing = true; // Indicate it's an auto-incrementing key
    protected $keyType = 'int'; // Set the key type to integer

    protected $fillable = [
        'activity_name',
        'location',
        'date_time',
        'description',
        'participants',
        'poster',
        'category',
        'duration',
        'club_id', // Add club_id to the fillable properties
        'registration_id' // Add registration_id to the fillable properties
    ];

    // This method ensures Laravel uses 'activity_id' for route model binding
    public function getRouteKeyName()
    {
        return 'activity_id';
    }

    // Define the relationship with the Club model
    public function club()
    {
        return $this->belongsTo(Club::class, 'club_id', 'club_id');
    }

    // Define the relationship with the Registration model
    public function registration()
    {
        return $this->belongsTo(Registration::class, 'registration_id', 'registration_id');
    }
}
