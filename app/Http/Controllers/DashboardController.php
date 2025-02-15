<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Club;
use App\Models\Activity;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller 
{
    public function showUserSummary()
    {
        // Retrieve registrations for the user
        $registrations = Registration::with('club')->where('user_id', Auth::id())->get();
        
        // Retrieve user details
        $user = User::findOrFail(Auth::id());

        // Retrieve activities based on the clubs the user is registered to
        $clubIds = $registrations->pluck('club_id');
        $activities = Activity::whereIn('club_id', $clubIds)->get();
        
        // Pass registrations, user details, and activities to the view
        return view('dashboard.index', [
            'registrations' => $registrations,
            'user' => $user,
            'activities' => $activities
        ]);
    }
}
