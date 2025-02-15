<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',                        
        'ic',
        'last_login_at',
        'role', // 'student' or 'teacher'
        'peofile_pic',
        'classroom'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Relationships

    /**
     * A user (teacher) can manage many clubs.
     */
    public function clubs()
    {
        return $this->belongsToMany(Club::class, 'registrations', 'user_id', 'club_id');
    }

    public function activities()
    {
        return $this->hasMany(Activity::class, 'activity_id');
    }

    /**
     * A user (student) can belong to many clubs.
     */
    public function clubsAsStudent()
    {
    return $this->hasManyThrough(Club::class, Registration::class, 'user_id', 'club_id');
    }


    /**
     * A user can have many attendances.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'user_id');  // Assuming 'user_id' is the foreign key in the attendances table
    }

    /**
     * A student can have many registrations.
     */
    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    public function assessments()
    {
        return $this->hasMany(Assessment::class, 'assessment_id', 'assessment_id');
    }

    // Helper Methods

    /**
     * Check if the user is a teacher.
     */
    public function isTeacher()
    {
        return $this->role === 'teacher';
    }

    /**
     * Check if the user is a student.
     */
    public function isStudent()
    {
        return $this->role === 'student';
    }
}
