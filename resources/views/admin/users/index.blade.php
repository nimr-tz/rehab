@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 dark:from-gray-900 dark:via-slate-900 dark:to-gray-900">
    <div class="relative max-w-[1600px] mx-auto px-6 py-10">

        {{-- Executive Header System --}}
        <div class="relative overflow-hidden mb-12">
            {{-- Abstract Background Elements --}}
            <div class="absolute inset-0 opacity-40 pointer-events-none">
                <div class="absolute top-0 right-1/4 w-96 h-96 bg-blue-200 dark:bg-blue-500/20 rounded-full blur-3xl animate-pulse"></div>
                <div class="absolute -bottom-24 left-1/4 w-80 h-80 bg-purple-200 dark:bg-purple-500/10 rounded-full blur-3xl"></div>
            </div>

            <div class="relative flex flex-col lg:flex-row lg:items-center justify-between gap-8 z-10">
                <div class="flex items-center gap-6">
                    <div class="p-5 bg-slate-900 dark:bg-white rounded-[2rem] shadow-2xl shadow-slate-200/50 dark:shadow-none group">
                        <svg class="w-10 h-10 text-white dark:text-slate-900 group-hover:rotate-12 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-2.239"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight">Attendee Management</h1>
                        <div class="flex items-center gap-3 mt-2">
                            <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 text-[10px] font-black uppercase rounded-md border border-blue-500/20">Administration</span>
                            <p class="text-slate-500 dark:text-slate-400 text-sm font-medium tracking-wide">Managing conference participants and system access roles.</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-4">
                    {{-- Professional Search --}}
                    <form action="{{ route('admin.users') }}" method="GET" class="relative group" id="searchForm">
                        @if(request('filter'))
                            <input type="hidden" name="filter" value="{{ request('filter') }}">
                        @endif
                        <input type="text" name="search" id="userSearch" value="{{ request('search') }}" placeholder="Search name, email or institution..."
                               class="w-full md:w-80 px-6 py-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all font-bold text-slate-900 dark:text-white dark:placeholder-slate-500 pl-14">
                        <button type="submit" class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </button>
                    </form>
                    <button onclick="exportUsers()" class="p-4 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 hover:text-blue-600 dark:hover:text-blue-400 transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    </button>
                    <button onclick="window.location.reload()" class="p-4 bg-white dark:bg-slate-800 text-slate-400 hover:text-blue-600 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 transition-all group">
                        <svg class="w-6 h-6 group-hover:rotate-180 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Tiered Metrics Console --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            {{-- Total Users --}}
            <div class="p-6 bg-white/70 dark:bg-slate-800/50 backdrop-blur-md rounded-[2.5rem] border border-white dark:border-slate-700/50 shadow-xl shadow-slate-200/50 dark:shadow-none group transition-all hover:scale-[1.02]">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-2 bg-blue-100 dark:bg-blue-500/20 rounded-xl">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <span class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest">All Users</span>
                </div>
                <p class="text-3xl font-black text-slate-900 dark:text-white leading-none">{{ number_format($stats['total_users']) }}</p>
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mt-2 tracking-widest">Registered Users</p>
            </div>

            {{-- Active Authors --}}
            <div class="p-6 bg-white/70 dark:bg-slate-800/50 backdrop-blur-md rounded-[2.5rem] border border-white dark:border-slate-700/50 shadow-xl shadow-slate-200/50 dark:shadow-none group transition-all hover:scale-[1.02]">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-2 bg-emerald-100 dark:bg-emerald-500/20 rounded-xl">
                        <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </div>
                    <span class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">Authors</span>
                </div>
                <p class="text-3xl font-black text-slate-900 dark:text-white leading-none">{{ number_format($stats['users_with_submissions']) }}</p>
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mt-2 tracking-widest">Users with Abstracts</p>
            </div>

            {{-- Review Panel --}}
            <div class="p-6 bg-white/70 dark:bg-slate-800/50 backdrop-blur-md rounded-[2.5rem] border border-white dark:border-slate-700/50 shadow-xl shadow-slate-200/50 dark:shadow-none group transition-all hover:scale-[1.02]">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-2 bg-indigo-100 dark:bg-indigo-500/20 rounded-xl">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <span class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">Reviewers</span>
                </div>
                <p class="text-3xl font-black text-slate-900 dark:text-white leading-none">{{ number_format($stats['reviewers']) }}</p>
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mt-2 tracking-widest">Assigned Reviewers</p>
            </div>

            {{-- Decision Makers --}}
            <div class="p-6 bg-slate-900 rounded-[2.5rem] border border-slate-800 shadow-2xl transition-all hover:scale-[1.02]">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-2 bg-slate-800 rounded-xl">
                        <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Admins</span>
                </div>
                <p class="text-3xl font-black text-white leading-none">{{ number_format($stats['admins']) }}</p>
                <p class="text-xs font-bold text-slate-600 uppercase mt-2 tracking-widest">System Administrators</p>
            </div>
        </div>

        {{-- Tactical Filter Console --}}
        <div class="bg-white/50 dark:bg-slate-800/50 backdrop-blur-xl rounded-[2.5rem] border border-white dark:border-slate-700 shadow-xl mb-12 p-2">
            <div class="flex flex-wrap items-center gap-2 p-4">
                <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-4 mr-4">Filter By</span>

                @foreach([
                    'all' => ['label' => 'All Users', 'icon' => '🌐', 'color' => 'slate'],
                    'authors' => ['label' => 'Authors', 'icon' => '✏️', 'color' => 'emerald'],
                    'scientific_admins' => ['label' => 'Scientific', 'icon' => '🎓', 'color' => 'blue'],
                    'reviewers' => ['label' => 'Reviewers', 'icon' => '🔍', 'color' => 'indigo'],
                    'admins' => ['label' => 'Admins', 'icon' => '👑', 'color' => 'rose']
                ] as $key => $filter)
                    <a href="{{ $key === 'all' ? route('admin.users') : route('admin.users', ['filter' => $key]) }}"
                        class="px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all
                        {{ (request('filter') === $key || ($key === 'all' && !request('filter')))
                            ? 'bg-slate-900 dark:bg-white text-white dark:text-slate-900 shadow-xl'
                            : 'bg-white/50 dark:bg-slate-900/50 text-slate-500 hover:bg-white dark:hover:bg-slate-700 border border-slate-100 dark:border-slate-800' }}">
                        <span class="mr-2">{{ $filter['icon'] }}</span>
                        {{ $filter['label'] }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Personnel Grid --}}
        <div id="personnel-grid" class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-2xl rounded-[3rem] border border-white dark:border-slate-700 shadow-2xl overflow-hidden">
            <div class="px-10 py-8 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-black text-slate-900 dark:text-white leading-none">Users</h3>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-2">List of all registered users</p>
                </div>
                <div class="flex items-center gap-4">
                    <span class="px-4 py-1.5 bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400 text-[10px] font-black uppercase tracking-widest rounded-full border border-slate-200 dark:border-slate-700">
                        Total Capacity: {{ $users->total() }}
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-50/50 dark:bg-slate-900/50">
                            <th class="px-10 py-6 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">User Profile</th>
                            <th class="px-10 py-6 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Assigned Roles</th>
                            <th class="px-10 py-6 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Institution</th>
                            <th class="px-10 py-6 text-center text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Abstracts</th>
                            <th class="px-10 py-6 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @foreach ($users as $user)
                            <tr class="group hover:bg-blue-50/30 dark:hover:bg-blue-900/10 transition-all duration-300">
                                <td class="px-10 py-8 whitespace-nowrap">
                                    <div class="flex items-center gap-5">
                                        <div class="relative flex-shrink-0">
                                            @if ($user->profile_image)
                                                <img src="{{ asset('storage/' . $user->profile_image) }}" alt="" class="h-14 w-14 rounded-2xl object-cover shadow-lg border border-white dark:border-slate-700" />
                                            @else
                                                <div class="h-14 w-14 rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-700 dark:to-slate-600 text-slate-600 dark:text-slate-300 flex items-center justify-center font-black text-xl shadow-inner border border-white dark:border-slate-700">
                                                    {{ $user->initials }}
                                                </div>
                                            @endif
                                            @if($user->hasRole('admin'))
                                                <div class="absolute -top-2 -right-2 w-6 h-6 bg-rose-500 text-white text-[10px] flex items-center justify-center rounded-lg shadow-lg border-2 border-white dark:border-slate-800 animate-bounce">👑</div>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="text-base font-black text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                                {{ $user->title ?? '' }} {{ $user->first_name }} {{ $user->last_name }}
                                            </p>
                                            <p class="text-xs font-bold text-slate-400 tracking-wide mt-0.5">{{ $user->email }}</p>
                                            <p class="text-[11px] font-medium text-slate-400 mt-0.5">
                                                {{ $user->phone ?: 'No phone on file' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-10 py-8 whitespace-nowrap">
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($user->roles as $role)
                                            @php
                                                $roleConfig = [
                                                    'admin' => ['bg' => 'rose', 'icon' => '👑'],
                                                    'scientific_admin' => ['bg' => 'blue', 'icon' => '🎓'],
                                                    'reviewer' => ['bg' => 'indigo', 'icon' => '🔍'],
                                                    'finance_officer' => ['bg' => 'emerald', 'icon' => '💰'],
                                                    'registration_officer' => ['bg' => 'violet', 'icon' => '📋'],
                                                    'author' => ['bg' => 'blue', 'icon' => '✏️'],
                                                ];
                                                $conf = $roleConfig[$role->name] ?? ['bg' => 'slate', 'icon' => '👤'];
                                            @endphp
                                            <span class="inline-flex items-center px-4 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest border bg-{{ $conf['bg'] }}-50 dark:bg-{{ $conf['bg'] }}-500/10 text-{{ $conf['bg'] }}-600 dark:text-{{ $conf['bg'] }}-400 border-{{ $conf['bg'] }}-200/50 dark:border-{{ $conf['bg'] }}-500/20 shadow-sm">
                                                <span class="mr-2 text-sm">{{ $conf['icon'] }}</span>
                                                {{ $role->name }}
                                                @if ($role->pivot->is_primary)
                                                    <div class="ml-2 w-1.5 h-1.5 bg-{{ $conf['bg'] }}-500 rounded-full animate-ping"></div>
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-10 py-8 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        </div>
                                        <p class="text-sm font-bold text-slate-600 dark:text-slate-300">
                                            {{ Str::limit($user->affiliation ?? 'External Partner', 25) }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-10 py-8 whitespace-nowrap text-center">
                                    <div class="inline-block p-2 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800">
                                        <p class="text-sm font-black text-slate-900 dark:text-white leading-none">{{ $user->abstractSubmissions->count() }}</p>
                                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mt-1">Units</p>
                                    </div>
                                </td>
                                <td class="px-10 py-8 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-3">
                                        {{-- Advanced Interaction Menu --}}
                                        <div class="relative group/menu">
                                            <button class="p-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl text-slate-400 hover:text-blue-600 hover:border-blue-500/30 transition-all shadow-sm">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                                            </button>

                                            <div class="absolute right-0 mt-4 w-60 bg-white dark:bg-slate-800 rounded-[2rem] shadow-2xl border border-slate-100 dark:border-slate-700 z-[50] hidden group-hover/menu:block p-3 animate-in fade-in zoom-in duration-200">
                                                <div class="px-4 py-3 border-b border-slate-50 dark:border-slate-700 mb-2">
                                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Update Roles</p>
                                                </div>
                                                <div class="space-y-1">
                                                    @foreach([
                                                        'author' => ['label' => 'Author', 'icon' => '✏️'],
                                                        'scientific_admin' => ['label' => 'Sci. Coord.', 'icon' => '🎓'],
                                                        'reviewer' => ['label' => 'Reviewer', 'icon' => '🔍'],
                                                        'finance_officer' => ['label' => 'Finance', 'icon' => '💰'],
                                                        'registration_officer' => ['label' => 'Registrar', 'icon' => '📋'],
                                                        'admin' => ['label' => 'Admin (Core)', 'icon' => '👑', 'danger' => true]
                                                    ] as $r => $data)
                                                        @if($r !== 'admin' || auth()->user()->hasRole('admin'))
                                                            <button onclick="changeUserRole({{ $user->id }}, '{{ $r }}')"
                                                                    class="flex items-center justify-between w-full px-4 py-3 text-xs font-black uppercase tracking-widest hover:bg-slate-50 dark:hover:bg-slate-900 rounded-xl transition-all {{ isset($data['danger']) ? 'text-rose-500' : 'text-slate-700 dark:text-slate-300' }}">
                                                                <span>{{ $data['icon'] }} {{ $data['label'] }}</span>
                                                                @if($user->hasRole($r))
                                                                    <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                                @endif
                                                            </button>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>

                                        <a href="{{ route('admin.users.edit', $user) }}" class="p-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl shadow-xl hover:-translate-y-1 transition-all" title="Edit User">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </a>

                                        @if($user->id !== auth()->id())
                                            <button onclick="confirmDeleteUser({{ $user->id }}, '{{ $user->first_name }} {{ $user->last_name }}')" 
                                                    class="p-3 bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-200/50 dark:border-rose-500/20 rounded-2xl shadow-xl hover:-translate-y-1 transition-all" title="Delete User">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>

                                            <form id="delete-user-form-{{ $user->id }}" action="{{ route('admin.users.destroy', $user) }}" method="POST" class="hidden">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="px-10 py-8 border-t border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    function changeUserRole(userId, newRole) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-slate-900/60 backdrop-blur-md flex items-center justify-center z-[100] p-6';
        modal.innerHTML = `
            <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-10 w-full max-w-md shadow-2xl border border-white dark:border-slate-700 animate-in zoom-in duration-300">
                <div class="w-16 h-16 bg-blue-100 dark:bg-blue-500/10 rounded-2xl flex items-center justify-center mb-6 mx-auto">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight text-center mb-2 text-balance">Update User Role?</h3>
                <p class="text-xs font-bold text-slate-400 text-center mb-8 tracking-wide">You are about to modify the access level for this user to <span class="text-blue-600 font-black uppercase text-sm">${newRole}</span>.</p>

                <div class="flex items-center gap-4">
                    <button onclick="this.closest('.fixed').remove()" class="flex-1 py-4 text-xs font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-all">Cancel</button>
                    <button id="confirmRoleBtn" class="flex-1 py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-xs font-black uppercase tracking-widest rounded-2xl shadow-xl hover:-translate-y-1 transition-all">Confirm Update</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        modal.querySelector('#confirmRoleBtn').addEventListener('click', function() {
            this.textContent = 'UPDATING...';
            this.disabled = true;

            fetch(`/admin/users/${userId}/toggle-role/${newRole}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('FAILURE: ' + data.error);
                    modal.remove();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('CRITICAL NETWORK ERROR');
                modal.remove();
            });
        });
    }
    
    function confirmDeleteUser(userId, userName) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-slate-900/60 backdrop-blur-md flex items-center justify-center z-[100] p-6';
        modal.innerHTML = `
            <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-10 w-full max-w-md shadow-2xl border border-white dark:border-slate-700 animate-in zoom-in duration-300">
                <div class="w-16 h-16 bg-rose-100 dark:bg-rose-500/10 rounded-2xl flex items-center justify-center mb-6 mx-auto">
                    <svg class="w-8 h-8 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight text-center mb-2 text-balance">Delete User Record?</h3>
                <p class="text-xs font-bold text-slate-400 text-center mb-8 tracking-wide">You are about to permanently delete <span class="text-rose-600 font-black uppercase text-sm">${userName}</span>. This action is irreversible and will remove all associated submission data.</p>

                <div class="flex items-center gap-4">
                    <button onclick="this.closest('.fixed').remove()" class="flex-1 py-4 text-xs font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-all">Cancel</button>
                    <button id="confirmDeleteBtn" class="flex-1 py-4 bg-rose-600 text-white text-xs font-black uppercase tracking-widest rounded-2xl shadow-xl hover:-translate-y-1 transition-all">Delete Record</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        modal.querySelector('#confirmDeleteBtn').addEventListener('click', function() {
            document.getElementById(`delete-user-form-${userId}`).submit();
        });
    }

    function exportUsers() {
        const filter = new URLSearchParams(window.location.search).get('filter') || 'all';
        window.location.href = `/admin/users/export?filter=${filter}`;
    }

    // Live AJAX Search Logic
    let searchTimeout;
    const searchForm = document.getElementById('searchForm');
    const searchInput = document.getElementById('userSearch');
    const submitBtn = searchForm.querySelector('button[type="submit"]');
    const originalIcon = submitBtn.innerHTML;
    const spinnerIcon = `<svg class="w-6 h-6 animate-spin text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>`;

    function performSearch() {
        submitBtn.innerHTML = spinnerIcon;
        const query = searchInput.value;
        const url = new URL(searchForm.action);
        const filterInput = searchForm.querySelector('input[name="filter"]');
        if (filterInput) url.searchParams.set('filter', filterInput.value);
        if (query) url.searchParams.set('search', query);

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newGrid = doc.getElementById('personnel-grid');
            if (newGrid) {
                document.getElementById('personnel-grid').innerHTML = newGrid.innerHTML;
            }
            submitBtn.innerHTML = originalIcon;
            window.history.pushState({}, '', url);
        })
        .catch(err => {
            console.error('Search failed:', err);
            submitBtn.innerHTML = originalIcon;
        });
    }

    searchInput.addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(performSearch, 500);
    });

    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        clearTimeout(searchTimeout);
        performSearch();
    });

</script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;900&display=swap');
    :root { font-family: 'Outfit', sans-serif; }
</style>
@endsection
