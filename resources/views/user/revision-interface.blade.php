@extends('layouts.app')

@section('title', 'Revision Required')

@section('content')
    <!-- Sophisticated Professional Header -->
    <div class="relative bg-gradient-to-br from-indigo-700 via-indigo-800 to-blue-900 py-20 rounded-b-[5rem] shadow-2xl overflow-hidden mb-12">
        <!-- Abstract Precision Background -->
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/simple-dashed.png')] opacity-[0.05]"></div>
        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-b from-black/20 to-transparent"></div>
        
        <div class="max-w-7xl mx-auto px-8 sm:px-10 relative z-10">
            <div class="flex flex-col md:flex-row justify-between items-end gap-10">
                <div class="space-y-3">
                    <h1 class="text-4xl md:text-6xl font-black text-white leading-none tracking-tight" style="font-family: 'Outfit', sans-serif;">
                        Revision <span class="text-blue-200">Required.</span>
                    </h1>
                    <p class="text-lg text-indigo-100/60 font-medium tracking-wide">
                        Please address the reviewer feedback for "{{ $abstract->title }}".
                    </p>
                </div>
                
                <div class="flex items-center gap-6 mb-2">
                    <!-- Discrete Modern Metrics -->
                    <div class="flex items-center gap-12 bg-white/5 backdrop-blur-md px-10 py-5 rounded-[2rem] border border-white/10 shadow-2xl">
                        <div class="text-center">
                            <p class="text-[9px] font-black text-blue-200/50 uppercase tracking-[0.3em] mb-1">Status</p>
                            <p class="text-3xl font-black text-white leading-none text-xs uppercase">{{ str_replace('_', ' ', $abstract->status) }}</p>
                        </div>
                    </div>
                    
                    <a href="{{ route('user.dashboard') }}" 
                       class="px-6 py-3 bg-white/10 backdrop-blur-xl border border-white/20 text-white font-bold rounded-xl hover:bg-white/20 transition-all text-xs uppercase tracking-widest">
                        Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Revision Feedback -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        Revision Feedback
                    </h2>
                    
                    <div class="space-y-4">
                        @if($revisionFeedback)
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                                <h3 class="text-sm font-semibold text-yellow-800 dark:text-yellow-200 mb-1">Admin Feedback</h3>
                                <p class="text-sm text-yellow-700 dark:text-yellow-300">{{ $revisionFeedback }}</p>
                            </div>
                        @endif
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Deadline</label>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    @if($revisionDeadline)
                                        {{ \Carbon\Carbon::parse($revisionDeadline)->format('M j, Y \a\t g:i A') }}
                                        @if(\Carbon\Carbon::parse($revisionDeadline)->isFuture())
                                            <span class="text-green-600 dark:text-green-400 text-xs ml-1">({{ \Carbon\Carbon::parse($revisionDeadline)->diffForHumans() }})</span>
                                        @else
                                            <span class="text-red-600 dark:text-red-400 text-xs ml-1">(Overdue)</span>
                                        @endif
                                    @else
                                        <span class="text-gray-500">No deadline set</span>
                                    @endif
                                </p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Requested On</label>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $revisionRequestedAt ? \Carbon\Carbon::parse($revisionRequestedAt)->format('M j, Y \a\t g:i A') : 'N/A' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reviewer Feedback -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Reviewer Comments
                    </h2>
                    
                    <div class="space-y-6">
                        @forelse($reviews as $review)
                            <div class="border border-gray-100 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-750">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 {{ $review->reviewer_number === 1 ? 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400' }} rounded-full flex items-center justify-center mr-3">
                                            <span class="text-sm font-bold">{{ $review->reviewer_number }}</span>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                                {{ $review->reviewer_number === 1 ? 'Reviewer A' : 'Reviewer B' }}
                                            </h4>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                Anonymous Reviewer
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-bold {{ $review->score >= 85 ? 'text-green-600 dark:text-green-400' : ($review->score >= 70 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                                            Score: {{ number_format($review->score, 1) }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $review->submitted_at ? $review->submitted_at->format('M j, Y') : 'Submitted' }}
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        {{ $review->recommendation === 'accept' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 
                                           ($review->recommendation === 'minor_revisions' ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300' : 
                                           ($review->recommendation === 'major_revisions' ? 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300' : 
                                           'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300')) }}">
                                        Recommendation: {{ ucfirst(str_replace('_', ' ', $review->recommendation)) }}
                                    </span>
                                </div>

                                @if($review->comments)
                                    <div class="mt-2">
                                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Comments:</p>
                                        <div class="text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-600">
                                            {{ $review->comments }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-8 bg-gray-50 dark:bg-gray-750 rounded-lg border border-dashed border-gray-300 dark:border-gray-600">
                                <p class="text-gray-500 dark:text-gray-400">No reviewer comments available.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Edit Abstract Form -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit Abstract
                    </h2>
                    
                    <form method="POST" action="{{ route('user.abstract.resubmit', $abstract) }}" class="space-y-6">
                        @csrf
                        
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Title</label>
                            <input type="text" 
                                   id="title" 
                                   name="title" 
                                   value="{{ old('title', $abstract->title) }}" 
                                   required 
                                   class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            @error('title')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="author_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Author Name</label>
                                <input type="text" 
                                       id="author_name" 
                                       name="author_name" 
                                       value="{{ old('author_name', $abstract->author_name) }}" 
                                       required 
                                       class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                @error('author_name')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="author_institute" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Institution</label>
                                <input type="text" 
                                       id="author_institute" 
                                       name="author_institute" 
                                       value="{{ old('author_institute', $abstract->author_institute) }}" 
                                       required 
                                       class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                @error('author_institute')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="subtheme" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Subtheme</label>
                            <select id="subtheme" 
                                    name="subtheme" 
                                    required 
                                    class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="">Select a subtheme</option>
                                @foreach(array_keys(config('conference.subtheme_prefixes', [])) as $theme)
                                    <option value="{{ $theme }}" {{ old('subtheme', $abstract->subtheme) === $theme ? 'selected' : '' }}>{{ $theme }}</option>
                                @endforeach
                            </select>
                            @error('subtheme')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description</label>
                            <textarea id="description" 
                                      name="description" 
                                      rows="8" 
                                      required 
                                      class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">{{ old('description', $abstract->description) }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="revision_feedback" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                                Revision Notes <span class="text-rose-500">*</span>
                            </label>
                            <textarea id="revision_feedback" 
                                      name="revision_feedback" 
                                      rows="4" 
                                      required
                                      placeholder="Explain the changes you made to address the reviewer feedback..."
                                      class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">{{ old('revision_feedback') }}</textarea>
                            @error('revision_feedback')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                            <a href="{{ route('user.dashboard') }}" 
                               class="px-5 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 font-medium transition-colors text-sm">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="px-5 py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors text-sm shadow-sm">
                                Resubmit Abstract
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 sticky top-8">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Revision Guidelines</h3>
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="w-1.5 h-1.5 bg-blue-500 rounded-full mt-1.5 mr-3 flex-shrink-0"></div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Address all reviewer comments and feedback</p>
                        </div>
                        <div class="flex items-start">
                            <div class="w-1.5 h-1.5 bg-blue-500 rounded-full mt-1.5 mr-3 flex-shrink-0"></div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Make substantive improvements to your abstract</p>
                        </div>
                        <div class="flex items-start">
                            <div class="w-1.5 h-1.5 bg-blue-500 rounded-full mt-1.5 mr-3 flex-shrink-0"></div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Provide revision notes explaining your changes</p>
                        </div>
                        <div class="flex items-start">
                            <div class="w-1.5 h-1.5 bg-blue-500 rounded-full mt-1.5 mr-3 flex-shrink-0"></div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Ensure all information is accurate and complete</p>
                        </div>
                    </div>

                    @if($revisionDeadline && \Carbon\Carbon::parse($revisionDeadline)->isFuture())
                        <div class="mt-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                            <h4 class="text-sm font-semibold text-green-800 dark:text-green-200 mb-1">Deadline Reminder</h4>
                            <p class="text-xs text-green-700 dark:text-green-300">
                                You have until <strong>{{ \Carbon\Carbon::parse($revisionDeadline)->format('M j, Y \a\t g:i A') }}</strong> to resubmit.
                            </p>
                        </div>
                    @elseif($revisionDeadline && \Carbon\Carbon::parse($revisionDeadline)->isPast())
                        <div class="mt-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <h4 class="text-sm font-semibold text-red-800 dark:text-red-200 mb-1">Deadline Passed</h4>
                            <p class="text-xs text-red-700 dark:text-red-300">
                                The revision deadline has passed. Please contact the administrator.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
