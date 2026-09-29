@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Notifications</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Stay updated with your latest activities</p>
            </div>
            
            @if(auth()->user()->notifications()->unread()->count() > 0)
                <form action="{{ route('notifications.markAllAsRead') }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-slate-300 dark:border-gray-600 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 shadow-sm hover:bg-slate-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Mark all as read
                    </button>
                </form>
            @endif
        </div>

        <div class="space-y-4">
            @forelse($notifications as $notification)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border {{ $notification->read_at ? 'border-slate-100 dark:border-gray-700' : 'border-blue-200 dark:border-blue-900 ring-1 ring-blue-500/10' }} p-5 transition-all hover:shadow-md group">
                    <div class="flex items-start gap-4">
                        <!-- Icon -->
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center border {{ $notification->getPriorityClass() }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $notification->getIcon() }}" />
                                </svg>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="text-base font-semibold text-slate-900 dark:text-white {{ $notification->read_at ? '' : 'text-blue-600 dark:text-blue-400' }}">
                                        {{ $notification->title }}
                                        @if(!$notification->read_at)
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">New</span>
                                        @endif
                                    </h3>
                                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $notification->message }}</p>
                                </div>
                                <span class="text-xs text-slate-400 whitespace-nowrap ml-4">{{ $notification->created_at->diffForHumans() }}</span>
                            </div>

                            <!-- Actions -->
                            <div class="mt-4 flex items-center gap-4">
                                @if($notification->action_url)
                                    <a href="{{ $notification->action_url }}" class="text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 flex items-center">
                                        View Details
                                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                @endif

                                @if(!$notification->read_at)
                                    <form action="{{ route('notifications.markAsRead', $notification) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                                            Mark as read
                                        </button>
                                    </form>
                                @endif
                                
                                <form action="{{ route('notifications.destroy', $notification) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this notification?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-medium text-red-500 hover:text-red-700 opacity-0 group-hover:opacity-100 transition-opacity">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-slate-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    </div>
                    <h3 class="text-lg font-medium text-slate-900 dark:text-white">All caught up!</h3>
                    <p class="text-slate-500 dark:text-slate-400 mt-1">You have no new notifications.</p>
                </div>
            @endforelse

            <div class="mt-6">
                <!-- Pagination links could go here if paginated -->
            </div>
        </div>
    </div>
</div>
@endsection
