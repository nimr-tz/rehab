<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Switches the role a person is working in, from the sidebar. */
class WorkspaceController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate(['role' => ['required', Rule::enum(Role::class)]]);
        $role = Role::from($data['role']);

        abort_unless(Workspace::switchTo($request->user(), $role), 403);

        return redirect()->route('dashboard')->with('status', 'You are now working as '.Workspace::label($role).'.');
    }
}
