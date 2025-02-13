<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Club;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ActivityController extends Controller
{
    
    // Show the activities list
    public function index(Request $request)
    {
        $filter = $request->input('filter', 'all');
        $viewType = $request->input('view', 'card');
        $user = auth()->user();

        if ($filter == 'registered') {
            $activities = Activity::whereIn('club_id', $user->clubs->pluck('club_id'))
                                  ->where('category', '!=', 'Open to All')
                                  ->get();
        } else {
            $activities = Activity::where('category', 'Open to All')->get();
        }

        return view('activities.index', compact('activities', 'viewType'));
    }

    // Show the create form
    public function create()
    {
        $clubs = auth()->user()->clubs; // Assuming the user model has a relationship with clubs
        return view('activities.create', compact('clubs'));
    }

    // Handle form submission
    public function store(Request $request)
    {
        $request->validate([
            'activity_name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'date_time' => 'required|date',
            'description' => 'required|string',
            'participants' => 'nullable|integer',
            'poster' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'category' => 'required|string',
            'duration' => 'required|string',
            'club_id' => 'nullable|integer',
            'registration_id' => 'nullable|integer',
        ]);

        $posterPath = null;
        if ($request->hasFile('poster')) {
            $posterPath = $request->file('poster')->store('posters', 'public');
        }

        Activity::create([
            'activity_name' => $request->input('activity_name'),
            'location' => $request->input('location'),
            'date_time' => $request->input('date_time'),
            'description' => $request->input('description'),
            'participants' => $request->input('participants'),
            'poster' => $posterPath,
            'category' => $request->input('category'),
            'duration' => $request->input('duration'),
            'club_id' => $request->input('club_id'),
            'registration_id' => $request->input('registration_id') ?? auth()->user()->id,
        ]);

        return redirect()->route('activities.index')->with('success', 'Activity added successfully!');
    }

    // Show the edit form with the current activity data
    public function edit(Activity $activity)
    {
        $clubs = auth()->user()->clubs; // Assuming the user model has a relationship with clubs
        return view('activities.edit', compact('activity', 'clubs'));
    }

    // Handle the form submission and update the activity
    public function update(Request $request, Activity $activity)
    {
        $request->validate([
            'activity_name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'date_time' => 'required|date',
            'description' => 'required|string',
            'participants' => 'nullable|integer',
            'poster' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'category' => 'required|string',
            'duration' => 'required|string',
            'club_id' => 'nullable|integer',
            'registration_id' => 'nullable|integer',
        ]);

        $updateData = [
            'activity_name' => $request->input('activity_name'),
            'location' => $request->input('location'),
            'date_time' => $request->input('date_time'),
            'description' => $request->input('description'),
            'participants' => $request->input('participants'),
            'category' => $request->input('category'),
            'duration' => $request->input('duration'),
            'club_id' => $request->input('club_id'),
            'registration_id' => $request->input('registration_id') ?? auth()->user()->id,
        ];

        if ($request->hasFile('poster')) {
            if ($activity->poster) {
                Storage::disk('public')->delete($activity->poster);
            }
            $updateData['poster'] = $request->file('poster')->store('posters', 'public');
        }

        $activity->update($updateData);

        return redirect()->route('activities.index')->with('success', 'Activity updated successfully!');
    }

    public function destroy(Activity $activity)
    {
        // Delete the activity
        $activity->delete();

        // Redirect with a success message
        return redirect()->route('activities.index')->with('success', 'Activity deleted successfully');
    }

    public function show(Activity $activity)
    {
        return view('activities.show', compact('activity'));
    }
}