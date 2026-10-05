<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Support\Summit;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParticipantController extends Controller
{
    public function index(Request $request, Summit $summit): View
    {
        $edition = $summit->edition();
        $status = $request->query('status');
        $category = $request->query('category');
        $search = trim((string) $request->query('q'));

        $registrations = Registration::query()
            ->where('edition_id', $edition?->id)
            ->with('user', 'category')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($category, fn ($q) => $q->where('registration_category_id', $category))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('reference', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($q) => $q
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('institution', 'like', "%{$search}%"))))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.participants.index', [
            'registrations' => $registrations,
            'categories' => $edition?->categories ?? collect(),
            'statuses' => RegistrationStatus::cases(),
            'filters' => compact('status', 'category', 'search'),
        ]);
    }

    public function show(Registration $registration): View
    {
        $registration->load('user', 'category', 'payments.reviewer', 'edition');

        return view('admin.participants.show', [
            'registration' => $registration,
            'abstracts' => $registration->user->abstracts()->where('edition_id', $registration->edition_id)->with('topic')->get(),
        ]);
    }
}
