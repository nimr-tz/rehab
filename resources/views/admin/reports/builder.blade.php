@extends('layouts.app')

@section('title', 'Report Builder')

@section('content')
<div class="min-h-screen bg-slate-50/50 dark:bg-gray-900/50" x-data="{ 
    selectedColumns: ['id', 'title', 'subtheme', 'author_name', 'status'],
    format: 'csv',
    dateFrom: '',
    dateTo: '',
    presets: {
        default: ['id', 'title', 'subtheme', 'author_name', 'status'],
        scientific: ['id', 'title', 'subtheme', 'conference_code', 'status', 'average_score', 'reviewer_names', 'recommendations'],
        contact: ['id', 'author_title', 'author_name', 'author_email', 'author_institute', 'author_country'],
        full: ['id', 'title', 'conference_code', 'subtheme', 'author_name', 'author_email', 'status', 'average_score', 'recommendations', 'description']
    },
    applyPreset(name) {
        this.selectedColumns = [...this.presets[name]];
    },
    toggleColumn(col) {
        if (this.selectedColumns.includes(col)) {
            this.selectedColumns = this.selectedColumns.filter(c => c !== col);
        } else {
            this.selectedColumns.push(col);
        }
    },
    isColumnSelected(col) {
        return this.selectedColumns.includes(col);
    }
}">
    <div class="max-w-6xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        
        <!-- Premium Header Section -->
        <div class="relative mb-10 p-8 rounded-[2rem] bg-gradient-to-br from-indigo-700 via-blue-600 to-cyan-500 shadow-2xl shadow-blue-500/20 overflow-hidden">
            {{-- Decorative Elements --}}
            <div class="absolute -top-24 -right-24 w-80 h-80 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-cyan-400/20 rounded-full blur-3xl"></div>
            
            <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div>
                    <nav class="flex mb-4 text-blue-100/70 text-sm font-medium" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-2">
                            <li><a href="{{ route('admin.dashboard') }}" class="hover:text-white transition-colors">Admin</a></li>
                            <li class="flex items-center"><svg class="w-4 h-4 mx-1" fill="currentColor" viewBox="0 0 20 20"><path d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"/></svg></li>
                            <li class="text-white">Report Builder</li>
                        </ol>
                    </nav>
                    <div class="flex flex-wrap items-center gap-3 mt-3">
                        <a href="{{ route('admin.reports.summary') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-sm font-bold border border-white/20 transition-colors">
                            View summary by institute & country
                        </a>
                        <a href="{{ route('admin.reports.unpaid-contacts') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-400/20 hover:bg-amber-400/30 text-amber-100 text-sm font-bold border border-amber-300/30 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                            Export Unpaid Phone Numbers
                        </a>
                    </div>
                    <h1 class="text-4xl font-extrabold text-white tracking-tight sm:text-5xl font-outfit">
                        Dynamic <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-200 to-cyan-100">Report Builder</span>
                    </h1>
                    <p class="mt-3 text-lg text-blue-100 leading-relaxed font-light max-w-2xl">
                        Design and generate precision reports with customized data points, perfect for administrative tracking and scientific review.
                    </p>
                </div>
                
                {{-- Live Summary Card --}}
                <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 text-white min-w-[200px] shadow-lg">
                    <div class="text-sm font-medium text-blue-200 mb-1">Columns Selected</div>
                    <div class="text-4xl font-black font-outfit" x-text="selectedColumns.length"></div>
                    <div class="mt-4 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-cyan-400 animate-pulse"></span>
                        <span class="text-xs uppercase tracking-wider font-bold text-blue-100" x-text="format.toUpperCase()"></span>
                    </div>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.reports.generate') }}" method="POST" class="space-y-8">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                {{-- Left Side: Filters & Format --}}
                <div class="lg:col-span-4 space-y-8">
                    
                    <!-- 1. Filters Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-5 border-b border-slate-100 dark:border-gray-700 flex items-center gap-4">
                            <div class="p-2.5 bg-indigo-100 dark:bg-indigo-900/40 rounded-xl text-indigo-600 dark:text-indigo-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Filter Data</h2>
                                <p class="text-xs text-slate-500 dark:text-gray-400">Step 1: Refine your records</p>
                            </div>
                        </div>
                        <div class="p-6 space-y-5">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-widest mb-2 ml-1">Submission Status</label>
                                <select name="status" class="w-full h-12 px-4 rounded-xl border-slate-200 dark:border-gray-600 bg-slate-50 dark:bg-gray-700/50 text-slate-900 dark:text-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all">
                                    <option value="all">All Active Statuses</option>
                                    <option value="submitted">Submitted</option>
                                    <option value="under_review">Under Review</option>
                                    <option value="accepted">Accepted</option>
                                    <option value="rejected">Rejected</option>
                                    <option value="minor_revision_required">Minor Revision</option>
                                    <option value="major_revision_required">Major Revision</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-widest mb-2 ml-1">By Subtheme</label>
                                <select name="subtheme" class="w-full h-12 px-4 rounded-xl border-slate-200 dark:border-gray-600 bg-slate-50 dark:bg-gray-700/50 text-slate-900 dark:text-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all">
                                    <option value="all">All Scientific Themes</option>
                                    @foreach($subthemes as $subtheme)
                                        <option value="{{ $subtheme }}">{{ $subtheme }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-widest mb-2 ml-1">From</label>
                                    <input type="date" name="date_from" id="report_date_from" x-model="dateFrom" class="w-full h-12 px-4 rounded-xl border-slate-200 dark:border-gray-600 bg-slate-50 dark:bg-gray-700/50 text-slate-900 dark:text-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all text-sm [&::-webkit-calendar-picker-indicator]:opacity-100 [&::-webkit-calendar-picker-indicator]:cursor-pointer">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-widest mb-2 ml-1">To</label>
                                    <input type="date" name="date_to" id="report_date_to" x-model="dateTo" :min="dateFrom || undefined" class="w-full h-12 px-4 rounded-xl border-slate-200 dark:border-gray-600 bg-slate-50 dark:bg-gray-700/50 text-slate-900 dark:text-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all text-sm [&::-webkit-calendar-picker-indicator]:opacity-100 [&::-webkit-calendar-picker-indicator]:cursor-pointer">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-amber-50 dark:bg-amber-950/30 rounded-3xl shadow-xl shadow-amber-200/40 dark:shadow-none border border-amber-200 dark:border-amber-800/60 overflow-hidden">
                        <div class="px-6 py-5 border-b border-amber-200/70 dark:border-amber-800/60 flex items-center gap-4">
                            <div class="p-2.5 bg-amber-100 dark:bg-amber-900/50 rounded-xl text-amber-700 dark:text-amber-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.3a1 1 0 01.95.68l1.2 3.6a1 1 0 01-.5 1.2l-2.1 1.05a11 11 0 005.62 5.62l1.05-2.1a1 1 0 011.2-.5l3.6 1.2a1 1 0 01.68.95V18a2 2 0 01-2 2h-1C9.82 20 4 14.18 4 7V5z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-amber-950 dark:text-amber-100">Unpaid Contacts</h2>
                                <p class="text-xs text-amber-800/80 dark:text-amber-200/70">{{ number_format($unpaidContactsCount ?? 0) }} phone contacts ready</p>
                            </div>
                        </div>
                        <div class="p-6 space-y-4">
                            <p class="text-sm leading-relaxed text-amber-900/80 dark:text-amber-100/80">
                                Download delegates who have not been verified or waived.
                            </p>
                            <a href="{{ route('admin.reports.unpaid-contacts') }}" class="inline-flex w-full items-center justify-center gap-3 rounded-2xl bg-amber-500 px-5 py-4 text-sm font-black text-slate-950 shadow-lg shadow-amber-500/20 transition-all hover:bg-amber-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 7l5-5m0 0l5 5m-5-5v13"/>
                                </svg>
                                Download Phone Numbers CSV
                            </a>
                        </div>
                    </div>

                    <!-- 2. Format Selection Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-5 border-b border-slate-100 dark:border-gray-700 flex items-center gap-4">
                            <div class="p-2.5 bg-green-100 dark:bg-green-900/40 rounded-xl text-green-600 dark:text-green-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Output Format</h2>
                                <p class="text-xs text-slate-500 dark:text-gray-400">Step 3: Choose delivery method</p>
                            </div>
                        </div>
                        <div class="p-6 space-y-4">
                            <label class="group relative flex items-center p-4 rounded-2xl border-2 transition-all cursor-pointer" 
                                   :class="format === 'csv' ? 'border-green-500 bg-green-50/50 dark:bg-green-900/10' : 'border-slate-100 dark:border-gray-700 hover:border-slate-300'">
                                <input type="radio" name="format" value="csv" x-model="format" class="sr-only">
                                <div class="w-10 h-10 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-green-600 mr-4 group-hover:scale-110 transition-transform">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <span class="block text-sm font-bold text-slate-900 dark:text-white">Excel / CSV</span>
                                    <span class="block text-xs text-slate-500 dark:text-gray-400">Spreadsheet ready</span>
                                </div>
                                <div x-show="format === 'csv'" class="ml-auto">
                                    <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                </div>
                            </label>

                            <label class="group relative flex items-center p-4 rounded-2xl border-2 transition-all cursor-pointer"
                                   :class="format === 'pdf' ? 'border-red-500 bg-red-50/50 dark:bg-red-900/10' : 'border-slate-100 dark:border-gray-700 hover:border-slate-300'">
                                <input type="radio" name="format" value="pdf" x-model="format" class="sr-only">
                                <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center text-red-600 mr-4 group-hover:scale-110 transition-transform">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <span class="block text-sm font-bold text-slate-900 dark:text-white">PDF Report</span>
                                    <span class="block text-xs text-slate-500 dark:text-gray-400">Perfect for printing</span>
                                </div>
                                <div x-show="format === 'pdf'" class="ml-auto">
                                    <svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Right Side: Column Selection --}}
                <div class="lg:col-span-8 space-y-8">
                    
                    <!-- 3. Column Selector Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-[2rem] shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-8 py-6 border-b border-slate-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-blue-100 dark:bg-blue-900/40 rounded-2xl text-blue-600 dark:text-blue-400">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Precision Columns</h2>
                                    <p class="text-sm text-slate-500 dark:text-gray-400 font-light">Step 2: Define your report structure</p>
                                </div>
                            </div>
                            
                            {{-- Presets Toolbar --}}
                            <div class="flex flex-wrap gap-2 p-1.5 bg-slate-50 dark:bg-gray-750 rounded-2xl border border-slate-200 dark:border-gray-600">
                                <button type="button" @click="applyPreset('default')" class="px-3 py-1.5 text-xs font-bold rounded-xl transition-all hover:bg-white dark:hover:bg-gray-700 hover:shadow-sm" :class="selectedColumns.length === 5 ? 'bg-white dark:bg-gray-700 shadow-sm text-blue-600' : 'text-slate-500'">Default</button>
                                <button type="button" @click="applyPreset('scientific')" class="px-3 py-1.5 text-xs font-bold rounded-xl transition-all hover:bg-white dark:hover:bg-gray-700 hover:shadow-sm" :class="selectedColumns.includes('average_score') ? 'bg-white dark:bg-gray-700 shadow-sm text-blue-600' : 'text-slate-500'">Review Report</button>
                                <button type="button" @click="applyPreset('contact')" class="px-3 py-1.5 text-xs font-bold rounded-xl transition-all hover:bg-white dark:hover:bg-gray-700 hover:shadow-sm" :class="selectedColumns.includes('author_email') ? 'bg-white dark:bg-gray-700 shadow-sm text-blue-600' : 'text-slate-500'">Directory</button>
                                <button type="button" @click="applyPreset('full')" class="px-3 py-1.5 text-xs font-bold rounded-xl transition-all hover:bg-white dark:hover:bg-gray-700 hover:shadow-sm" :class="selectedColumns.length > 8 ? 'bg-white dark:bg-gray-700 shadow-sm text-blue-600' : 'text-slate-500'">Master View</button>
                            </div>
                        </div>

                        <div class="p-8">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                                {{-- Column Group: Essential --}}
                                <div class="space-y-6">
                                    <div class="flex items-center gap-2 px-1">
                                        <div class="w-1.5 h-6 bg-blue-500 rounded-full"></div>
                                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Metadata & Core</h3>
                                    </div>
                                    <div class="grid gap-3">
                                        @foreach(['id' => 'Abstract ID', 'conference_code' => 'Conference Code', 'title' => 'Presentation Title', 'subtheme' => 'Scientific Subtheme', 'created_at' => 'Submission Date', 'updated_at' => 'Last Modified'] as $val => $label)
                                            <label class="group flex items-center p-4 rounded-2xl border-2 transition-all cursor-pointer select-none"
                                                   :class="isColumnSelected('{{ $val }}') ? 'border-blue-500/50 bg-blue-50/50 dark:bg-blue-900/10' : 'border-slate-50 dark:border-gray-750 hover:bg-slate-50 dark:hover:bg-gray-750'">
                                                <div class="relative flex items-center justify-center w-6 h-6 mr-4 transition-all">
                                                    <input type="checkbox" name="columns[]" value="{{ $val }}" class="sr-only" @change="toggleColumn('{{ $val }}')" :checked="isColumnSelected('{{ $val }}')">
                                                    <div class="absolute inset-0 border-2 rounded-lg transition-all" :class="isColumnSelected('{{ $val }}') ? 'bg-blue-600 border-blue-600 scale-110 shadow-lg shadow-blue-500/30' : 'border-slate-300 dark:border-gray-600 group-hover:border-blue-400'"></div>
                                                    <svg x-show="isColumnSelected('{{ $val }}')" class="w-4 h-4 text-white relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                </div>
                                                <span class="text-sm font-semibold transition-colors" :class="isColumnSelected('{{ $val }}') ? 'text-blue-700 dark:text-blue-400' : 'text-slate-600 dark:text-slate-400'">{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Column Group: Authorship --}}
                                <div class="space-y-6">
                                    <div class="flex items-center gap-2 px-1">
                                        <div class="w-1.5 h-6 bg-purple-500 rounded-full"></div>
                                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Author & Identity</h3>
                                    </div>
                                    <div class="grid gap-3">
                                        @foreach(['author_title' => 'Author Salutation', 'author_name' => 'Primary Author', 'author_email' => 'Contact Email', 'author_institute' => 'Affiliation / Lab', 'author_country' => 'Country of Origin'] as $val => $label)
                                            <label class="group flex items-center p-4 rounded-2xl border-2 transition-all cursor-pointer select-none"
                                                   :class="isColumnSelected('{{ $val }}') ? 'border-purple-500/50 bg-purple-50/50 dark:bg-purple-900/10' : 'border-slate-50 dark:border-gray-750 hover:bg-slate-50 dark:hover:bg-gray-750'">
                                                <div class="relative flex items-center justify-center w-6 h-6 mr-4 transition-all">
                                                    <input type="checkbox" name="columns[]" value="{{ $val }}" class="sr-only" @change="toggleColumn('{{ $val }}')" :checked="isColumnSelected('{{ $val }}')">
                                                    <div class="absolute inset-0 border-2 rounded-lg transition-all" :class="isColumnSelected('{{ $val }}') ? 'bg-purple-600 border-purple-600 scale-110 shadow-lg shadow-purple-500/30' : 'border-slate-300 dark:border-gray-600 group-hover:border-purple-400'"></div>
                                                    <svg x-show="isColumnSelected('{{ $val }}')" class="w-4 h-4 text-white relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                </div>
                                                <span class="text-sm font-semibold transition-colors" :class="isColumnSelected('{{ $val }}') ? 'text-purple-700 dark:text-purple-400' : 'text-slate-600 dark:text-slate-400'">{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Column Group: Peer Review --}}
                                <div class="space-y-6 md:col-span-2">
                                    <div class="flex items-center gap-2 px-1 border-t border-slate-100 dark:border-gray-700 pt-8">
                                        <div class="w-1.5 h-6 bg-pink-500 rounded-full"></div>
                                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Review Insights & Content</h3>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                        @foreach(['status' => 'Current Status', 'average_score' => 'Average Scoring', 'reviewer_names' => 'Reviewer Panel', 'recommendations' => 'Review Recommendations', 'description' => 'Abstract Full Content'] as $val => $label)
                                            <label class="group flex items-center p-4 rounded-2xl border-2 transition-all cursor-pointer select-none"
                                                   :class="isColumnSelected('{{ $val }}') ? 'border-pink-500/50 bg-pink-50/50 dark:bg-pink-900/10' : 'border-slate-50 dark:border-gray-750 hover:bg-slate-50 dark:hover:bg-gray-750'">
                                                <div class="relative flex items-center justify-center w-6 h-6 mr-4 transition-all">
                                                    <input type="checkbox" name="columns[]" value="{{ $val }}" class="sr-only" @change="toggleColumn('{{ $val }}')" :checked="isColumnSelected('{{ $val }}')">
                                                    <div class="absolute inset-0 border-2 rounded-lg transition-all" :class="isColumnSelected('{{ $val }}') ? 'bg-pink-600 border-pink-600 scale-110 shadow-lg shadow-pink-500/30' : 'border-slate-300 dark:border-gray-600 group-hover:border-pink-400'"></div>
                                                    <svg x-show="isColumnSelected('{{ $val }}')" class="w-4 h-4 text-white relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                </div>
                                                <span class="text-sm font-semibold transition-colors" :class="isColumnSelected('{{ $val }}') ? 'text-pink-700 dark:text-pink-400' : 'text-slate-600 dark:text-slate-400'">{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Enhanced Action Buttons -->
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-6 p-8 bg-gradient-to-r from-slate-900 to-slate-800 rounded-[2rem] shadow-2xl relative overflow-hidden">
                        {{-- Background Pattern --}}
                        <div class="absolute inset-0 opacity-10 pointer-events-none">
                            <svg class="h-full w-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                                <rect fill="url(#grid-pattern)" width="100" height="100"/>
                                <defs>
                                    <pattern id="grid-pattern" width="10" height="10" patternUnits="userSpaceOnUse">
                                        <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
                                    </pattern>
                                </defs>
                            </svg>
                        </div>

                        <div class="relative z-10">
                            <h4 class="text-white font-bold text-xl mb-1">Ready to finalize?</h4>
                            <p class="text-slate-400 text-sm">Your report will be processed based on your selections.</p>
                        </div>

                        <div class="flex items-center gap-4 relative z-10 w-full sm:w-auto">
                            <a href="{{ route('admin.dashboard') }}" class="flex-1 sm:flex-none text-center px-8 py-4 rounded-2xl bg-white/5 hover:bg-white/10 text-white font-bold border border-white/10 transition-all hover:scale-105">
                                Back
                            </a>
                            <button type="submit" 
                                    class="flex-1 sm:flex-none px-8 py-4 rounded-2xl font-bold text-white transition-all transform hover:scale-105 active:scale-95 shadow-xl disabled:opacity-50 disabled:pointer-events-none flex items-center justify-center gap-3"
                                    :class="format === 'pdf' ? 'bg-gradient-to-r from-red-600 to-pink-600 shadow-red-500/20' : 'bg-gradient-to-r from-green-600 to-emerald-600 shadow-green-500/20'"
                                    :disabled="selectedColumns.length === 0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Confirm & Generate
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>

@endsection
