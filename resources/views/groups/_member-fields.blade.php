{{--
    One person's details. $prefix is the field name prefix: "members[0]" in the
    group form, or empty when adding one person. Inside the Alpine row template
    of the group form, $alpine names the row variable instead.
--}}
@php
    $name = fn (string $field) => $prefix === '' ? $field : $prefix.'['.$field.']';
    $old = fn (string $field, $default = null) => old($prefix === '' ? $field : str_replace(['[', ']'], ['.', ''], $prefix).'.'.$field, $default);
    $error = fn (string $field) => $errors->first($prefix === '' ? $field : str_replace(['[', ']'], ['.', ''], $prefix).'.'.$field);
@endphp

<div class="grid gap-3 sm:grid-cols-6">
    <div class="sm:col-span-1">
        <label class="label">Title</label>
        <select name="{{ $name('title') }}" class="field">
            <option value="">—</option>
            @foreach ($titles as $title)
                <option value="{{ $title }}" @selected($old('title') === $title)>{{ $title }}</option>
            @endforeach
        </select>
    </div>
    @foreach ([['first_name', 'First name', 'sm:col-span-2'], ['last_name', 'Last name', 'sm:col-span-3'], ['email', 'Email', 'sm:col-span-3'], ['phone', 'Phone', 'sm:col-span-3'], ['institution', 'Institution', 'sm:col-span-3'], ['profession', 'Profession', 'sm:col-span-3']] as [$field, $label, $span])
        <div class="{{ $span }}">
            <label class="label">{{ $label }}</label>
            <input name="{{ $name($field) }}" value="{{ $old($field) }}" type="{{ $field === 'email' ? 'email' : ($field === 'phone' ? 'tel' : 'text') }}" required class="field @if ($error($field)) border-red-400 @endif">
            @if ($message = $error($field)) <p class="mt-1 text-xs font-medium text-red-700">{{ $message }}</p> @endif
        </div>
    @endforeach
    <div class="sm:col-span-3">
        <label class="label">Country</label>
        <select name="{{ $name('country') }}" class="field" required>
            @foreach ($countries as $code => $country)
                <option value="{{ $code }}" @selected($old('country', 'TZ') === $code)>{{ $country }}</option>
            @endforeach
        </select>
    </div>
    <div class="sm:col-span-3">
        <label class="label">Category</label>
        <select name="{{ $name('category') }}" class="field" required>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((int) $old('category') === $category->id)>{{ $category->name }} · {{ $category->formattedFee() }}</option>
            @endforeach
        </select>
        @if ($message = $error('category')) <p class="mt-1 text-xs font-medium text-red-700">{{ $message }}</p> @endif
    </div>
    <div class="sm:col-span-3">
        <label class="label">Dietary needs (optional)</label>
        <input name="{{ $name('dietary_needs') }}" value="{{ $old('dietary_needs') }}" class="field" placeholder="e.g. Vegetarian">
    </div>
    <div class="sm:col-span-3">
        <label class="label">Accessibility needs (optional)</label>
        <input name="{{ $name('accessibility_needs') }}" value="{{ $old('accessibility_needs') }}" class="field" placeholder="e.g. Wheelchair access">
    </div>
</div>
