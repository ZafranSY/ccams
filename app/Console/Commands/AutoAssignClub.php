<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Club;
use App\Models\Registration;
use Carbon\Carbon;

class AutoAssignClub extends Command
{
    protected $signature = 'clubs:auto-assign';
    protected $description = 'Automatically assign students to clubs with free seating after registration deadline.';

    public function handle()
    {
        $this->info("Auto-assigning clubs to unregistered students...");

        // Step 1: Check the registration deadline
        $deadline = env('CLUB_REGISTRATION_DEADLINE');
        if (Carbon::now()->lt(Carbon::parse($deadline))) {
            $this->info("Deadline not reached. Skipping auto-assignment.");
            return;
        }

        // Step 2: Find students with role 'student' who haven't registered all club types
        $requiredClubTypes = ['Persatuan', 'Sukan', 'Unit Beruniform']; // Define all club types
        $unregisteredStudents = User::where('role', 'student')
            ->get()
            ->filter(function ($student) use ($requiredClubTypes) {
                $registeredClubTypes = $student->registrations->pluck('club_type')->unique();
                return $registeredClubTypes->count() < count($requiredClubTypes); // Find students with missing club types
            });

        if ($unregisteredStudents->isEmpty()) {
            $this->info("All students have already registered for the required club types.");
            return;
        }

        // Step 3: Find clubs with free seating grouped by club type
        $availableClubsByType = Club::with('registrations')
            ->get()
            ->filter(function ($club) {
                return $club->participant_total > $club->registrations->count(); // Clubs with available seating
            })
            ->groupBy('club_category'); // Group by club category (club type)

        if ($availableClubsByType->isEmpty()) {
            $this->error("No clubs with free seating available.");
            return;
        }

        // Step 4: Assign students to clubs
        foreach ($unregisteredStudents as $student) {
            $registeredClubTypes = $student->registrations->pluck('club_type')->unique();

            foreach ($requiredClubTypes as $clubType) {
                // Skip already registered club types
                if ($registeredClubTypes->contains($clubType)) {
                    continue;
                }

                // Get clubs with available seating for the current club type
                $validClubs = $availableClubsByType[$clubType] ?? collect();

                if ($validClubs->isEmpty()) {
                    $this->info("No available clubs for Club Type: {$clubType}");
                    continue;
                }

                // Assign the first available club to the student
                $club = $validClubs->shift(); // Remove the club from the list to avoid duplicates

                Registration::create([
                    'user_id' => $student->id,
                    'club_id' => $club->club_id,
                    'club_type' => $clubType,
                ]);

                $this->info("Student ID {$student->id} assigned to Club ID {$club->club_id} (Type: {$clubType})");
            }
        }

        $this->info("Auto-assignment completed successfully!");
    }
}
