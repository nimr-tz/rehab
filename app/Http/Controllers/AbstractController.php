<?php

namespace App\Http\Controllers;

use App\Enums\PresentationType;
use App\Models\AbstractSubmission;
use App\Services\AbstractService;
use App\Support\Summit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AbstractController extends Controller
{
    public function __construct(private Summit $summit) {}

    public function index(Request $request): View
    {
        $edition = $this->summit->edition();

        return view('abstracts.index', [
            'edition' => $edition,
            'abstracts' => $edition
                ? $request->user()->abstracts()->where('edition_id', $edition->id)->with('topic')->latest()->get()
                : collect(),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $edition = $this->summit->edition();

        if (! $edition?->acceptsAbstracts()) {
            return redirect()->route('abstracts.index')->with('status', 'Abstract submission is closed.');
        }

        $user = $request->user();

        return view('abstracts.form', [
            'edition' => $edition,
            'abstract' => null,
            'authors' => [['name' => $user->name, 'email' => $user->email, 'affiliation' => (string) $user->institution]],
            'presenter' => 0,
        ]);
    }

    public function store(Request $request, AbstractService $service): RedirectResponse
    {
        $edition = $this->summit->edition();
        abort_unless($edition?->acceptsAbstracts(), 403, 'Abstract submission is closed.');

        $submit = $request->input('action') === 'submit';
        $abstract = $service->save($request->user(), $edition, $this->validated($request, $submit), $submit);

        return redirect()->route('abstracts.show', $abstract)
            ->with('status', $submit ? 'Your abstract has been submitted.' : 'Draft saved. Submit it before the deadline.');
    }

    public function show(Request $request, AbstractSubmission $abstract): View
    {
        $this->authorizeOwner($request, $abstract);

        return view('abstracts.show', [
            'abstract' => $abstract->load('topic', 'authors', 'edition', 'reviews', 'sessions'),
        ]);
    }

    public function edit(Request $request, AbstractSubmission $abstract): View|RedirectResponse
    {
        $this->authorizeOwner($request, $abstract);

        if (! $abstract->status->isEditable() || ! $abstract->edition->acceptsAbstracts()) {
            return redirect()->route('abstracts.show', $abstract)->with('status', 'This abstract can no longer be edited.');
        }

        $abstract->load('authors');

        return view('abstracts.form', [
            'edition' => $abstract->edition,
            'abstract' => $abstract,
            'authors' => $abstract->authors->map->only('name', 'email', 'affiliation')->all(),
            'presenter' => max(0, (int) $abstract->authors->search(fn ($author) => $author->is_presenter)),
        ]);
    }

    public function update(Request $request, AbstractSubmission $abstract, AbstractService $service): RedirectResponse
    {
        $this->authorizeOwner($request, $abstract);
        abort_unless($abstract->status->isEditable() && $abstract->edition->acceptsAbstracts(), 403, 'This abstract can no longer be edited.');

        $submit = $request->input('action') === 'submit' || $abstract->status->value === 'submitted';
        $service->save($request->user(), $abstract->edition, $this->validated($request, $submit), $submit, $abstract);

        return redirect()->route('abstracts.show', $abstract)
            ->with('status', $submit ? 'Your abstract has been saved and submitted.' : 'Draft saved.');
    }

    public function withdraw(Request $request, AbstractSubmission $abstract, AbstractService $service): RedirectResponse
    {
        $this->authorizeOwner($request, $abstract);
        abort_unless($abstract->status->isEditable(), 403);

        $service->withdraw($abstract);

        return redirect()->route('abstracts.index')->with('status', 'The abstract has been withdrawn.');
    }

    private function authorizeOwner(Request $request, AbstractSubmission $abstract): void
    {
        abort_unless($abstract->user_id === $request->user()->id, 404);
    }

    /** Drafts may be incomplete; submissions must be complete and within the word limit. */
    private function validated(Request $request, bool $submit): array
    {
        $required = $submit ? 'required' : 'nullable';
        $edition = $this->summit->edition();

        $data = $request->validate([
            'topic_id' => ['required', Rule::exists('topics', 'id')->where('edition_id', $edition->id)],
            'preferred_type' => ['required', Rule::enum(PresentationType::class)],
            'title' => ['required', 'string', 'max:200'],
            'background' => [$required, 'string', 'max:3000'],
            'methods' => [$required, 'string', 'max:3000'],
            'results' => [$required, 'string', 'max:3000'],
            'conclusions' => [$required, 'string', 'max:3000'],
            'keywords' => ['nullable', 'string', 'max:255'],
            'authors' => ['required', 'array', 'min:1', 'max:12'],
            'authors.*.name' => ['required', 'string', 'max:150'],
            'authors.*.email' => ['nullable', 'email', 'max:255'],
            'authors.*.affiliation' => ['required', 'string', 'max:255'],
            'presenter' => ['required', 'integer', 'min:0'],
        ], [
            'authors.*.name.required' => 'Every author needs a name.',
            'authors.*.affiliation.required' => 'Every author needs an affiliation.',
        ]);

        foreach (['background', 'methods', 'results', 'conclusions'] as $field) {
            $data[$field] = $data[$field] ?? '';
        }

        $words = AbstractSubmission::countWords(implode(' ', [$data['background'], $data['methods'], $data['results'], $data['conclusions']]));

        if ($submit && $words > AbstractSubmission::WORD_LIMIT) {
            throw ValidationException::withMessages([
                'background' => "The abstract has {$words} words. The limit is ".AbstractSubmission::WORD_LIMIT.'.',
            ]);
        }

        $data['presenter'] = min((int) $data['presenter'], count($data['authors']) - 1);

        return $data;
    }
}
