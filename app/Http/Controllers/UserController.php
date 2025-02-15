<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Activity;
use App\Models\Club;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
class UserController extends Controller
{
    public function store(Request $request)
    {
        // Validate the incoming data
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'ic' => 'required|string|max:12',
            'role' => 'required|string|in:student,teacher',
            'classroom'=> 'required|string|max:255'
        ]);
        
        // Create and save the user to the database with a hashed password
        User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']), // Hash the password
            'ic' => $validatedData['ic'],
            'role' => $validatedData['role'],
            'classroom' => $validatedData['classroom'],
        ]);

        return redirect()->route('login')->with('success', 'Daftar berjaya');
    }

    public function checkEmail(Request $request)
    {
        $exists = User::where('email', $request->email)->exists();
        return response()->json(['exists' => $exists]);
    }

    public function checkName(Request $request)
    {
        $exists = User::where('name', $request->name)->exists();
        return response()->json(['exists' => $exists]);
    }

    public function checkIC(Request $request)
    {
        $exists = User::where('ic', $request->ic)->exists();
        return response()->json(['exists' => $exists]);
    }      
    

    public function create()
    {
        return view('login.signin'); 
    }

    public function showLoginForm()
    {
        return view('login.index'); 
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'email' => !$user ? 'The email address does not exist.' : null,
                'password' => $user && !Hash::check($request->password, $user->password) ? 'Kata laluan yang anda masukkan tidak betul.' : null,
            ]);
        }

        Auth::login($user);
        $user->update(['last_login_at' => now('UTC')]);

        $request->session()->regenerate();

        return $this->redirectToDashboard($user);
    }
    
    private function redirectToDashboard(User $user)
    {
        $routes = [
            'teacher' => 'dashboard.index',
            'student' => 'dashboard.index',
        ];

        return isset($routes[$user->role])
            ? redirect()->route($routes[$user->role])->with('success', 'Welcome, ' . ucfirst($user->role) . '!')
            : redirect()->route('login.index')->with('error', 'Invalid user role!');
    }

    // public function logout(): RedirectResponse
    // {
    //     return redirect()->route('login'); 
    // }

    public function logout(): RedirectResponse
    {
        auth()->logout(); 
        return redirect()->route('login'); 
    }

    public function index()
    {
        // $user = Auth::user();
        // $user->last_login_at = Carbon::parse($user->last_login_at)->format('Y-m-d H:i:s');

        // $activities = $user->activities; 
        // $clubs = $user->clubs; 

        // return view('profile.index', compact('user', 'activities', 'clubs'));
    $users = User::all(); 
    foreach ($users as $user) {
        $user->last_login_at = Carbon::parse($user->last_login_at)->format('Y-m-d H:i:s');
    }
    $activities = $user->activities; 
    $clubs = $user->clubs; 
    return view('profile.list', compact('users', 'activities', 'clubs'));
    }

    public function show(int $id)
    {
        $user = User::findOrFail($id);
        return view('profile.show', compact('user')); 
    }

    public function showStudentProfile(int $id)
    {
    $user = User::findOrFail($id);
    return view('club.show', compact('user'));
    }

    public function edit(int $id)
    {
        $user = User::findOrFail($id); 
        return view('users.edit', compact('user')); 
    }

    public function update(Request $request, int $id)
    {

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id, 
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|string',
        ]);
        $user = User::findOrFail($id); 
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'] ? Hash::make($validated['password']) : $user->password,
            'role' => $validated['role'],
        ]);
        return redirect()->route('login.index')->with('success', 'Berjaya disimpan');
    }

    public function destroy(int $id)
    {
        $user = User::findOrFail($id);
        $user->delete(); 
        return redirect()->route('users.index')->with('success', 'Pengguna berjaya dipadamkan.');
    }

    // Reset password
    public function resetPassword(Request $request)
    {
        // Validate the incoming data
        $validatedData = $request->validate([
            'email' => 'required|email|exists:users,email', // Ensure email exists in the users table
            'ic' => 'required|string|max:12|exists:users,ic', // Ensure IC exists in the users table
            'Newpassword' => 'required|min:8|confirmed', // Password rules and confirmation
        ]);
        // Retrieve the user based on email and IC
        $user = User::where('email', $validatedData['email'])
                    ->where('ic', $validatedData['ic'])
                    ->first();
        if (!$user) {
            // If the user is not found with the provided email and IC
            return redirect()->back()->withErrors(['email' => 'E-mel atau IC tidak betul.'])->withInput();
        }
        // Update the password
        $user->password = Hash::make($validatedData['Newpassword']); // Hash the new password
        $user->save();
        // Redirect to the login page or display success
        return redirect()->route('login')->with('success', 'Kata laluan telah berjaya ditetapkan semula!');
    }

    public function checkEmailAndICMatch(Request $request)
    {
        $email = $request->input('email');
        $ic = $request->input('ic');
        // Check if the email and IC match the same user
        $user = User::where('email', $email)->where('ic', $ic)->first();
        if ($user) {
            return response()->json(['match' => true]);
        } else {
            return response()->json(['match' => false]);
        }
    }

    // Profile
    public function showprofile()
    {
        $user = Auth::user();
        $user->last_login_at = Carbon::parse($user->last_login_at)->format('Y-m-d H:i:s');

        $activities = $user->activities; 
        $clubs = $user->clubs; 

        return view('profile.index', compact('user', 'activities', 'clubs'));
    }    

    // Update the profile
    public function editprofile(Request $request)
    {
    // Validation for new fields (address, city, etc.)
    $request->validate([
        'address' => 'nullable|string',
        'city' => 'nullable|string',
        'country' => 'nullable|string',
        'postal_code' => 'nullable|string',
        'about_me' => 'nullable|string',
        'current_password' => 'nullable|string',
        'new_password' => 'nullable|string|confirmed|min:8',
        'profile_pic' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Optional image upload for profile picture
    ]);

    // Get the current authenticated user
    $user = Auth::user();
    if ($request->hasFile('profile_pic')) {

        
        if ($user->profile_pic) {
            Storage::delete('public/' . $user->profile_pic);
        }
        $profilePicPath = $request->file('profile_pic')->store('public/profiles');
        $user->profile_pic = basename($profilePicPath); // Save the filename to the database
    }

    // Handle profile picture upload (if present)
    $profilePicPath = null;
    if ($request->hasFile('profile_pic')) {
        $profilePicPath = $request->file('profile_pic')->store('profiles', 'public');
        $user->profile_pic = basename($profilePicPath); // Save the filename to the database
    }

    // Update other fields (address, city, country, etc.)
    $user->address = $request->address;
    $user->city = $request->city;
    $user->country = $request->country;
    $user->postal_code = $request->postal_code;
    $user->about_me = $request->about_me;

    // If a new password is provided, check the current password and update the password field
    if ($request->new_password) {
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Kata laluan semasa tidak betul']);
        }
        $user->password = Hash::make($request->new_password);
    }

    // Save the changes to the user model
    $user->save();
    return redirect()->route('profile.index')->with('success', 'Profil berjaya dikemas kini');
    }
}
