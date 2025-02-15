<?php

namespace App\Http\Controllers;

use App\Models\Club;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class RegistrationController extends Controller
{
    public function persatuanIndex()
    {
        $persatuan = Club::where('club_category', 'Persatuan')->get();
        return view('registration.persatuan', compact('persatuan'));
        // kelab => $kelab (assigne $kelab(value) to kelab (key))
    }

    public function sukanIndex()
    {
        $sukan = Club::where('club_category', 'Sukan')->get();
        return view('registration.sukan', compact('sukan'));
    }


    public function beruniformIndex()
    {
        $beruniform = Club::where('club_category', 'Unit Beruniform')->get();
        return view('registration.beruniform', compact('beruniform'));
    }

    public function register(Request $request, $clubId, $clubType)
    {
        $userId = Auth::id();

        // Fetch the club to check its capacity
        $club = Club::find($clubId);

        if (!$club) {
            return redirect()->back()->with('error', 'Kelab tidak ditemui.');
        }

        //Check if user is already registerd for this club type
        $existingRegistration = Registration::where('user_id', $userId)->where('club_type', $clubType)->first();

        if ($existingRegistration) {
            return redirect()->back()->with('error', 'Anda telah mendaftar untuk ' . $clubType);
        }

        // Check if the club is already at capacity
        $currentRegistrations = Registration::where('club_id', $clubId)->count();
        if ($currentRegistrations >= $club->participant_total) {
            return redirect()->back()->with('error', 'Pendaftaran gagal: Kapasiti kelab dicapai');
        }

        //Register student to club
        Registration::create([
            'user_id' => $userId,   // Assign $userId (value) to user_id (key)
            'club_id' => $clubId,
            'club_type' => $clubType,
        ]);

        return redirect()->back()->with('success', 'Berjaya mendaftar ke ' . $club->club_name);
    }

    public function viewRegister()
    {
        $userId = Auth::id();

        // Fetch all registered clubs for the user
        $registrations = Registration::where('user_id', $userId)->with('club')->get();

        return view('registration.viewRegister', compact('registrations'));
    }

    public function unregister($registrationId)
    {
        $registration = Registration::find($registrationId);
    
        if ($registration) {
            $registration->delete();
            return redirect()->back()->with('success', 'Berjaya membatalkan pendaftaran daripada ' . $registration->club->club_name);
        }
    
        return redirect()->back()->with('error', 'Pendaftaran tidak ditemui');
    }
    
    public function searchPersatuan(Request $request)
    {
        try {
            $search = $request->input('search');
    
            // Get clubs based on search query
            $clubs = Club::where('club_category', 'Persatuan')
                ->when($search, function ($query) use ($search) {
                    return $query->where('club_name', 'LIKE', "%{$search}%");
                })
                ->get();
    
            return view('registration.persatuan', ['persatuan' => $clubs]);
        } catch (\Exception $e) {
            return back()->with('error', 'Error during search: ' . $e->getMessage());
        }
    }

    public function searchSukan(Request $request)
{
    try {
        $search = $request->input('search');

        // Get clubs based on search query
        $clubs = Club::where('club_category', 'Sukan')
            ->when($search, function ($query) use ($search) {
                return $query->where('club_name', 'LIKE', "%{$search}%");
            })
            ->get();

        return view('registration.sukan', ['sukan' => $clubs]);
    } catch (\Exception $e) {
        return back()->with('error', 'Error during search: ' . $e->getMessage());
    }
}

public function searchUnitBeruniform(Request $request)
{
    try {
        $search = $request->input('search');

        // Get clubs based on search query
        $clubs = Club::where('club_category', 'Unit Beruniform')
            ->when($search, function ($query) use ($search) {
                return $query->where('club_name', 'LIKE', "%{$search}%");
            })
            ->get();

        return view('registration.beruniform', ['beruniform' => $clubs]);
    } catch (\Exception $e) {
        return back()->with('error', 'Error during search: ' . $e->getMessage());
    }
}
}
