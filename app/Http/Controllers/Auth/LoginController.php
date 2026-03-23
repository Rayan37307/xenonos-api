<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Show the login page.
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Handle login request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            // Store user ID in session for web authentication
            $request->session()->put('auth_token', $user->id);

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Show dashboard data (web session auth).
     */
    public function dashboard(Request $request)
    {
        $userId = $request->session()->get('auth_token');
        $user = User::find($userId);

        if (!$user) {
            return redirect()->route('login');
        }

        $stats = [
            'total_projects' => Project::count(),
            'active_projects' => Project::where('status', 'active')->count(),
            'total_tasks' => Task::count(),
            'pending_tasks' => Task::where('status', 'todo')->count(),
        ];

        return response()->json([
            'user' => $user,
            'stats' => $stats,
        ]);
    }

    /**
     * Show dashboard data (API Sanctum auth).
     */
    public function dashboardApi(Request $request)
    {
        $user = $request->user('sanctum');

        $stats = [
            'total_projects' => Project::count(),
            'active_projects' => Project::where('status', 'active')->count(),
            'total_tasks' => Task::count(),
            'pending_tasks' => Task::where('status', 'todo')->count(),
        ];

        return response()->json([
            'user' => $user,
            'stats' => $stats,
        ]);
    }

    /**
     * Handle logout request.
     */
    public function logout(Request $request)
    {
        $request->session()->forget('auth_token');

        return redirect()->route('login');
    }
}
