<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvitationLetter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvitationManagementController extends Controller
{
    public function index()
    {
        $invitations = InvitationLetter::with('user')->latest()->paginate(20);
        return view('admin.invitations.index', compact('invitations'));
    }

    // Approval logic removed as invitations are now automated.
}
