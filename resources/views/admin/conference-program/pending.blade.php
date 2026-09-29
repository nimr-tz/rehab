@extends('layouts.app')

@section('title', 'Pending Conference Codes')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="bg-gradient-to-r from-yellow-600 to-orange-700 dark:from-yellow-700 dark:to-orange-800 rounded-xl shadow-lg p-8 text-white mb-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <svg class="w-8 h-8 text-white mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <h1 class="text-3xl font-bold">✏️ Assign Conference Codes</h1>
                    <p class="text-yellow-100 dark:text-yellow-200 mt-1">Abstracts waiting for conference code assignment • Filter by subtheme</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <a href="{{ route('admin.conference-program.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-white bg-opacity-20 text-white rounded-lg hover:bg-opacity-30 transition-colors backdrop-blur-sm">
                    ← Back to Overview
                </a>
            </div>
        </div>
        <div class="mt-4 flex items-center space-x-6 text-yellow-100 dark:text-yellow-200">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                Quick Assignment Tools
            </div>
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Smart Filtering
            </div>
        </div>
    </div>

    <!-- Enhanced Search and Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">🔍 Search & Filter Abstracts</h3>
            <form method="GET" class="space-y-4">
                <!-- Search Bar -->
                <div class="flex gap-4">
                    <div class="flex-1">
                        <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                        <input type="text" 
                               id="search"
                               name="search" 
                               value="{{ request('search') }}"
                               placeholder="🔍 Search by title, author, institution, or keywords..."
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400">
                    </div>
                </div>
                
                <!-- Filter Row -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Subtheme Filter -->
                    <div>
                        <label for="subtheme" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subtheme</label>
                        <select name="subtheme" 
                                id="subtheme"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400">
                            <option value="">All Subthemes</option>
                            @foreach(array_keys(config('conference.subtheme_prefixes', [])) as $subtheme)
                                <option value="{{ $subtheme }}" {{ request('subtheme') == $subtheme ? 'selected' : '' }}>{{ $subtheme }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Sort By -->
                    <div>
                        <label for="sort" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sort By</label>
                        <select name="sort" 
                                id="sort"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400">
                            <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>📅 Submission Date</option>
                            <option value="title" {{ request('sort') == 'title' ? 'selected' : '' }}>📝 Title (A-Z)</option>
                            <option value="author_name" {{ request('sort') == 'author_name' ? 'selected' : '' }}>👤 Author Name</option>
                            <option value="subtheme" {{ request('sort') == 'subtheme' ? 'selected' : '' }}>🏷️ Subtheme</option>
                            <option value="reviewer_avg" {{ request('sort') == 'reviewer_avg' ? 'selected' : '' }}>⭐ Review Score</option>
                        </select>
                    </div>
                    
                    <!-- Order -->
                    <div>
                        <label for="order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Order</label>
                        <select name="order" 
                                id="order"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400">
                            <option value="desc" {{ request('order') == 'desc' ? 'selected' : '' }}>📉 Descending</option>
                            <option value="asc" {{ request('order') == 'asc' ? 'selected' : '' }}>📈 Ascending</option>
                        </select>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex gap-3">
                    <button type="submit" 
                            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg transition-colors font-medium">
                        🔍 Apply Filters
                    </button>
                    @if(request('search') || request('subtheme') || request('sort') || request('order'))
                    <a href="{{ route('admin.conference-program.pending') }}" 
                       class="px-6 py-2 bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 text-white rounded-lg transition-colors font-medium">
                        🔄 Reset All
                    </a>
                    @endif
                    <button type="button" 
                            onclick="window.location.href='{{ route('admin.conference-program.pending') }}?subtheme={{ urlencode(request('subtheme', '')) }}&bulk_assign=sequential'"
                            class="px-6 py-2 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white rounded-lg transition-colors font-medium">
                        ⚡ Bulk Assign Sequential
                    </button>
                </div>
                
                @if(request('subtheme') || request('search'))
                <div class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                    <span class="font-medium">Active Filters:</span>
                    @if(request('subtheme'))
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 ml-2">
                            🏷️ {{ request('subtheme') }}
                        </span>
                    @endif
                    @if(request('search'))
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 ml-2">
                            🔍 "{{ request('search') }}"
                        </span>
                    @endif
                </div>
                @endif
            </form>
        </div>

        @if($abstracts->count() == 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
                <div class="text-gray-400 dark:text-gray-500 mb-4">
                    <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">🎉 All Done!</h3>
                <p class="text-gray-600 dark:text-gray-400">All abstracts have been assigned conference codes.</p>
            </div>
        @else
            <!-- Abstracts List -->
            <div class="space-y-4">
                @foreach($abstracts as $abstract)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex justify-between items-start">
                        <div class="flex-1 pr-4">
                            <!-- Abstract Info -->
                            <div class="mb-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                    {{ $abstract->title }}
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600 dark:text-gray-400">
                                    <div>
                                        <span class="font-medium">👤 Author:</span><br>
                                        {{ \App\Support\TitleFormatter::personName($abstract->author_name) }}
                                    </div>
                                    <div>
                                        <span class="font-medium">🏛️ Institution:</span><br>
                                        {{ $abstract->author_institute }}
                                    </div>
                                    <div>
                                        <span class="font-medium">📚 Theme:</span><br>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                            {{ $abstract->subtheme }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Conference Code Assignment Form -->
                            <form id="assignForm{{ $abstract->id }}" class="border-t border-gray-200 dark:border-gray-600 pt-4">
                                @csrf
                                @method('PATCH')
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            📝 Conference Code
                                        </label>
                                        <input type="text" 
                                               name="conference_code" 
                                               id="code{{ $abstract->id }}"
                                               placeholder="e.g., MED-001, TECH-002"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 rounded-md focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400"
                                               required>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Suggested: {{ strtoupper(substr($abstract->subtheme, 0, 3)) }}-{{ str_pad($abstract->id, 3, '0', STR_PAD_LEFT) }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            💭 Committee Notes (Optional)
                                        </label>
                                        <input type="text" 
                                               name="committee_notes" 
                                               placeholder="Notes for this abstract..."
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 rounded-md focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400">
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-between mt-4">
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               name="committee_selected" 
                                               value="1"
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded">
                                        <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                            ⭐ Select for Conference Program
                                        </span>
                                    </label>
                                    
                                    <button type="button" 
                                            onclick="assignCode({{ $abstract->id }})"
                                            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white rounded-md transition-colors">
                                        💾 Assign Code
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex flex-col gap-2">
                            <a href="{{ route('admin.abstracts.view', $abstract) }}" 
                               class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white text-sm rounded-md transition-colors">
                                👁️ View Details
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $abstracts->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function assignCode(abstractId) {
    const form = document.getElementById(`assignForm${abstractId}`);
    const formData = new FormData(form);
    const submitButton = form.querySelector('button[type="button"]');
    
    // Get the conference code value
    const conferenceCodeInput = form.querySelector('input[name="conference_code"]');
    const conferenceCode = conferenceCodeInput.value.trim();
    
    // Simple client-side validation
    if (!conferenceCode) {
        showError(form, 'Please enter a conference code.');
        return;
    }
    
    // Disable button and show loading
    submitButton.disabled = true;
    submitButton.innerHTML = '⏳ Assigning...';
    
    // Clear any previous error messages
    clearMessages(form);
    
    fetch(`/admin/abstracts/${abstractId}/assign-code`, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            conference_code: conferenceCode,
            committee_notes: form.querySelector('input[name="committee_notes"]').value,
            committee_selected: form.querySelector('input[name="committee_selected"]').checked ? 1 : 0
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => Promise.reject(err));
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showSuccess(form, data.message);
            // Hide the form after successful assignment
            setTimeout(() => {
                form.style.display = 'none';
                // Optionally reload the page to update the list
                setTimeout(() => window.location.reload(), 1000);
            }, 2000);
        } else {
            throw new Error(data.message || 'An error occurred');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        // Handle validation errors
        if (error.errors) {
            let errorMessage = 'Validation errors:\n';
            Object.keys(error.errors).forEach(field => {
                errorMessage += `- ${error.errors[field].join(', ')}\n`;
            });
            showError(form, errorMessage);
        } else {
            showError(form, error.message || 'An unexpected error occurred. Please try again.');
        }
    })
    .finally(() => {
        // Re-enable button
        submitButton.disabled = false;
        submitButton.innerHTML = '💾 Assign Code';
    });
}

function showSuccess(form, message) {
    clearMessages(form);
    const successDiv = document.createElement('div');
    successDiv.className = 'bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-300 px-4 py-3 rounded-md mb-4 success-message';
    successDiv.innerHTML = '✅ ' + message;
    form.parentNode.insertBefore(successDiv, form);
}

function showError(form, message) {
    clearMessages(form);
    const errorDiv = document.createElement('div');
    errorDiv.className = 'bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 px-4 py-3 rounded-md mb-4 error-message';
    errorDiv.innerHTML = '❌ ' + message;
    form.parentNode.insertBefore(errorDiv, form);
}

function clearMessages(form) {
    // Remove any existing success or error messages
    const messages = form.parentNode.querySelectorAll('.success-message, .error-message');
    messages.forEach(msg => msg.remove());
}
</script>
@endpush
@endsection
