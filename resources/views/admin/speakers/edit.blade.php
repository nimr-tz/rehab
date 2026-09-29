@extends('layouts.app')

@section('title', isset($speaker) ? 'Edit Speaker' : 'Add Speaker')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-8">
            <a href="{{ route('admin.speakers.index') }}" class="inline-flex items-center text-sm text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 mb-4">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Speakers
            </a>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white">
                {{ isset($speaker) ? 'Edit Speaker' : 'Add New Speaker' }}
            </h1>
            <p class="text-slate-600 dark:text-slate-400 mt-2">{{ isset($speaker) ? 'Update speaker information' : 'Add a keynote or featured speaker for the mobile app' }}</p>
        </div>

        <!-- Form -->
        <form action="{{ isset($speaker) ? route('admin.speakers.update', $speaker) : route('admin.speakers.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @if(isset($speaker))
                @method('PUT')
            @endif

            <!-- Photo Upload -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Speaker Photo</h3>
                
                <div class="flex items-start gap-6">
                    <!-- Photo Preview -->
                    <div class="flex-shrink-0">
                        <div id="photo-preview" class="w-32 h-32 rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-700 border-2 border-dashed border-gray-300 dark:border-gray-600">
                            @if(isset($speaker) && $speaker->photo_path)
                                <img src="{{ $speaker->photo_url }}" alt="Speaker photo" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Upload Input -->
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Upload Photo</label>
                        <input type="file" name="photo" id="photo" accept="image/*" class="block w-full text-sm text-slate-500 dark:text-slate-400
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-lg file:border-0
                            file:text-sm file:font-semibold
                            file:bg-indigo-50 file:text-indigo-700
                            hover:file:bg-indigo-100
                            dark:file:bg-indigo-900/20 dark:file:text-indigo-400
                            dark:hover:file:bg-indigo-900/30
                            cursor-pointer">
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">JPG, PNG or JPEG. Max 2MB.</p>
                        @error('photo')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        
                        @if(isset($speaker) && $speaker->photo_path)
                            <div class="mt-4">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="remove_photo" value="1" class="rounded border-gray-300 text-red-600 shadow-sm focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-red-600 dark:text-red-400 font-medium">Remove current photo</span>
                                </label>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Basic Information -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Basic Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Title (Prof., Dr., etc.) -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Title (e.g., Prof., Dr., Hon.)</label>
                        <select name="title" id="title"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                            <option value="" {{ old('title', $speaker->title ?? '') == '' ? 'selected' : '' }}>No Title</option>
                            <option value="Prof." {{ old('title', $speaker->title ?? '') == 'Prof.' ? 'selected' : '' }}>Prof.</option>
                            <option value="Dr." {{ old('title', $speaker->title ?? '') == 'Dr.' ? 'selected' : '' }}>Dr.</option>
                            <option value="Hon." {{ old('title', $speaker->title ?? '') == 'Hon.' ? 'selected' : '' }}>Hon.</option>
                            <option value="Mr." {{ old('title', $speaker->title ?? '') == 'Mr.' ? 'selected' : '' }}>Mr.</option>
                            <option value="Mrs." {{ old('title', $speaker->title ?? '') == 'Mrs.' ? 'selected' : '' }}>Mrs.</option>
                            <option value="Ms." {{ old('title', $speaker->title ?? '') == 'Ms.' ? 'selected' : '' }}>Ms.</option>
                            <option value="Sir" {{ old('title', $speaker->title ?? '') == 'Sir' ? 'selected' : '' }}>Sir</option>
                            <option value="Dame" {{ old('title', $speaker->title ?? '') == 'Dame' ? 'selected' : '' }}>Dame</option>
                        </select>
                        @error('title')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Full Name *</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $speaker->name ?? '') }}" required
                            class="w-full px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $speaker->email ?? '') }}"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Type -->
                    <div>
                        <label for="type" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Speaker Type *</label>
                        <select name="type" id="type" required
                            class="w-full px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                            <option value="keynote" {{ old('type', $speaker->type ?? '') == 'keynote' ? 'selected' : '' }}>Keynote</option>
                            <option value="plenary" {{ old('type', $speaker->type ?? '') == 'plenary' ? 'selected' : '' }}>Plenary</option>
                            <option value="vip" {{ old('type', $speaker->type ?? '') == 'vip' ? 'selected' : '' }}>VIP</option>
                            <option value="guest_of_honor" {{ old('type', $speaker->type ?? '') == 'guest_of_honor' ? 'selected' : '' }}>Guest of Honor</option>
                            <option value="distinguished" {{ old('type', $speaker->type ?? '') == 'distinguished' ? 'selected' : '' }}>Distinguished Guest</option>
                            <option value="chief_guest" {{ old('type', $speaker->type ?? '') == 'chief_guest' ? 'selected' : '' }}>Chief Guest</option>
                            <option value="special" {{ old('type', $speaker->type ?? '') == 'special' ? 'selected' : '' }}>Special Guest</option>
                            <option value="invited" {{ old('type', $speaker->type ?? '') == 'invited' ? 'selected' : '' }}>Invited Speaker</option>
                            <option value="panelist" {{ old('type', $speaker->type ?? '') == 'panelist' ? 'selected' : '' }}>Panelist</option>
                        </select>
                        @error('type')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Position -->
                    <div>
                        <label for="position" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Position/Title</label>
                        <input type="text" name="position" id="position" value="{{ old('position', $speaker->position ?? '') }}"
                            placeholder="e.g., Professor, Director"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                        @error('position')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Affiliation -->
                    <div>
                        <label for="affiliation" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Affiliation/Organization</label>
                        <input type="text" name="affiliation" id="affiliation" value="{{ old('affiliation', $speaker->affiliation ?? '') }}"
                            placeholder="e.g., MIT, Harvard University"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                        @error('affiliation')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Bio -->
                    <div class="md:col-span-2">
                        <label for="bio" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Biography</label>
                        <textarea name="bio" id="bio" rows="4"
                            placeholder="Brief biography about the speaker..."
                            class="w-full px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white">{{ old('bio', $speaker->bio ?? '') }}</textarea>
                        @error('bio')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Social Links -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Social Links</h3>
                
                <div class="space-y-4">
                    <!-- Twitter -->
                    <div>
                        <label for="twitter" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Twitter Handle</label>
                        <div class="flex">
                            <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-slate-300 dark:border-gray-600 bg-slate-50 dark:bg-gray-700 text-slate-500 dark:text-slate-400 text-sm">@</span>
                            <input type="text" name="twitter" id="twitter" value="{{ old('twitter', $speaker->social_links['twitter'] ?? '') }}"
                                placeholder="username"
                                class="flex-1 px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-r-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                        </div>
                        @error('twitter')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- LinkedIn -->
                    <div>
                        <label for="linkedin" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">LinkedIn URL</label>
                        <input type="url" name="linkedin" id="linkedin" value="{{ old('linkedin', $speaker->social_links['linkedin'] ?? '') }}"
                            placeholder="https://linkedin.com/in/username"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                        @error('linkedin')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Website -->
                    <div>
                        <label for="website" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Website</label>
                        <input type="url" name="website" id="website" value="{{ old('website', $speaker->social_links['website'] ?? '') }}"
                            placeholder="https://example.com"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                        @error('website')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Display Settings -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Display Settings</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Display Order -->
                    <div>
                        <label for="display_order" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Display Order</label>
                        <input type="number" name="display_order" id="display_order" value="{{ old('display_order', $speaker->display_order ?? 0) }}" min="0"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Lower numbers appear first</p>
                        @error('display_order')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Active Status -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Status</label>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $speaker->is_active ?? true) ? 'checked' : '' }}
                                class="sr-only peer">
                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                            <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">Active (visible in mobile app)</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-4">
                <a href="{{ route('admin.speakers.index') }}" class="px-6 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-bold rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                    {{ isset($speaker) ? 'Update Speaker' : 'Add Speaker' }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Photo preview
document.getElementById('photo').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('photo-preview').innerHTML = `<img src="${e.target.result}" alt="Preview" class="w-full h-full object-cover">`;
        }
        reader.readAsDataURL(file);
    }
});
</script>
@endsection
