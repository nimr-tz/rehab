@extends('layouts.app')

@section('title', 'Subtheme Management')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
                  <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Subtheme Management</h1>
          <div class="flex gap-2">
              <button type="button" onclick="toggleBulkActions()" class="bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                  Bulk Actions
              </button>
              <a href="{{ route('admin.conference-program.builder') }}" class="bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                  Program Builder
              </a>
          </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-300 px-4 py-3 rounded mb-6">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 px-4 py-3 rounded mb-6">
            {{ session('error') }}
        </div>
    @endif

    <!-- Bulk Actions Panel (Initially Hidden) -->
    <div id="bulkActionsPanel" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow border border-gray-200 dark:border-gray-700 mb-8 hidden">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Bulk Actions</h3>
        <form method="POST" action="{{ route('admin.subthemes.bulk-update') }}" class="flex flex-wrap gap-4">
            @csrf
            <input type="hidden" name="abstract_ids" id="selectedAbstractIds" value="">
            
            <div>
                <label for="bulk_subtheme" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Change Subtheme</label>
                <select name="bulk_subtheme" id="bulk_subtheme" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-indigo-500 dark:focus:ring-indigo-400">
                    <option value="">-- Select New Subtheme --</option>
                    @foreach($subthemeData as $data)
                        <option value="{{ $data['subtheme'] }}">{{ $data['subtheme'] }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label for="bulk_presentation_mode" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Change Presentation Mode</label>
                <select name="bulk_presentation_mode" id="bulk_presentation_mode" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-indigo-500 dark:focus:ring-indigo-400">
                    <option value="">-- Select New Mode --</option>
                    <option value="oral">Oral</option>
                    <option value="poster">Poster</option>
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                    Apply Changes
                </button>
            </div>
        </form>
        
        <div class="mt-4">
            <form method="POST" action="{{ route('admin.subthemes.generate-codes') }}" class="inline">
                @csrf
                <input type="hidden" name="abstract_ids" id="selectedAbstractIdsForCodes" value="">
                <button type="submit" class="bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white px-4 py-2 rounded-lg transition-colors">
                    Generate Conference Codes
                </button>
            </form>
        </div>
    </div>

    <!-- Subtheme Overview -->
    <div class="space-y-8">
        @foreach($subthemeData as $data)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ $data['subtheme'] }}</h2>
                    <div class="flex gap-4 text-sm text-gray-600 dark:text-gray-400">
                        <span>{{ $data['total_abstracts'] }} abstracts</span>
                        <span>{{ $data['oral_count'] }} oral</span>
                        <span>{{ $data['poster_count'] }} poster</span>
                        <span>{{ $data['assigned_to_session'] }} in sessions</span>
                    </div>
                </div>
                
                <!-- Progress Bars -->
                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                            <span>Session Assignment</span>
                            <span>{{ $data['assigned_to_session'] }}/{{ $data['total_abstracts'] }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-blue-600 dark:bg-blue-500 h-2 rounded-full" style="width: {{ $data['total_abstracts'] > 0 ? ($data['assigned_to_session'] / $data['total_abstracts']) * 100 : 0 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                            <span>Conference Codes</span>
                            <span>{{ $data['with_codes'] }}/{{ $data['total_abstracts'] }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-green-600 dark:bg-green-500 h-2 rounded-full" style="width: {{ $data['total_abstracts'] > 0 ? ($data['with_codes'] / $data['total_abstracts']) * 100 : 0 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                            <span>Oral vs Poster</span>
                            <span>{{ $data['oral_count'] }}:{{ $data['poster_count'] }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 flex">
                            <div class="bg-green-600 dark:bg-green-500 h-2 rounded-l-full" style="width: {{ $data['total_abstracts'] > 0 ? ($data['oral_count'] / $data['total_abstracts']) * 100 : 0 }}%"></div>
                            <div class="bg-purple-600 dark:bg-purple-500 h-2 rounded-r-full" style="width: {{ $data['total_abstracts'] > 0 ? ($data['poster_count'] / $data['total_abstracts']) * 100 : 0 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Abstracts Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                <input type="checkbox" class="subtheme-select-all text-blue-600 dark:text-blue-400 focus:ring-blue-500 dark:focus:ring-blue-400 dark:bg-gray-600 dark:border-gray-500" data-subtheme="{{ $data['subtheme'] }}">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Abstract</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Author</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Session</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($data['abstracts'] as $abstract)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" class="abstract-checkbox text-blue-600 dark:text-blue-400 focus:ring-blue-500 dark:focus:ring-blue-400 dark:bg-gray-600 dark:border-gray-500" value="{{ $abstract->id }}" data-subtheme="{{ $data['subtheme'] }}">
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ Str::limit($abstract->title, 50) }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $abstract->author_institute }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-gray-100">{{ $abstract->author_name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $abstract->user->email ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $abstract->presentation_mode == 'oral' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300' }}">
                                    {{ ucfirst($abstract->presentation_mode) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($abstract->session)
                                    <div class="text-sm text-gray-900 dark:text-gray-100">{{ $abstract->session->name }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $abstract->session->room_location }}</div>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300">
                                        Not Assigned
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($abstract->conference_code)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                        {{ $abstract->conference_code }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                                        No Code
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="{{ route('admin.abstracts.view', $abstract) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-3 transition-colors">View</a>
                                <button type="button" onclick="editAbstract({{ $abstract->id }})" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 transition-colors">Edit</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Edit Abstract Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-600 dark:bg-gray-900 bg-opacity-50 dark:bg-opacity-75 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border border-gray-200 dark:border-gray-600 w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3 text-center">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Edit Abstract</h3>
            <form id="editForm" method="POST" action="" class="mt-4">
                @csrf
                @method('PUT')
                <div class="mb-4 text-left">
                    <label for="edit_subtheme" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Subtheme</label>
                    <select name="subtheme" id="edit_subtheme" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-indigo-500 dark:focus:ring-indigo-400" required>
                        @foreach($subthemeData as $data)
                            <option value="{{ $data['subtheme'] }}">{{ $data['subtheme'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4 text-left">
                    <label for="edit_presentation_mode" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Presentation Mode</label>
                    <select name="presentation_mode" id="edit_presentation_mode" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-indigo-500 dark:focus:ring-indigo-400" required>
                        <option value="oral">Oral</option>
                        <option value="poster">Poster</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeModal()" class="bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500 text-gray-800 dark:text-gray-200 font-bold py-2 px-4 rounded transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="bg-blue-500 dark:bg-blue-600 hover:bg-blue-700 dark:hover:bg-blue-500 text-white font-bold py-2 px-4 rounded transition-colors">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleBulkActions() {
    const panel = document.getElementById('bulkActionsPanel');
    panel.classList.toggle('hidden');
}

function editAbstract(abstractId) {
    // This would typically fetch the abstract data via AJAX
    // For now, just show the modal
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editForm').action = `/admin/abstracts/${abstractId}`;
}

function closeModal() {
    document.getElementById('editModal').classList.add('hidden');
}

// Handle bulk selection
document.addEventListener('DOMContentLoaded', function() {
    // Select all checkboxes for subtheme
    document.querySelectorAll('.subtheme-select-all').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const subtheme = this.dataset.subtheme;
            const abstractCheckboxes = document.querySelectorAll(`input[data-subtheme="${subtheme}"].abstract-checkbox`);
            abstractCheckboxes.forEach(cb => cb.checked = this.checked);
            updateSelectedAbstracts();
        });
    });

    // Handle individual checkbox changes
    document.querySelectorAll('.abstract-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedAbstracts);
    });

    function updateSelectedAbstracts() {
        const selectedIds = Array.from(document.querySelectorAll('.abstract-checkbox:checked')).map(cb => cb.value);
        document.getElementById('selectedAbstractIds').value = selectedIds.join(',');
        document.getElementById('selectedAbstractIdsForCodes').value = selectedIds.join(',');
    }
});
</script>
@endsection
