<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Club;
use App\Models\Registration;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    // For the student view - show only the clubs they are registered to
    public function indexStudent()
    {
        $userId = auth()->id(); // Get the logged-in user's ID
    
        // Fetch all registered clubs for the user with the club details
        $registrations = Registration::where('user_id', $userId)->with('club')->get();
    
        // Extract the clubs from the registrations
        $clubs = $registrations->map(function ($registration) {
            return $registration->club; // Ensure this fetches the club related to the registration
        });
    
        return view('attendance.index', compact('clubs'));
    } 

    // For the teacher view - show all clubs they manage
    public function indexTeacher()
    {
        $userId = auth()->id(); // Get the logged-in user's ID
    
        // Fetch all registered clubs for the user with the club details
        $registrations = Registration::where('user_id', $userId)->with('club')->get();
    
        // Extract the clubs from the registrations
        $clubs = $registrations->map(function ($registration) {
            return $registration->club; // Ensure this fetches the club related to the registration
        });
    
        return view('attendance.index', compact('clubs'));
    }
    

    // Show attendance for a specific club
    public function show($clubId, Request $request)
    {
        // Fetch the club along with its registered students and their attendance
        $club = Club::with(['students' => function ($query) {
            $query->where('role', 'student'); // Ensure only students are fetched
        }, 'students.attendances'])
            ->findOrFail($clubId);

        // Get the start and end weeks from the request or use defaults
        $startWeek = $request->query('start_week', 1); // Default to week 1
        $endWeek = $request->query('end_week', 12);   // Default to week 12

        // Pass the club and related data to the view
        return view('attendance.show', [
            'club' => $club,
            'students' => $club->students, // Students filtered by the role
            'startWeek' => $startWeek,
            'endWeek' => $endWeek,
        ]);
    }



    // Store attendance for multiple students
    public function store(Request $request, $clubId)
    {
        // Validate the form data
        $request->validate([
            'attendance' => 'required|array',
            'attendance.*.week_*' => 'required|in:Present,Absent,Excused',
        ]);

        // Loop through the attendance data
        foreach ($request->attendance as $studentId => $weeks) {
            // Find the student
            $student = User::findOrFail($studentId);

            // Loop through weeks (1 to 12) and update attendance
            foreach ($weeks as $week => $status) {
                // Extract the week number from the key (week_1, week_2, etc.)
                preg_match('/week_(\d+)/', $week, $matches);
                $weekNumber = (int) $matches[1];

                // Use updateOrCreate to handle attendance
                Attendance::updateOrCreate(
                    [
                        'user_id' => $studentId,
                        'club_id' => $clubId,
                        'week_number' => $weekNumber,
                    ],
                    ['status' => $status]
                );
            }

        }

        // Redirect with success message
        return redirect()->route('attendance.show', ['club' => $clubId])
            ->with('success', 'Attendance updated successfully!');
    }

    // Update individual student's attendance
    public function update(Request $request, $studentId)
    {
        $validated = $request->validate([
            'club_id' => 'required|exists:clubs,club_id',
            'attendance' => 'required|array',
        ]);

        $clubId = $validated['club_id'];
        $attendanceData = $validated['attendance'];

        foreach ($attendanceData as $weekNumber => $data) {

            $status = $data['status'];
            
                // Skip if no status is selected
                if (is_null($status) || $status === '') {
                    continue;
                }

                // Update or create attendance record for the specified week
                Attendance::updateOrCreate(
                    [
                        'user_id' => $studentId,
                        'club_id' => $clubId,
                        'week_number' => $weekNumber,
                    ],
                    ['status' => $status]
                );
        }

        return redirect()->route('attendance.show', ['clubs' => $clubId])
                ->with('success', 'Attendance updated successfully!');
    }


    public function viewDetails($user_id, $club_id)
    {
        $student = User::with('attendances')->findOrFail($user_id);
        $club = Club::findOrFail($club_id);
        
        $attendances = $student->attendances->where('club_id', $club_id);

        // Calculate totals
        $totalPresent = $attendances->where('status', 'Hadir')->count();
        $totalAbsent = $attendances->where('status', 'T. Hadir')->count();
        $totalExcused = $attendances->where('status', 'Dikecuali')->count();

        return view('attendance.details', compact('student', 'club', 'attendances', 'totalPresent', 'totalAbsent', 'totalExcused'));
    }

    public function getTotalPresent($user_id, $club_id)
    {
        $totalPresent = Attendance::where('user_id', $user_id)
            ->where('club_id', $club_id)
            ->where('status', 'Present')
            ->count();

        return response()->json(['totalPresent' => $totalPresent]);
    }
    
}
