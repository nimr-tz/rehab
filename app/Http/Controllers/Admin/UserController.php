<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $role = $request->query('role');
        $search = trim((string) $request->query('q'));

        $users = User::query()
            ->with('roles')
            ->when($role, fn ($q) => $q->role($role))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->orderBy('last_name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => Role::cases(),
            'filters' => compact('role', 'search'),
        ]);
    }

    public function roles(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'roles' => ['array'],
            'roles.*' => [Rule::enum(Role::class)],
        ]);

        $roles = $data['roles'] ?? [];

        // An admin cannot remove their own admin role and lock everyone out.
        if ($user->is($request->user()) && ! in_array(Role::Admin->value, $roles, true)) {
            return back()->withErrors(['roles' => 'You cannot remove your own administrator role.']);
        }

        $user->syncRoles($roles);

        return back()->with('status', 'Roles updated for '.$user->name.'.');
    }
}
