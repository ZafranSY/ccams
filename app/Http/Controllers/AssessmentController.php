<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Assessment;
use App\Models\User;
use App\Models\Registration;
use App\Models\Club;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class AssessmentController extends Controller
{
    // Show list of clubs the student is registered to
    public function index()
    {
        $userId = Auth::id();
        $user = Auth::user();

        // Fetch all registered clubs for the user
        $registrations = Registration::where('user_id', $userId)->with('club')->get();

        return view("assessment.index", compact('registrations', 'user'));
    }

    // public function list($club_id)
    // {
    //     $userId = Auth::id();

    //     // Fetch the specific club registration for the teacher
    //     $registration = Registration::where('user_id', $userId)
    //         ->where('club_id', $club_id)
    //         ->with('club')
    //         ->first();

    //     // Fetch assessments for this club
    //     $assessments = Assessment::where('club_id', $club_id)->with('user', 'club')->get();

    //     return view("assessment.list", [
    //         'assessments' => $assessments,
    //         'club' => $registration->club
    //     ]);
    // }

    public function list($club_id)
    {
        $userId = Auth::id();

        // Fetch the specific club registration for the teacher
        $registration = Registration::where('user_id', $userId)
            ->where('club_id', $club_id)
            ->with('club')
            ->first();

        // Fetch assessments for this club
        $assessments = Assessment::where('club_id', $club_id)->with('user', 'club')->get();

        // Get IDs of users who already have assessments
        $assessedUserIds = $assessments->pluck('user_id')->toArray();

        // Fetch registered users with role 'Student' who are not already assessed
        $users = Registration::where('club_id', $club_id)
            ->whereHas('user', function ($query) {
                $query->where('role', 'student');
            })
            ->whereNotIn('user_id', $assessedUserIds)
            ->with('user')
            ->get();

        return view("assessment.list", [
            'assessments' => $assessments,
            'club' => $registration->club,
            'users' => $users // Pass only unassessed students
        ]);
    }



    // public function create($club_id)
    // {
    //     // First verify the club exists
    //     $club = Club::findOrFail($club_id);

    //     // Get all registered students
    //     $registeredUsers = Registration::whereHas('user', function ($query) {
    //         $query->where('role', 'student');
    //     })
    //         ->where('club_id', $club_id)
    //         ->with('user')
    //         ->get();

    //     // Get IDs of students who already have assessments
    //     $assessedUserIds = Assessment::where('club_id', $club_id)
    //         ->pluck('user_id')
    //         ->toArray();

    //     // Filter out users who already have assessments
    //     $availableUsers = $registeredUsers->filter(function ($registration) use ($assessedUserIds) {
    //         return !in_array($registration->user_id, $assessedUserIds);
    //     });

    //     return view('assessment.create', [
    //         'users' => $availableUsers,
    //         'club' => $club,
    //         'club_id' => $club_id
    //     ]);
    // }

    public function create(Request $request, $club_id)
    {
        $club = Club::findOrFail($club_id);
        $userId = $request->input('user_id');
        $selectedUser = User::findOrFail($userId);

        return view('assessment.create', [
            'club' => $club,
            'club_id' => $club_id,
            'selectedUser' => $selectedUser
        ]);
    }


    public function store(Request $request)
    {
        // Validate incoming request data
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'position' => 'required',
            'engagement' => 'array',
            'achievement' => 'array',
            'commitment' => 'array',
            'contribution' => 'array',
            'attendance' => 'required|numeric',
            'comment' => 'required|string',
            'club_id' => 'required|exists:clubs,club_id',
        ]);

        // Define scores for position
        $positionScores = [
            'Pengerusi' => 10,
            'Naib Pengerusi' => 8,
            'Setiausaha' => 8,
            'Bendahari' => 8,
            'Naib Setiausaha' => 6,
            'Naib Bendahari' => 6,
            'AJK' => 6,
            'Ahli Aktif' => 4,
            'Ahli Biasa' => 2,
        ];
        // Get the score for the selected position
        $pos = $positionScores[$data['position']] ?? 0; // Default to 0 if not found


        // Define scores for engagement
        $engagementScores = [
            'I1' => 20,
            'N1' => 17,
            'C1' => 14,
            'D1' => 11,
            'S1' => 0,
            'I2' => 15,
            'N2' => 12,
            'C2' => 10,
            'D2' => 8,
            'S2' => 0,
            'I3' => 10,
            'N3' => 8,
            'C3' => 6,
            'D3' => 4,
            'S3' => 0,
        ];
        // Calculate total engagement score
        $eng = 0;
        if (!empty($request->engagement)) {
            foreach ($request->engagement as $engagement) {
                $eng += $engagementScores[$engagement] ?? 0; // Default to 0 if not found
            }
        } else {
            $data['engagement'] = "[]";
        }
        $eng = min($eng, 20); // Cap at 20 marks


        // Define scores for achievement
        $achievementScores = [
            'IC' => 20,
            'NC' => 17,
            'CC' => 14,
            'DC' => 11,
            'SC' => 8,
            'I1' => 19,
            'N1' => 16,
            'C1' => 13,
            'D1' => 10,
            'S1' => 7,
            'I2' => 18,
            'N2' => 15,
            'C2' => 12,
            'D2' => 9,
            'S2' => 6,
        ];
        // Calculate total achievement score
        $ach = 0;
        if (!empty($request->achievement)) {
            foreach ($request->achievement as $achievement) {
                $ach += $achievementScores[$achievement] ?? 0; // Default to 0 if not found
            }
        } else {
            $data['achievement'] = "[]";
        }
        $ach = min($ach, 20); // Cap at 10 marks


        // Define scores for commitment
        $commitmentScores = [
            'C1' => 3,
            'C2' => 3,
            'C3' => 2,
            'C4' => 2,
            'C5' => 2,
            'C6' => 2,
            'C7' => 2,
            'C8' => 2,
            'C9' => 2,
            'C10' => 2,
            'C11' => 2,
            'C12' => 2,
        ];
        // Calculate total commitment score
        $com = 0;
        if (!empty($request->commitment)) {
            foreach ($request->commitment as $commitment) {
                $com += $commitmentScores[$commitment] ?? 0; // Default to 0 if not found
            }
        } else {
            $data['commitment'] = "[]";
        }
        $com = min($com, 10); // Cap at 10 marks


        // Define scores for contribution
        $contributionScores = [
            'CS1' => 10,
            'CS2' => 10,
            'CS3' => 8,
            'CS4' => 5,
        ];
        // Calculate total contribution score
        $con = 0;
        if (!empty($request->contribution)) {
            foreach ($request->contribution as $contribution) {
                $con += $contributionScores[$contribution] ?? 0; // Default to 0 if not found
            }
        } else {
            $data['contribution'] = "[]";
        }
        $con = min($con, 10); // Cap at 10 marks


        // Calculate attendance score using the formula
        $attend = ($data['attendance'] / 12) * 40; // Attendance formula
        $attend = min($attend, 40);

        // Calculate total marks
        $total = $pos + $eng + $ach + $com + $con + $attend;
        // Cap the total marks at 110
        $data['total_mark'] = min($total, 110); // Max 110 marks

        // Prepare data for database insertion
        $assessmentData = [
            'user_id' => $data['user_id'], // Save user_id
            'position' => $data['position'],
            'engagement' => $data['engagement'],
            'achievement' => $data['achievement'],
            'commitment' => $data['commitment'],
            'contribution' => $data['contribution'],
            'attendance' => $data['attendance'],
            'comment' => $data['comment'],
            'total_mark' => $data['total_mark'],
            'club_id' => $data['club_id'],
        ];

        // Create new assessment record in the database
        Assessment::create($assessmentData);

        // Redirect to the assessment list page
        return redirect()->route('assessment.list', ['club_id' => $data['club_id']])->with('success', 'Penilaian berjaya disimpan!');
    }


    public function show($assessment_id)
    {
        $assessment = Assessment::findOrFail($assessment_id);
        $user = $assessment->user; // Assuming there's a relationship set up between Assessment and User
        return view("assessment.show", compact('assessment', 'user'));
    }

    public function view($club_id)
    {
        $user_id = Auth::id();
        $user = Auth::user();

        // Find the assessment with club information
        $assessment = Assessment::where('user_id', $user_id)
            ->where('club_id', $club_id)
            ->with('club') // Eager load the club relationship
            ->first();

        if (!$assessment) {
            return redirect()->route('assessment.index')
                ->with('error', 'Tiada penilaian ditemui untuk kelab ini lagi.');
        }

        return view("assessment.view", compact('assessment', 'user'));
    }

    public function edit($assessment_id)
    {
        $assessment = Assessment::findOrFail($assessment_id);

        // Get all registered students
        $registeredUsers = Registration::whereHas('user', function ($query) {
            $query->where('role', 'student');
        })
            ->where('club_id', $assessment->club_id)
            ->with('user')
            ->get();

        // Get IDs of students who already have assessments, excluding the current assessment
        $assessedUserIds = Assessment::where('club_id', $assessment->club_id)
            ->where('assessment_id', '!=', $assessment_id)
            ->pluck('user_id')
            ->toArray();

        // Filter out users who already have assessments, except the current user being edited
        $availableUsers = $registeredUsers->filter(function ($registration) use ($assessedUserIds) {
            return !in_array($registration->user_id, $assessedUserIds);
        });

        return view("assessment.edit", [
            'assessment' => $assessment,
            'users' => $availableUsers,
            'club' => $assessment->club
        ]);
    }

    public function update(Request $request, $assessment_id)
    {
        $assessment = Assessment::findOrFail($assessment_id);
        // Validate incoming request data
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'position' => 'required',
            'engagement' => 'array',
            'achievement' => 'array',
            'commitment' => 'array',
            'contribution' => 'array',
            'attendance' => 'required|numeric',
            'comment' => 'required|string',
            'club_id' => 'required|exists:clubs,club_id',
        ]);

        // Define scores for position
        $positionScores = [
            'Pengerusi' => 10,
            'Naib Pengerusi' => 8,
            'Setiausaha' => 8,
            'Bendahari' => 8,
            'Naib Setiausaha' => 6,
            'Naib Bendahari' => 6,
            'AJK' => 6,
            'Ahli Aktif' => 4,
            'Ahli Biasa' => 2,
        ];
        // Get the score for the selected position
        $pos = $positionScores[$data['position']] ?? 0; // Default to 0 if not found


        $engagementScores = [
            'I1' => 20,
            'N1' => 17,
            'C1' => 14,
            'D1' => 11,
            'S1' => 0,
            'I2' => 15,
            'N2' => 12,
            'C2' => 10,
            'D2' => 8,
            'S2' => 0,
            'I3' => 10,
            'N3' => 8,
            'C3' => 6,
            'D3' => 4,
            'S3' => 0,
        ];
        // Calculate total engagement score
        $eng = 0;
        if (!empty($request->engagement)) {
            foreach ($request->engagement as $engagement) {
                $eng += $engagementScores[$engagement] ?? 0; // Default to 0 if not found
            }
        } else {
            $data['engagement'] = "[]";
        }
        $eng = min($eng, 20); // Cap at 20 marks


        // Define scores for achievement
        $achievementScores = [
            'IC' => 20,
            'NC' => 17,
            'CC' => 14,
            'DC' => 11,
            'SC' => 8,
            'I1' => 19,
            'N1' => 16,
            'C1' => 13,
            'D1' => 10,
            'S1' => 7,
            'I2' => 18,
            'N2' => 15,
            'C2' => 12,
            'D2' => 9,
            'S2' => 6,
        ];
        // Calculate total achievement score
        $ach = 0;
        if (!empty($request->achievement)) {
            foreach ($request->achievement as $achievement) {
                $ach += $achievementScores[$achievement] ?? 0; // Default to 0 if not found
            }
        } else {
            $data['achievement'] = "[]";
        }
        $ach = min($ach, 20); // Cap at 10 marks


        // Define scores for commitment
        $commitmentScores = [
            'C1' => 3,
            'C2' => 3,
            'C3' => 2,
            'C4' => 2,
            'C5' => 2,
            'C6' => 2,
            'C7' => 2,
            'C8' => 2,
            'C9' => 2,
            'C10' => 2,
            'C11' => 2,
            'C12' => 2,
        ];
        // Calculate total commitment score
        $com = 0;
        if (!empty($request->commitment)) {
            foreach ($request->commitment as $commitment) {
                $com += $commitmentScores[$commitment] ?? 0; // Default to 0 if not found
            }
        } else {
            $data['commitment'] = "[]";
        }
        $com = min($com, 10); // Cap at 10 marks


        // Define scores for contribution
        $contributionScores = [
            'CS1' => 10,
            'CS2' => 10,
            'CS3' => 8,
            'CS4' => 5,
        ];
        // Calculate total contribution score
        $con = 0;
        if (!empty($request->contribution)) {
            foreach ($request->contribution as $contribution) {
                $con += $contributionScores[$contribution] ?? 0; // Default to 0 if not found
            }
        } else {
            $data['contribution'] = "[]";
        }
        $con = min($con, 10); // Cap at 10 marks


        // Calculate attendance score using the formula
        $attend = ($data['attendance'] / 12) * 40; // Attendance formula
        $attend = min($attend, 40); // Cap at 40 marks


        // Calculate total marks
        $total = $pos + $eng + $ach + $com + $con + $attend;
        // Cap the total marks at 110
        $data['total_mark'] = min($total, 110); // Max 110 marks

        // Update the assessment record in the database
        $assessment->update(array_merge($data, ['total_mark' => $data['total_mark']]));

        // Redirect to the assessment list page
        return redirect()->route('assessment.list', ['club_id' => $assessment->club_id])
            ->with('success', 'Penilaian berjaya dikemas kini!');
    }


    public function destroy($assessment_id)
    {
        $assessment = Assessment::findOrFail($assessment_id);
        $club_id = $assessment->club_id; // Store club_id before deletion

        $assessment->delete();

        return redirect()->route('assessment.list', ['club_id' => $club_id])
            ->with('success', 'Penilaian berjaya dipadamkan!');
    }

    public function classlist($classroom)
    {
        // Verify the teacher belongs to this classroom
        if (Auth::user()->classroom !== $classroom) {
            return redirect()->back()->with('error', 'Akses tanpa kebenaran!');
        }

        // Get all students in the class
        $students = User::where('classroom', $classroom)
            ->where('role', 'student')
            ->get();

        foreach ($students as $student) {
            // Get all registrations for the student with club and assessment info
            $registrations = Registration::where('user_id', $student->id)
                ->with(['club', 'club.assessments' => function ($query) use ($student) {
                    $query->where('user_id', $student->id);
                }])
                ->get();

            // Get Permainan club info
            $permainanReg = $registrations->first(function ($reg) {
                return $reg->club_type === 'Sukan';
            });
            $student->permainanClub = $permainanReg ? $permainanReg->club->club_name : null;
            $student->permainanAssessment = $permainanReg ? $permainanReg->club->assessments->first() : null;

            // Get Persatuan club info
            $persatuanReg = $registrations->first(function ($reg) {
                return $reg->club_type === 'Persatuan';
            });
            $student->persatuanClub = $persatuanReg ? $persatuanReg->club->club_name : null;
            $student->persatuanAssessment = $persatuanReg ? $persatuanReg->club->assessments->first() : null;

            // Get Unit Beruniform club info
            $uniformReg = $registrations->first(function ($reg) {
                return $reg->club_type === 'Unit Beruniform';
            });
            $student->uniformClub = $uniformReg ? $uniformReg->club->club_name : null;
            $student->uniformAssessment = $uniformReg ? $uniformReg->club->assessments->first() : null;

            // // Calculate average of available assessment marks
            // $marks = collect([
            //     $student->permainanAssessment?->total_mark,
            //     $student->persatuanAssessment?->total_mark,
            //     $student->uniformAssessment?->total_mark
            // ])->filter()->values();

            // $student->averageAssessment = $marks->isEmpty() ? 0 : round($marks->avg(), 2);
        }

        return view('assessment.classlist', compact('students', 'classroom'));
    }

    public function viewclass($student_id)
    {
        $student = User::findOrFail($student_id);

        // Get all registrations for the student
        $registrations = Registration::where('user_id', $student_id)
            ->with(['club', 'club.assessments' => function ($query) use ($student_id) {
                $query->where('user_id', $student_id);
            }])
            ->get();

        // Get assessments for each club type
        $permainanReg = $registrations->first(function ($reg) {
            return $reg->club_type === 'Sukan';
        });
        $permainanAssessment = $permainanReg ? $permainanReg->club->assessments->first() : null;

        $persatuanReg = $registrations->first(function ($reg) {
            return $reg->club_type === 'Persatuan';
        });
        $persatuanAssessment = $persatuanReg ? $persatuanReg->club->assessments->first() : null;

        $uniformReg = $registrations->first(function ($reg) {
            return $reg->club_type === 'Unit Beruniform';
        });
        $uniformAssessment = $uniformReg ? $uniformReg->club->assessments->first() : null;

        // Set club names
        $student->permainanClub = $permainanReg ? $permainanReg->club->club_name : null;
        $student->persatuanClub = $persatuanReg ? $persatuanReg->club->club_name : null;
        $student->uniformClub = $uniformReg ? $uniformReg->club->club_name : null;

        return view('assessment.viewclass', compact(
            'student',
            'permainanAssessment',
            'persatuanAssessment',
            'uniformAssessment'
        ));
    }
}
