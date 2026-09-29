<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    /**
     * Display all users with role management
     */
    public function index()
    {
        // Get users with pagination and filtering
        $filter = request('filter');
        $search = request('search');
        
        $query = User::with('roles');
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('affiliation', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            });
        }
        
        if ($filter === 'authors') {
            $query->whereHas('roles', function($q) {
                $q->where('name', 'author');
            });
        } elseif ($filter === 'reviewers') {
            $query->whereHas('roles', function($q) {
                $q->where('name', 'reviewer');
            });
        } elseif ($filter === 'admins') {
            $query->whereHas('roles', function($q) {
                $q->where('name', 'admin');
            });
        } elseif ($filter === 'scientific_admins') {
            $query->whereHas('roles', function($q) {
                $q->where('name', 'scientific_admin');
            });
        }
        
        $users = $query->orderBy('first_name')->paginate(20)->withQueryString();
        
        // Calculate statistics
        $stats = [
            'total_users' => User::count(),
            'admins' => User::whereHas('roles', function($query) {
                $query->where('name', 'admin');
            })->count(),
            'reviewers' => User::whereHas('roles', function($query) {
                $query->where('name', 'reviewer');
            })->count(),
            'scientific_admins' => User::whereHas('roles', function($query) {
                $query->where('name', 'scientific_admin');
            })->count(),
            'users_with_submissions' => User::whereHas('abstractSubmissions')->count(),
            'attendees' => User::count(),
        ];
        
        $roles = Role::all();
        
        return view('admin.users.index', compact('users', 'roles', 'stats'));
    }

    /**
     * Show user edit form
     */
    public function edit(User $user)
    {
        $user->load('roles');
        $roles = Role::all();
        // Calculate user stats
        $userStats = [
            'total_submissions' => $user->abstractSubmissions()->count(),
            'reviews_completed' => $user->reviews()->whereNotNull('completed_at')->count(),
            'member_since' => $user->created_at ? $user->created_at->format('M Y') : 'N/A',
        ];
        $availableRoles = [
            'admin' => 'Administrator',
            'scientific_admin' => 'Scientific Coordinator',
            'reviewer' => 'Reviewer',
            'finance_officer' => 'Finance Officer',
            'registration_officer' => 'Registration Officer',
            'chief_rapporteur' => 'Chief Rapporteur',
            'author' => 'Author',
        ];
        return view('admin.users.edit', compact('user', 'roles', 'userStats', 'availableRoles'));
    }

    /**
     * Update user information
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id)
            ],
            'affiliation' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'student_status' => 'nullable|in:yes,no',
            'title' => 'nullable|string|max:255',
            'registration_category' => 'nullable|string|in:professional_local,professional_international,student_local,student_international,test_item',
        ]);

        // Handle password update if provided
        if ($request->filled('password')) {
            $request->validate([
                'password' => 'required|string|min:8|confirmed'
            ]);
            $validated['password'] = Hash::make($request->password);
        }

        $user->fill($validated);

        if (array_key_exists('registration_category', $validated)) {
            $user->syncStudentStateFromRegistrationCategory($validated['registration_category']);
        }

        $user->save();

        // Update roles
        if ($request->has('roles')) {
            $roleNames = $request->input('roles', []);
            foreach (['admin', 'scientific_admin', 'reviewer', 'finance_officer', 'registration_officer', 'chief_rapporteur', 'author'] as $role) {
                if (in_array($role, $roleNames)) {
                    if (!$user->hasRole($role)) {
                        $user->assignRole($role, false, auth()->id());
                    }
                } else {
                    if ($user->hasRole($role)) {
                        $user->removeRole($role);
                    }
                }
            }
        }

        // Set primary role
        if ($request->filled('primary_role') && $user->hasRole($request->input('primary_role'))) {
            $user->setPrimaryRole($request->input('primary_role'));
        }

        $this->syncCurrentUserActiveRole($user);

        return redirect()->route('admin.users')
            ->with('success', 'User updated successfully');
    }

    /**
     * Toggle user role
     */
    public function toggleRole(Request $request, User $user, $role)
    {
        $validRoles = ['author', 'reviewer', 'scientific_admin', 'finance_officer', 'registration_officer', 'chief_rapporteur', 'admin'];
        
        if (!in_array($role, $validRoles)) {
            return response()->json(['error' => 'Invalid role'], 400);
        }
        
        if ($user->hasRole($role)) {
            // Remove role
            $user->removeRole($role);
            $action = 'removed';
        } else {
            // Add role (don't set as primary unless it's their only role)
            $isPrimary = $user->roles()->count() === 0;
            $user->assignRole($role, $isPrimary, Auth::id());
            $action = 'added';
        }

        $this->syncCurrentUserActiveRole($user);
        
        return response()->json([
            'success' => true,
            'message' => "Role {$role} {$action} for {$user->first_name} {$user->last_name}",
            'action' => $action,
            'role' => $role,
            'user_name' => $user->first_name . ' ' . $user->last_name
        ]);
    }

    /**
     * Set user's primary role
     */
    public function setPrimaryRole(Request $request, User $user, $role)
    {
        if (!$user->hasRole($role)) {
            return response()->json([
                'success' => false,
                'message' => 'User does not have this role'
            ], 400);
        }

        $user->setPrimaryRole($role);
        $this->syncCurrentUserActiveRole($user);
        
        return response()->json([
            'success' => true,
            'message' => "Primary role set to {$role} for {$user->first_name} {$user->last_name}"
        ]);
    }

    /**
     * Get user statistics
     */
    public function getStats()
    {
        $stats = [
            'total_users' => User::count(),
            'admins' => User::whereHas('roles', function($query) {
                $query->where('name', 'admin');
            })->count(),
            'reviewers' => User::whereHas('roles', function($query) {
                $query->where('name', 'reviewer');
            })->count(),
            'scientific_admins' => User::whereHas('roles', function($query) {
                $query->where('name', 'scientific_admin');
            })->count(),
            'authors' => User::whereHas('roles', function($query) {
                $query->where('name', 'author');
            })->count(),
            'verified_users' => User::whereNotNull('email_verified_at')->count(),
            'recent_registrations' => User::where('created_at', '>=', now()->subDays(7))->count(),
        ];
        
        return response()->json($stats);
    }

    /**
     * Search users
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        $role = $request->get('role');
        
        $users = User::with('roles')
            ->when($query, function($q) use ($query) {
                $q->where(function($subQ) use ($query) {
                    $subQ->where('first_name', 'like', "%{$query}%")
                          ->orWhere('last_name', 'like', "%{$query}%")
                          ->orWhere('email', 'like', "%{$query}%")
                          ->orWhere('affiliation', 'like', "%{$query}%");
                });
            })
            ->when($role, function($q) use ($role) {
                $q->whereHas('roles', function($subQ) use ($role) {
                    $subQ->where('name', $role);
                });
            })
            ->orderBy('first_name')
            ->paginate(20);
            
        return response()->json($users);
    }

    /**
     * Get users by role
     */
    public function getByRole($role)
    {
        $users = User::whereHas('roles', function($query) use ($role) {
            $query->where('name', $role);
        })
        ->with('roles')
        ->orderBy('first_name')
        ->get();
        
        return response()->json($users);
    }

    /**
     * Export users list
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'csv');
        $role = $request->get('role');
        
        $query = User::with('roles');
        
        if ($role) {
            $query->whereHas('roles', function($q) use ($role) {
                $q->where('name', $role);
            });
        }
        
        $users = $query->get();
        
        if ($format === 'json') {
            return response()->json($users);
        }
        
        // CSV export
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users.csv"',
        ];
        
        $callback = function() use ($users) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, ['ID', 'Name', 'Email', 'Affiliation', 'Roles', 'Verified', 'Created']);
            
            // Data
            foreach ($users as $user) {
                $roles = $user->roles->pluck('name')->implode(', ');
                fputcsv($file, [
                    $user->id,
                    $user->first_name . ' ' . $user->last_name,
                    $user->email,
                    $user->affiliation,
                    $roles,
                    $user->email_verified_at ? 'Yes' : 'No',
                    $user->created_at->format('Y-m-d H:i:s')
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    /**
     * Manually set a user's payment status.
     */
    public function setPaymentStatus(Request $request, User $user)
    {
        $request->validate([
            'payment_status' => 'required|in:pending,submitted,verified,rejected,waived',
            'payment_notes'  => 'nullable|string|max:500',
        ]);

        $old = $user->payment_status;
        $user->update([
            'payment_status' => $request->payment_status,
            'payment_notes'  => $request->filled('payment_notes')
                                    ? $request->payment_notes
                                    : $user->payment_notes,
        ]);

        \Illuminate\Support\Facades\Log::info('Admin manually set payment status', [
            'user_id'    => $user->id,
            'from'       => $old,
            'to'         => $request->payment_status,
            'admin_id'   => Auth::id(),
            'notes'      => $request->payment_notes,
        ]);

        return redirect()->route('admin.users.edit', $user)
            ->with('success', "{$user->full_name}'s payment status updated to {$request->payment_status}.");
    }

    /**
     * Delete a user
     */
    public function destroy(User $user)
    {
        // Prevent admin from deleting themselves
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        // Optional: Check if user has critical data that prevents deletion
        // or handle cascading deletes if not set in DB
        
        $userName = $user->first_name . ' ' . $user->last_name;
        $user->delete();

        return redirect()->route('admin.users')
            ->with('success', "User {$userName} has been deleted successfully.");
    }

    private function syncCurrentUserActiveRole(User $user): void
    {
        if (!Auth::check() || Auth::id() !== $user->id) {
            return;
        }

        $sessionRole = Session::get('active_role');

        if ($sessionRole && $user->hasRole($sessionRole)) {
            return;
        }

        Session::put('active_role', $user->primary_role_name ?? $user->role ?? 'author');
    }
} 
