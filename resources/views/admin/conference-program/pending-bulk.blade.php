@extends('layouts.app')

@section('title', 'Bulk Code Assignment')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-[95%] mx-auto px-4">
        
        <!-- Compact Header -->
        <div class="bg-gradient-to-r from-yellow-500 to-orange-600 rounded-lg shadow-lg p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-white mb-1">⚡ Bulk Code Assignment</h1>
                    <p class="text-yellow-100 text-sm">Assign conference codes to {{ $totalPending }} accepted abstracts</p>
                </div>
                <div class="flex gap-3">
                    <button onclick="autoAssignAll()" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg backdrop-blur-sm transition-colors font-medium">
                        🤖 Auto-Assign All
                    </button>
                    <button onclick="saveAllCodes()" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors font-medium shadow-lg">
                        💾 Save All Codes
                    </button>
                    <a href="{{ route('admin.conference-program.index') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg backdrop-blur-sm transition-colors">
                        ← Back
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Stats & Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-4">
            <div class="flex items-center justify-between gap-4">
                <!-- Stats -->
                <div class="flex gap-6 text-sm">
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Total Pending:</span>
                        <span class="font-bold text-gray-900 dark:text-white ml-1">{{ $totalPending }}</span>
                    </div>
                </div>

                <!-- Filters -->
                <div class="flex gap-3">
                    <select id="subthemeFilter" onchange="filterTable()" class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                        <option value="">All Subthemes ({{ $totalPending }})</option>
                        @foreach($subthemeCounts as $subtheme => $count)
                            <option value="{{ $subtheme }}">{{ $subtheme }} ({{ $count }})</option>
                        @endforeach
                    </select>
                    
                    <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="🔍 Search title/author..." 
                           class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg w-64">
                </div>
            </div>
        </div>

        <!-- Compact Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto" style="max-height: calc(100vh - 280px);">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="abstractsTable">
                    <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0 z-10">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 w-12">#</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 w-32">Code</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 dark:text-gray-300">Title</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 w-40">Author</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 w-56">Subtheme</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 w-32">Presentation Mode</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 w-20">Select</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($abstracts as $index => $abstract)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors abstract-row" 
                            data-id="{{ $abstract->id }}"
                            data-subtheme="{{ $abstract->subtheme }}"
                            data-title="{{ strtolower($abstract->title) }}"
                            data-author="{{ strtolower($abstract->author_name) }}">
                            
                            <!-- Row Number -->
                            <td class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                                {{ $index + 1 }}
                            </td>
                            
                            <!-- Code Input -->
                            <td class="px-3 py-2">
                                <input type="text" 
                                       class="code-input w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono uppercase"
                                       data-abstract-id="{{ $abstract->id }}"
                                       placeholder="{{ strtoupper(substr($abstract->subtheme, 0, 3)) }}-{{ str_pad($index + 1, 3, '0', STR_PAD_LEFT) }}"
                                       value=""
                                       onchange="updateCount()">
                            </td>
                            
                            <!-- Title -->
                            <td class="px-3 py-2">
                                <div class="text-sm font-medium text-gray-900 dark:text-white line-clamp-2">
                                    {{ $abstract->title }}
                                </div>
                            </td>
                            
                            <!-- Author -->
                            <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400">
                                {{ \App\Support\TitleFormatter::personName($abstract->author_name) }}
                            </td>
                            
                            <!-- Subtheme Dropdown -->
                            <td class="px-3 py-2">
                                <select class="subtheme-select w-full px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        data-abstract-id="{{ $abstract->id }}">
                                    @foreach($allSubthemes as $subtheme)
                                        <option value="{{ $subtheme }}" {{ $abstract->subtheme === $subtheme ? 'selected' : '' }}>
                                            {{ $subtheme }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            
                            <!-- Presentation Mode Dropdown -->
                            <td class="px-3 py-2">
                                <select class="mode-select w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        data-abstract-id="{{ $abstract->id }}">
                                    <option value="Oral" {{ $abstract->presentation_mode === 'Oral' ? 'selected' : '' }}>🎤 Oral</option>
                                    <option value="Poster" {{ $abstract->presentation_mode === 'Poster' ? 'selected' : '' }}>📋 Poster</option>
                                </select>
                            </td>
                            
                            <!-- Select for Program -->
                            <td class="px-3 py-2 text-center">
                                <input type="checkbox" 
                                       class="program-select w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500"
                                       data-abstract-id="{{ $abstract->id }}">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Bottom Actions -->
        <div class="mt-4 flex items-center justify-between">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <span class="font-medium">Tip:</span> Use Tab key to quickly move between code inputs. Codes are auto-uppercased.
            </div>
            <div class="flex gap-3">
                <button onclick="clearAll()" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors">
                    🗑️ Clear All
                </button>
                <button onclick="saveAllCodes()" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors font-medium shadow-lg">
                    💾 Save All Codes (0)
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Auto-Assign Confirmation Modal -->
<div id="autoAssignModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-md w-full transform transition-all">
        <div class="p-6">
            <div class="flex items-center justify-center w-16 h-16 mx-auto bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white text-center mb-2">
                Auto-Assign Conference Codes
            </h3>
            <p class="text-gray-600 dark:text-gray-400 text-center mb-6">
                This will automatically generate codes for all <span class="font-bold text-blue-600 dark:text-blue-400" id="modalAbstractCount">{{ $totalPending }}</span> abstracts based on their subtheme.
            </p>
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-6">
                <p class="text-sm text-blue-800 dark:text-blue-300">
                    <span class="font-semibold">Format:</span> SUBTHEME-001, SUBTHEME-002, etc.<br>
                    <span class="font-semibold">Example:</span> AI-001, CYB-001, DAT-001<br>
                    <span class="text-xs mt-1 block">You can edit any code before saving.</span>
                </p>
            </div>
            <div class="flex gap-3">
                <button onclick="closeAutoAssignModal()" class="flex-1 px-4 py-2.5 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-lg font-medium transition-colors">
                    Cancel
                </button>
                <button onclick="confirmAutoAssign()" class="flex-1 px-4 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-lg font-medium transition-colors shadow-lg">
                    ⚡ Auto-Assign
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Save Confirmation Modal -->
<div id="saveModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-md w-full transform transition-all">
        <div class="p-6">
            <div class="flex items-center justify-center w-16 h-16 mx-auto bg-gradient-to-br from-green-500 to-emerald-600 rounded-full mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white text-center mb-2">
                Save Conference Codes
            </h3>
            <p class="text-gray-600 dark:text-gray-400 text-center mb-6">
                You are about to save <span class="font-bold text-green-600 dark:text-green-400" id="modalSaveCount">0</span> conference codes.
            </p>
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-6">
                <p class="text-sm text-blue-800 dark:text-blue-300">
                    <span class="font-semibold">💡 Note:</span> Please review all codes before saving. Codes can be updated later if needed.
                </p>
            </div>
            <div class="flex gap-3">
                <button onclick="closeSaveModal()" class="flex-1 px-4 py-2.5 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-lg font-medium transition-colors">
                    Review Again
                </button>
                <button onclick="confirmSave()" class="flex-1 px-4 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-lg font-medium transition-colors shadow-lg">
                    💾 Confirm Save
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const totalPending = {{ $totalPending }};

// Show auto-assign modal
function autoAssignAll() {
    document.getElementById('autoAssignModal').classList.remove('hidden');
}

// Close auto-assign modal
function closeAutoAssignModal() {
    document.getElementById('autoAssignModal').classList.add('hidden');
}

// Confirm auto-assign
function confirmAutoAssign() {
    closeAutoAssignModal();
    
    const rows = document.querySelectorAll('.abstract-row');
    const subthemeCounts = {};
    
    rows.forEach((row, index) => {
        const input = row.querySelector('.code-input');
        const subtheme = row.dataset.subtheme;
        const prefix = subtheme.substring(0, 3).toUpperCase();
        
        // Count per subtheme
        if (!subthemeCounts[prefix]) {
            subthemeCounts[prefix] = 0;
        }
        subthemeCounts[prefix]++;
        
        const code = `${prefix}-${String(subthemeCounts[prefix]).padStart(3, '0')}`;
        input.value = code;
    });
    
    updateCount();
    showNotification('✅ Auto-assigned codes to all abstracts! Review and click Save.', 'success');
}

// Show save modal
function saveAllCodes() {
    const assignments = [];
    const inputs = document.querySelectorAll('.code-input');
    
    inputs.forEach(input => {
        if (input.value.trim()) {
            const abstractId = input.dataset.abstractId;
            const code = input.value.trim().toUpperCase();
            const checkbox = document.querySelector(`.program-select[data-abstract-id="${abstractId}"]`);
            const selected = checkbox.checked;
            
            // Get presentation mode from dropdown
            const modeSelect = document.querySelector(`.mode-select[data-abstract-id="${abstractId}"]`);
            const presentationMode = modeSelect ? modeSelect.value : null;
            
            // Get subtheme from dropdown
            const subthemeSelect = document.querySelector(`.subtheme-select[data-abstract-id="${abstractId}"]`);
            const subtheme = subthemeSelect ? subthemeSelect.value : null;
            
            assignments.push({
                abstract_id: abstractId,
                conference_code: code,
                committee_selected: selected,
                presentation_mode: presentationMode,
                subtheme: subtheme
            });
        }
    });
    
    if (assignments.length === 0) {
        showNotification('❌ No codes to save. Please assign codes first.', 'error');
        return;
    }
    
    // Store assignments for later
    window.pendingAssignments = assignments;
    
    // Update modal count and show
    document.getElementById('modalSaveCount').textContent = assignments.length;
    document.getElementById('saveModal').classList.remove('hidden');
}

// Close save modal
function closeSaveModal() {
    document.getElementById('saveModal').classList.add('hidden');
}

// Confirm save
function confirmSave() {
    closeSaveModal();
    
    const assignments = window.pendingAssignments;
    if (!assignments || assignments.length === 0) {
        return;
    }
    
    // Show loading
    const saveBtn = event.target;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '⏳ Saving...';
    
    fetch('{{ route('admin.conference-program.bulk-save-codes') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ assignments: assignments })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(`✅ Successfully saved ${data.saved} conference codes!`, 'success');
            setTimeout(() => {
                window.location.href = '{{ route('admin.conference-program.index') }}';
            }, 1500);
        } else {
            showNotification('❌ Error: ' + data.message, 'error');
            saveBtn.disabled = false;
            saveBtn.innerHTML = '💾 Save All Codes';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('❌ An error occurred. Please try again.', 'error');
        saveBtn.disabled = false;
        saveBtn.innerHTML = '💾 Save All Codes';
    });
}

// Update counts
function updateCount() {
    const inputs = document.querySelectorAll('.code-input');
    let count = 0;
    
    inputs.forEach(input => {
        if (input.value.trim()) {
            count++;
        }
    });
    
    // Update save button
    const saveBtns = document.querySelectorAll('button[onclick="saveAllCodes()"]');
    saveBtns.forEach(btn => {
        btn.innerHTML = `💾 Save All Codes (${count})`;
    });
}

// Filter table
function filterTable() {
    const subthemeFilter = document.getElementById('subthemeFilter').value.toLowerCase();
    const searchInput = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('.abstract-row');
    
    let visibleCount = 0;
    
    rows.forEach(row => {
        const subtheme = row.dataset.subtheme.toLowerCase();
        const title = row.dataset.title;
        const author = row.dataset.author;
        
        const matchesSubtheme = !subthemeFilter || subtheme === subthemeFilter;
        const matchesSearch = !searchInput || title.includes(searchInput) || author.includes(searchInput);
        
        if (matchesSubtheme && matchesSearch) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Update visible count
    console.log(`Showing ${visibleCount} of ${totalPending} abstracts`);
}

// Clear all inputs
function clearAll() {
    if (!confirm('Clear all assigned codes?')) {
        return;
    }
    
    document.querySelectorAll('.code-input').forEach(input => {
        input.value = '';
    });
    
    document.querySelectorAll('.program-select').forEach(checkbox => {
        checkbox.checked = false;
    });
    
    updateCount();
    showNotification('All codes cleared.', 'info');
}

// Show notification
function showNotification(message, type = 'info') {
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500'
    };
    
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-fade-in-down`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Auto-uppercase code inputs
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.code-input').forEach(input => {
        input.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    });
});
</script>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

@keyframes fade-in-down {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-down {
    animation: fade-in-down 0.3s ease-out;
}
</style>
@endpush
@endsection

