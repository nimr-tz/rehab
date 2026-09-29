@extends('layouts.app')
@section('title', 'Reception')

@push('styles')
<style>
    .rec-page { background: #f8fafc; min-height: 100vh; color: #0f172a; font-family: 'Outfit', sans-serif; }
    .rec-mono { font-family: 'JetBrains Mono', 'Fira Code', ui-monospace, monospace; }
    .tab-pill { padding: 6px 20px; border-radius: 999px; font-size: 10px; font-weight: 900; letter-spacing: 0.12em; text-transform: uppercase; transition: all .18s; }
    .tab-pill.active { background: #0f172a; color: #fff; }
    .tab-pill:not(.active) { color: #94a3b8; }
    .tab-pill:not(.active):hover { color: #475569; background: #f1f5f9; }
    .search-hero input { caret-color: #7c3aed; }
    .search-hero input::placeholder { color: #cbd5e1; }
    .result-row { border-bottom: 1px solid #f1f5f9; transition: background .12s; }
    .result-row:hover { background: #f8fafc; }
    .sev-critical { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
    .sev-high     { background: #fff7ed; color: #ea580c; border-color: #fed7aa; }
    .sev-medium   { background: #fefce8; color: #ca8a04; border-color: #fde68a; }
    .sev-low      { background: #f8fafc; color: #64748b; border-color: #e2e8f0; }
    .reg-row { border-bottom: 1px solid #f1f5f9; }
    .reg-row:hover td { background: #f8fafc !important; }
    .reg-row:hover .rname-edit-btn, .reg-row:hover .rinst-edit-btn { opacity:1 !important; }
    .rname-edit-btn:hover, .rinst-edit-btn:hover { color:#7c3aed !important; background:#f5f3ff !important; }
    .pulse-dot { width: 7px; height: 7px; border-radius: 50%; background: #10b981; box-shadow: 0 0 0 0 rgba(16,185,129,.5); animation: pulse-ring 1.6s infinite; flex-shrink:0; }
    @keyframes pulse-ring { 0%{box-shadow:0 0 0 0 rgba(16,185,129,.4)} 70%{box-shadow:0 0 0 8px rgba(16,185,129,0)} 100%{box-shadow:0 0 0 0 rgba(16,185,129,0)} }
    .checkin-feed { max-height: 340px; overflow-y: auto; scrollbar-width: thin; scrollbar-color: #e2e8f0 transparent; }
    .modal-bg { background: rgba(100,116,139,.25); backdrop-filter: blur(8px); }
    .rec-input { background: #f8fafc; border: 1px solid #e2e8f0; color: #0f172a; border-radius: 10px; padding: 10px 14px; font-size: 13px; font-weight: 600; width: 100%; transition: border-color .15s; display: block; }
    .rec-input:focus { outline: none; border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,.08); }
    .rec-input::placeholder { color: #cbd5e1; }
    .rec-btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; border-radius: 10px; font-size: 9px; font-weight: 900; letter-spacing: .13em; text-transform: uppercase; cursor: pointer; transition: all .15s; border: none; text-decoration: none; }
    .rec-btn-primary { background: #7c3aed; color: #fff; }
    .rec-btn-primary:hover { background: #6d28d9; color: #fff; }
    .rec-btn-ghost { background: #f1f5f9; color: #64748b; }
    .rec-btn-ghost:hover { background: #e2e8f0; color: #1e293b; }
    .rec-btn-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
    .rec-btn-danger:hover { background: #fee2e2; color: #dc2626; }
    .badge-pill { padding: 2px 8px; border-radius: 999px; font-size: 8px; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; }
</style>
@endpush

@section('content')
<div class="rec-page">

{{-- ══ COMMAND BAR ══════════════════════════════════════════════ --}}
<div style="background:#ffffff; border-bottom:1px solid #e2e8f0; box-shadow:0 1px 3px rgba(0,0,0,.04);" class="sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 flex items-center justify-between gap-6" style="height:56px;">

        <div class="flex items-center gap-5 flex-shrink-0">
            <div class="flex items-center gap-2">
                <div class="pulse-dot"></div>
                <span class="rec-mono text-[10px] font-bold uppercase tracking-widest" style="color:#94a3b8;">Live · Day {{ $headerStats['current_day'] }}</span>
            </div>
            <div class="h-4 w-px" style="background:#e2e8f0;"></div>
            <span class="text-lg font-black tracking-tight" style="color:#0f172a;">Reception</span>
        </div>

        <div class="hidden md:flex items-center gap-6">
            @php $inlineStats = [
                ['n' => $headerStats['checked_in_today'], 'l' => 'In Today',     'c' => '#059669', 'href' => route('registration.dashboard', ['tab'=>'registry','status'=>'checkedin'])],
                ['n' => $headerStats['pending_checkin'],  'l' => 'Awaiting',     'c' => '#d97706', 'href' => route('registration.dashboard', ['tab'=>'registry','status'=>'pending_checkin'])],
                ['n' => $headerStats['paid_count'],       'l' => 'Verified',     'c' => '#7c3aed', 'href' => route('registration.dashboard', ['tab'=>'registry','status'=>'paid'])],
                ['n' => $headerStats['badges_today'],     'l' => 'Printed Today','c' => '#0891b2', 'href' => route('registration.dashboard', ['tab'=>'prints'])],
                ['n' => $headerStats['onsite_count'],     'l' => 'Walk-Ins',     'c' => '#64748b', 'href' => route('registration.dashboard', ['tab'=>'prints','print_type'=>'onsite_visitor'])],
                ['n' => $headerStats['total_registered'], 'l' => 'Registered',   'c' => '#94a3b8', 'href' => route('registration.dashboard', ['tab'=>'registry','status'=>'all'])],
            ]; @endphp
            @foreach($inlineStats as $s)
            <a href="{{ $s['href'] }}" class="flex items-baseline gap-1.5" style="text-decoration:none;padding:4px 8px;border-radius:8px;transition:background .12s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                <span class="rec-mono text-xl font-black" style="color:{{ $s['c'] }}">{{ $s['n'] }}</span>
                <span style="font-size:9px;font-weight:900;letter-spacing:.12em;text-transform:uppercase;color:#94a3b8;">{{ $s['l'] }}</span>
            </a>
            @if(!$loop->last)<div class="h-4 w-px" style="background:#f1f5f9;"></div>@endif
            @endforeach
        </div>

        <div class="flex items-center gap-3 flex-shrink-0">
            <div class="flex items-center gap-1" style="background:#f1f5f9;border-radius:999px;padding:4px;">
                <a href="{{ route('registration.dashboard', ['tab' => 'desk']) }}" class="tab-pill {{ $activeTab === 'desk' ? 'active' : '' }}">Desk</a>
                <a href="{{ route('registration.dashboard', ['tab' => 'registry']) }}" class="tab-pill {{ $activeTab === 'registry' ? 'active' : '' }}">Registry</a>
                <a href="{{ route('registration.dashboard', ['tab' => 'prints']) }}" class="tab-pill {{ $activeTab === 'prints' ? 'active' : '' }}">Print Log</a>
            </div>
            <a href="{{ route('registration.attendance.scanner') }}" class="rec-btn rec-btn-ghost" style="padding:7px 12px;font-size:8px;">
                <svg style="width:11px;height:11px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 3.5V16m-8 0v.5m0 0V20m0 0H8"/></svg>
                Scanner
            </a>
        </div>
    </div>
</div>

{{-- ══ PAGE BODY ════════════════════════════════════════════════ --}}
<div class="max-w-7xl mx-auto px-6 lg:px-8 py-8">

@if($activeTab === 'desk')
{{-- ─── DESK TAB ─────────────────────────────────────────────── --}}

<div class="mb-8 search-hero">
    <div class="relative" style="background:#ffffff;border:2px solid #7c3aed;border-radius:16px;overflow:hidden;box-shadow:0 0 0 4px rgba(124,58,237,.08), 0 4px 20px rgba(124,58,237,.1);">
        <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none">
            <svg id="search-icon" style="width:22px;height:22px;color:#7c3aed;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <svg id="search-spinner" style="width:22px;height:22px;color:#7c3aed;" class="hidden animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
        </div>
        <input type="text" id="search-input" autofocus
               placeholder="Search by name, email, or paste QR token…"
               style="background:transparent;border:none;width:100%;padding:22px 100px 22px 54px;font-size:clamp(1rem,2vw,1.4rem);font-weight:800;color:#0f172a;letter-spacing:-.01em;"
               class="focus:outline-none">
        <div id="search-kbd" class="absolute inset-y-0 right-0 pr-6 flex items-center pointer-events-none">
            <kbd style="background:#f5f3ff;border:1px solid #ddd6fe;border-radius:6px;padding:2px 8px;font-size:10px;color:#7c3aed;font-family:monospace;">⌘K</kbd>
        </div>
    </div>
    <div id="search-results" class="hidden mt-1" style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,.06);"></div>
    <div id="no-results" class="hidden mt-6 text-center py-10">
        <p style="color:#64748b;font-weight:700;font-size:13px;margin-bottom:4px;">No match found.</p>
        <p style="color:#94a3b8;font-size:11px;margin-bottom:16px;">Not in the system? Print a walk-in badge below.</p>
        <button onclick="focusWalkin()" class="rec-btn rec-btn-primary">Add Walk-In</button>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Live check-in feed --}}
    <div class="lg:col-span-2" style="border-radius:16px;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 4px 24px rgba(124,58,237,.15);">
        {{-- Gradient header --}}
        <div class="flex items-center justify-between px-6 py-4" style="background:linear-gradient(135deg,#7c3aed,#4f46e5);">
            <div class="flex items-center gap-2.5">
                <div class="pulse-dot" style="background:#a5f3fc;box-shadow:0 0 0 0 rgba(165,243,252,.6);"></div>
                <span style="font-size:9px;font-weight:900;letter-spacing:.15em;text-transform:uppercase;color:rgba(255,255,255,.7);">Live Check-In Feed</span>
            </div>
            <span class="rec-mono" style="font-size:11px;font-weight:700;color:#a5f3fc;">{{ $headerStats['checked_in_today'] }} today</span>
        </div>

        {{-- White body --}}
        <div style="background:#ffffff;border:1px solid #e2e8f0;border-top:none;flex:1;display:flex;flex-direction:column;">
        @if($recentCheckins->isEmpty())
        <div class="flex-1 flex items-center justify-center py-16 text-center">
            <p style="color:#cbd5e1;font-size:13px;font-weight:700;">No check-ins yet today.</p>
        </div>
        @else
        <div class="checkin-feed flex-1">
            @foreach($recentCheckins as $checkin)
            @php
                $person = $checkin->user ?? $checkin->groupMember ?? $checkin->onsiteVisitor;
                $pName = $person
                    ? ($person->full_name ?? $person->effective_name ?? trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')))
                    : 'Unknown';
                $pAffil = $person?->affiliation ?? $person?->institution ?? '';
                $pInit = collect(preg_split('/\s+/', $pName))->take(2)->map(fn($p)=>strtoupper(substr($p,0,1)))->implode('');
            @endphp
            <div class="result-row flex items-center gap-4 px-6 py-4">
                <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#7c3aed,#4f46e5);display:flex;align-items:center;justify-content:center;font-weight:900;font-size:12px;color:#fff;flex-shrink:0;">{{ $pInit }}</div>
                <div class="flex-1 min-w-0">
                    <p style="font-weight:800;font-size:13px;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $pName }}</p>
                    @if($pAffil)<p style="font-size:10px;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ Str::limit($pAffil, 40) }}</p>@endif
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="rec-mono" style="font-size:10px;font-weight:700;color:#7c3aed;">{{ $checkin->checked_in_at->format('g:i A') }}</p>
                    <p style="font-size:8px;color:#cbd5e1;text-transform:uppercase;letter-spacing:.1em;margin-top:1px;">checked in</p>
                </div>
            </div>
            @endforeach
        </div>
        <div class="px-6 py-4" style="border-top:1px solid #f1f5f9;">
            <a href="{{ route('registration.dashboard', ['tab' => 'registry']) }}" class="rec-btn rec-btn-ghost w-full justify-center">Open Full Registry →</a>
        </div>
        @endif
        </div>
    </div>

    {{-- Walk-in form --}}
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.04);">
        <div class="px-6 py-4" style="border-bottom:1px solid #f1f5f9;">
            <p style="font-size:9px;font-weight:900;letter-spacing:.15em;text-transform:uppercase;color:#94a3b8;margin-bottom:2px;">Not Registered?</p>
            <p style="font-size:14px;font-weight:900;color:#0f172a;">Walk-In Quick Print</p>
        </div>
        <form id="walkin-form" class="px-6 py-5" style="display:flex;flex-direction:column;gap:12px;">
            @csrf
            <div>
                <label style="display:block;font-size:9px;font-weight:900;letter-spacing:.15em;text-transform:uppercase;color:#94a3b8;margin-bottom:6px;">Full Name <span style="color:#dc2626;">*</span></label>
                <input type="text" name="name" id="walkin-name" required placeholder="Dr. Jane Smith" class="rec-input">
            </div>
            <div>
                <label style="display:block;font-size:9px;font-weight:900;letter-spacing:.15em;text-transform:uppercase;color:#94a3b8;margin-bottom:6px;">Institution</label>
                <input type="text" name="institution" id="walkin-institution" placeholder="Organization" class="rec-input">
            </div>
            <div>
                <label style="display:block;font-size:9px;font-weight:900;letter-spacing:.15em;text-transform:uppercase;color:#94a3b8;margin-bottom:6px;">Category</label>
                <select name="badge_category" class="rec-input">
                    <option value="invitee">Invitee</option>
                    <option value="vip">VIP</option>
                    <option value="guest">Guest</option>
                    <option value="speaker">Speaker</option>
                    <option value="media">Media</option>
                    <option value="staff">Staff</option>
                </select>
            </div>
            <div id="walkin-flags" class="hidden rounded-xl p-3" style="background:#fffbeb;border:1px solid #fde68a;">
                <p style="font-size:8px;font-weight:900;letter-spacing:.12em;text-transform:uppercase;color:#d97706;margin-bottom:6px;">Data Warning</p>
                <div id="walkin-flag-list"></div>
            </div>
            <button type="submit" id="walkin-submit" class="rec-btn rec-btn-primary w-full justify-center">
                <svg style="width:13px;height:13px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Create &amp; Print Badge
            </button>
        </form>

        @if($recentOnsiteVisitors->isNotEmpty())
        <div class="px-6 pb-5" style="border-top:1px solid #f1f5f9;padding-top:16px;">
            <p style="font-size:8px;font-weight:900;letter-spacing:.12em;text-transform:uppercase;color:#cbd5e1;margin-bottom:10px;">Recent Walk-Ins</p>
            @foreach($recentOnsiteVisitors as $v)
            <div class="flex items-center justify-between" style="padding:5px 0;">
                <div class="flex items-center gap-2 min-w-0">
                    <div style="width:26px;height:26px;border-radius:7px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:900;color:#94a3b8;flex-shrink:0;">{{ $v->initials }}</div>
                    <p style="font-size:11px;font-weight:700;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $v->name }}</p>
                </div>
                <a href="{{ route('registration.onsite.print-badge', $v) }}" target="_blank" style="font-size:8px;font-weight:900;letter-spacing:.1em;text-transform:uppercase;color:#94a3b8;flex-shrink:0;margin-left:8px;text-decoration:none;transition:color .12s;" onmouseover="this.style.color='#7c3aed'" onmouseout="this.style.color='#94a3b8'">Reprint</a>
            </div>
            @endforeach
            <a href="{{ route('registration.onsite.index') }}" style="display:block;margin-top:10px;text-align:center;font-size:9px;font-weight:900;letter-spacing:.1em;text-transform:uppercase;color:#cbd5e1;text-decoration:none;transition:color .12s;" onmouseover="this.style.color='#94a3b8'" onmouseout="this.style.color='#cbd5e1'">All Walk-Ins →</a>
        </div>
        @endif
    </div>

</div>

@else
{{-- ─── REGISTRY TAB ─────────────────────────────────────────── --}}

<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-5">
    <div class="flex flex-wrap gap-2">
        @foreach([
            ['status'=>'all',             'label'=>'All',       'n'=>$regStats['total'],          'c'=>'#818cf8'],
            ['status'=>'paid',            'label'=>'Paid',      'n'=>$regStats['paid'],            'c'=>'#34d399'],
            ['status'=>'unpaid',          'label'=>'Unpaid',    'n'=>$regStats['unpaid'],          'c'=>'#fbbf24'],
            ['status'=>'flagged',         'label'=>'Flagged',   'n'=>$regStats['flagged'],         'c'=>'#f87171'],
            ['status'=>'checkedin',       'label'=>'In Today',  'n'=>$regStats['checkedin'],       'c'=>'#059669'],
            ['status'=>'pending_checkin', 'label'=>'Awaiting',  'n'=>$regStats['pending_checkin'], 'c'=>'#d97706'],
        ] as $f)
        <a href="{{ route('registration.dashboard', ['tab'=>'registry','status'=>$f['status'],'search'=>$regSearch]) }}"
           style="{{ $regStatus===$f['status'] ? 'background:#f0f4ff;border:1px solid #c7d2fe;color:#3730a3;' : 'background:transparent;border:1px solid #e2e8f0;color:#64748b;' }}border-radius:999px;padding:6px 14px;font-size:9px;font-weight:900;letter-spacing:.1em;text-transform:uppercase;display:inline-flex;align-items:center;gap:6px;text-decoration:none;transition:all .15s;">
            {{ $f['label'] }}
            <span class="rec-mono" style="color:{{ $f['c'] }};font-size:10px;">{{ $f['n'] }}</span>
        </a>
        @endforeach
    </div>
    <div class="flex items-center gap-2">
        <div id="bulk-actions" class="hidden">
            <button onclick="openPrePrintModal()" class="rec-btn rec-btn-primary" style="font-size:8px;padding:8px 14px;">
                <svg style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print <span id="selected-count">0</span> Selected
            </button>
        </div>
        <button onclick="confirmPrintAll()" class="rec-btn rec-btn-ghost" style="font-size:8px;padding:8px 14px;">Print All Paid</button>
        <a href="{{ route('registration.attendance.report') }}" class="rec-btn rec-btn-ghost" style="font-size:8px;padding:8px 14px;">
            <svg style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Attendance &amp; Export
        </a>
    </div>
</div>

<form method="GET" action="{{ route('registration.dashboard') }}" class="mb-4 flex gap-2">
    <input type="hidden" name="tab" value="registry">
    <input type="hidden" name="status" value="{{ $regStatus }}">
    <div class="flex-1 relative">
        <div style="position:absolute;top:0;bottom:0;left:0;padding-left:14px;display:flex;align-items:center;pointer-events:none;">
            <svg style="width:13px;height:13px;color:#4d5566;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <input type="text" name="search" value="{{ $regSearch }}" placeholder="Filter by name, email, affiliation…"
               class="rec-input" style="padding-left:36px;">
    </div>
    <button type="submit" class="rec-btn rec-btn-primary" style="font-size:8px;padding:8px 16px;">Search</button>
    @if($regSearch)<a href="{{ route('registration.dashboard', ['tab'=>'registry','status'=>$regStatus]) }}" class="rec-btn rec-btn-ghost" style="font-size:8px;padding:8px 16px;">Clear</a>@endif
</form>

<div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.04);">
    <div style="overflow-x:auto;">
        <table style="min-width:100%;border-collapse:collapse;">
            <thead>
                <tr style="border-bottom:1px solid #f1f5f9;background:#f8fafc;">
                    <th style="padding:12px 16px;text-align:center;width:42px;">
                        <input type="checkbox" id="select-all" style="accent-color:#7c3aed;width:14px;height:14px;cursor:pointer;">
                    </th>
                    <th style="padding:12px 20px;text-align:left;font-size:8px;font-weight:900;letter-spacing:.15em;text-transform:uppercase;color:#94a3b8;white-space:nowrap;">Delegate</th>
                    <th style="padding:12px 20px;text-align:left;font-size:8px;font-weight:900;letter-spacing:.15em;text-transform:uppercase;color:#94a3b8;white-space:nowrap;">Affiliation</th>
                    <th style="padding:12px 20px;text-align:left;font-size:8px;font-weight:900;letter-spacing:.15em;text-transform:uppercase;color:#94a3b8;white-space:nowrap;">Payment</th>
                    <th style="padding:12px 20px;text-align:left;font-size:8px;font-weight:900;letter-spacing:.15em;text-transform:uppercase;color:#94a3b8;white-space:nowrap;">Quality</th>
                    <th style="padding:12px 16px;text-align:right;font-size:8px;font-weight:900;letter-spacing:.15em;text-transform:uppercase;color:#94a3b8;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendees as $attendee)
                @php
                    $isPaid = in_array($attendee['payment_status'], ['verified','waived']);
                    $qFlagged = $attendee['quality_flagged'] ?? false;
                    $qFlags   = $attendee['quality_flags'] ?? [];
                    $worst    = collect($qFlags)->pluck('severity')->sort()->first();
                    $ini = strtoupper(collect(explode(' ',$attendee['name']))->take(2)->map(fn($p)=>substr($p,0,1))->implode(''));
                    $profileUrl = match($attendee['type']) {
                        'group_member'           => route('registration.group-member-details', $attendee['id']),
                        default                  => route('registration.attendee-details', $attendee['id']),
                    };
                @endphp
                @php
                    $effectiveName      = $attendee['print_name'] ?? $attendee['name'];
                    $hasOverride        = !empty($attendee['print_name']) && $attendee['print_name'] !== $attendee['name'];
                    $effectiveInstitute = $attendee['print_institute'] ?? ($attendee['affiliation'] ?? '');
                    $hasInstOverride    = !empty($attendee['print_institute']);
                    $rowId = $attendee['type'] . '-' . $attendee['id'];
                @endphp
                <tr class="reg-row" id="reg-row-{{ $rowId }}">
                    <td style="padding:14px 16px;text-align:center;{{ $qFlagged ? 'background:#fef2f2;' : '' }}">
                        <input type="checkbox" value="{{ $attendee['id'] }}" data-type="{{ $attendee['type'] }}"
                               class="attendee-checkbox" style="accent-color:#7c3aed;width:14px;height:14px;cursor:pointer;{{ !$isPaid ? 'opacity:.3;cursor:not-allowed;' : '' }}"
                               {{ !$isPaid ? 'disabled' : '' }}>
                    </td>
                    <td style="padding:14px 20px;{{ $qFlagged ? 'background:#fef2f2;' : '' }}">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="position:relative;flex-shrink:0;">
                                <div style="width:34px;height:34px;border-radius:9px;background:{{ $qFlagged ? '#fee2e2' : '#f1f5f9' }};display:flex;align-items:center;justify-content:center;font-weight:900;font-size:11px;color:{{ $qFlagged ? '#dc2626' : '#94a3b8' }};border:1px solid {{ $qFlagged ? '#fecaca' : '#e2e8f0' }};">{{ $ini }}</div>
                                @if($qFlagged)<div style="position:absolute;top:-4px;right:-4px;width:12px;height:12px;border-radius:50%;background:#dc2626;border:2px solid #fff;display:flex;align-items:center;justify-content:center;"><svg style="width:6px;height:6px;color:#fff;" fill="currentColor" viewBox="0 0 20 20"><path d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"/></svg></div>@endif
                            </div>
                            <div style="min-width:0;flex:1;">
                                {{-- Display state --}}
                                <div class="rname-display" style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:2px;">
                                    <span class="rname-text" style="font-weight:800;font-size:13px;color:#0f172a;">{{ $effectiveName }}</span>
                                    @if($attendee['type']==='group_member')<span class="badge-pill" style="background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0;">Group</span>@endif
                                    <button onclick="startRegEdit('{{ $rowId }}')"
                                            style="opacity:0;padding:2px 5px;border:none;background:none;cursor:pointer;color:#94a3b8;border-radius:6px;transition:opacity .12s,color .12s;"
                                            class="rname-edit-btn" title="Edit badge name">
                                        <svg style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>
                                </div>
                                @if($hasOverride)
                                <p class="rname-original" style="font-size:9px;color:#94a3b8;margin-bottom:1px;">Original: {{ $attendee['name'] }}</p>
                                @endif
                                {{-- Edit state --}}
                                <div class="rname-edit" style="display:none;margin-top:4px;">
                                    <input type="text" class="rname-input rec-input"
                                           style="padding:5px 10px;font-size:12px;font-weight:700;border-radius:8px;width:100%;max-width:220px;"
                                           value="{{ $effectiveName }}" data-original="{{ $effectiveName }}"
                                           data-base-name="{{ $attendee['name'] }}"
                                           data-entity-type="{{ $attendee['type'] }}"
                                           data-entity-id="{{ is_string($attendee['id']) && str_contains($attendee['id'],':') ? 0 : (int)$attendee['id'] }}">
                                    <div style="display:flex;gap:6px;margin-top:5px;">
                                        <button onclick="saveRegName('{{ $rowId }}')"
                                                style="padding:3px 10px;background:#7c3aed;color:#fff;border:none;border-radius:6px;font-size:9px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;cursor:pointer;">Save</button>
                                        <button onclick="cancelRegEdit('{{ $rowId }}')"
                                                style="padding:3px 10px;background:#f1f5f9;color:#64748b;border:none;border-radius:6px;font-size:9px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;cursor:pointer;">Cancel</button>
                                    </div>
                                </div>
                                <p style="font-size:10px;color:#94a3b8;margin-top:1px;">{{ $attendee['email'] }}</p>
                            </div>
                        </div>
                    </td>
                    <td style="padding:14px 20px;{{ $qFlagged ? 'background:#fef2f2;' : '' }}">
                        <div class="rinst-display" style="display:flex;align-items:center;gap:5px;">
                            <p class="rinst-text" style="font-size:12px;font-weight:700;color:{{ $hasInstOverride ? '#7c3aed' : '#64748b' }};margin:0;">{{ Str::limit($effectiveInstitute ?: '—', 28) }}</p>
                            <button onclick="startInstEdit('{{ $rowId }}')"
                                    style="opacity:0;padding:2px 5px;border:none;background:none;cursor:pointer;color:#94a3b8;border-radius:6px;transition:opacity .12s,color .12s;"
                                    class="rinst-edit-btn" title="Edit badge institution">
                                <svg style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </button>
                        </div>
                        @if($hasInstOverride)
                        <p class="rinst-original" style="font-size:9px;color:#94a3b8;margin:0;">Orig: {{ Str::limit($attendee['affiliation'] ?? '', 22) }}</p>
                        @endif
                        <div class="rinst-edit" style="display:none;margin-top:4px;">
                            <input type="text" class="rinst-input rec-input"
                                   style="padding:5px 10px;font-size:12px;font-weight:700;border-radius:8px;width:100%;max-width:220px;"
                                   value="{{ $effectiveInstitute }}" data-original="{{ $effectiveInstitute }}"
                                   data-base-inst="{{ $attendee['affiliation'] ?? '' }}"
                                   data-entity-type="{{ $attendee['type'] }}"
                                   data-entity-id="{{ is_string($attendee['id']) && str_contains($attendee['id'],':') ? 0 : (int)$attendee['id'] }}">
                            <div style="display:flex;gap:6px;margin-top:5px;">
                                <button onclick="saveRegInst('{{ $rowId }}')"
                                        style="padding:3px 10px;background:#7c3aed;color:#fff;border:none;border-radius:6px;font-size:9px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;cursor:pointer;">Save</button>
                                <button onclick="cancelInstEdit('{{ $rowId }}')"
                                        style="padding:3px 10px;background:#f1f5f9;color:#64748b;border:none;border-radius:6px;font-size:9px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;cursor:pointer;">Cancel</button>
                            </div>
                        </div>
                    </td>
                    <td style="padding:14px 20px;{{ $qFlagged ? 'background:#fef2f2;' : '' }}">
                        @if($attendee['payment_status'] === 'verified')
                            <div style="display:flex;align-items:center;gap:5px;"><div style="width:6px;height:6px;border-radius:50%;background:#059669;flex-shrink:0;"></div><span style="font-size:9px;font-weight:900;letter-spacing:.1em;text-transform:uppercase;color:#059669;">Paid</span></div>
                        @elseif($attendee['payment_status']==='waived')
                            <div style="display:flex;align-items:center;gap:5px;"><div style="width:6px;height:6px;border-radius:50%;background:#7c3aed;flex-shrink:0;"></div><span style="font-size:9px;font-weight:900;letter-spacing:.1em;text-transform:uppercase;color:#7c3aed;">Waived</span></div>
                        @else
                            <div style="display:flex;align-items:center;gap:5px;"><div class="pulse-dot" style="width:6px;height:6px;"></div><span style="font-size:9px;font-weight:900;letter-spacing:.1em;text-transform:uppercase;color:#d97706;">Pending</span></div>
                        @endif
                    </td>
                    <td style="padding:14px 20px;{{ $qFlagged ? 'background:#fef2f2;' : '' }}">
                        @if($qFlagged && count($qFlags)>0)
                        <div style="position:relative;display:inline-block;" class="group/qt">
                            <span class="sev-{{ $worst }} badge-pill" style="border:1px solid;cursor:default;">{{ $worst }}</span>
                            <div style="position:absolute;bottom:calc(100% + 6px);left:0;width:220px;background:#ffffff;border:1px solid #e2e8f0;border-radius:10px;padding:10px;z-index:30;box-shadow:0 8px 24px rgba(0,0,0,.08);" class="hidden group-hover/qt:block">
                                @foreach(collect($qFlags)->take(3) as $fl)
                                <p style="font-size:9px;color:#475569;margin-bottom:3px;display:flex;align-items:flex-start;gap:5px;"><span style="color:#dc2626;margin-top:2px;flex-shrink:0;">•</span>{{ $fl['message'] }}</p>
                                @endforeach
                                @if(count($qFlags)>3)<p style="font-size:8px;color:#64748b;">+{{ count($qFlags)-3 }} more</p>@endif
                            </div>
                        </div>
                        @else
                        <span style="font-size:9px;font-weight:700;color:#e2e8f0;">Clean</span>
                        @endif
                    </td>
                    <td style="padding:14px 16px;text-align:right;{{ $qFlagged ? 'background:#fef2f2;' : '' }}">
                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:8px;">
                            @if($attendee['attended_today'] && $attendee['checked_in_at'])
                            <span class="rec-mono" style="font-size:8px;font-weight:700;color:#059669;">✓ {{ $attendee['checked_in_at']->format('g:i A') }}</span>
                            @endif
                            <a href="{{ $profileUrl }}" class="rec-btn rec-btn-ghost" style="padding:6px 14px;font-size:8px;">View</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="padding:60px;text-align:center;color:#4d5566;font-size:13px;font-weight:700;">No delegates match this filter.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($attendees->hasPages())
    <div style="padding:16px 24px;border-top:1px solid #f1f5f9;">
        {{ $attendees->withQueryString()->links() }}
    </div>
    @endif
</div>

@endif

@if($activeTab === 'prints')
{{-- ─── PRINT LOG TAB ────────────────────────────────────────── --}}

{{-- Stats row --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['n' => $printLogStats['today'],    'l' => 'Printed Today',  'c' => '#7c3aed', 'bg' => '#f5f3ff', 'border' => '#ddd6fe'],
        ['n' => $printLogStats['total'],    'l' => 'Total Badges',   'c' => '#0891b2', 'bg' => '#f0f9ff', 'border' => '#bae6fd'],
        ['n' => $printLogStats['reprints'], 'l' => 'Reprints',       'c' => '#d97706', 'bg' => '#fffbeb', 'border' => '#fde68a'],
        ['n' => $printLogStats['walkins'],  'l' => 'Walk-In Badges', 'c' => '#059669', 'bg' => '#ecfdf5', 'border' => '#a7f3d0'],
    ] as $s)
    <div style="background:{{ $s['bg'] }};border:1px solid {{ $s['border'] }};border-radius:14px;padding:18px 20px;">
        <p class="rec-mono" style="font-size:26px;font-weight:900;color:{{ $s['c'] }};line-height:1;">{{ $s['n'] }}</p>
        <p style="font-size:9px;font-weight:900;letter-spacing:.12em;text-transform:uppercase;color:{{ $s['c'] }};opacity:.7;margin-top:4px;">{{ $s['l'] }}</p>
    </div>
    @endforeach
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('registration.dashboard') }}" class="mb-4 flex flex-wrap gap-2 items-center">
    <input type="hidden" name="tab" value="prints">
    <div class="flex items-center gap-1" style="background:#f1f5f9;border-radius:999px;padding:4px;">
        @foreach(['all'=>'All','user'=>'Delegates','group_member'=>'Groups','onsite_visitor'=>'Walk-Ins'] as $val => $label)
        <a href="{{ route('registration.dashboard', ['tab'=>'prints','print_type'=>$val,'print_search'=>$printLogSearch]) }}"
           style="{{ $printLogFilter===$val ? 'background:#ffffff;color:#0f172a;box-shadow:0 1px 3px rgba(0,0,0,.08);' : 'color:#94a3b8;' }}padding:5px 14px;border-radius:999px;font-size:9px;font-weight:900;letter-spacing:.1em;text-transform:uppercase;transition:all .15s;text-decoration:none;display:inline-block;">{{ $label }}</a>
        @endforeach
    </div>
    <div class="flex-1 flex gap-2">
        <div class="flex-1 relative">
            <div style="position:absolute;top:0;bottom:0;left:0;padding-left:12px;display:flex;align-items:center;pointer-events:none;">
                <svg style="width:13px;height:13px;color:#94a3b8;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <input type="text" name="print_search" value="{{ $printLogSearch }}" placeholder="Search name or institution…" class="rec-input" style="padding-left:34px;">
        </div>
        <button type="submit" class="rec-btn rec-btn-primary" style="font-size:8px;padding:8px 16px;">Search</button>
        @if($printLogSearch)<a href="{{ route('registration.dashboard', ['tab'=>'prints','print_type'=>$printLogFilter]) }}" class="rec-btn rec-btn-ghost" style="font-size:8px;padding:8px 16px;">Clear</a>@endif
    </div>
</form>

@if(!$printLogsTableExists)
<div style="background:#fffbeb;border:1px solid #fde68a;border-radius:14px;padding:24px;text-align:center;">
    <p style="font-size:13px;font-weight:800;color:#d97706;">Print log table not yet migrated.</p>
    <p style="font-size:11px;color:#92400e;margin-top:4px;">Run <code>php artisan migrate</code> to activate badge print tracking.</p>
</div>
@elseif($printLogs->isEmpty())
<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:60px;text-align:center;">
    <p style="font-size:13px;font-weight:700;color:#94a3b8;">No badge prints recorded yet.</p>
    <p style="font-size:11px;color:#cbd5e1;margin-top:4px;">Print events will appear here as badges are printed.</p>
</div>
@else
<div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.04);">
    <div style="overflow-x:auto;">
        <table style="min-width:100%;border-collapse:collapse;">
            <thead>
                <tr style="border-bottom:1px solid #f1f5f9;background:#f8fafc;">
                    <th style="padding:11px 20px;text-align:left;font-size:8px;font-weight:900;letter-spacing:.15em;text-transform:uppercase;color:#94a3b8;">Name</th>
                    <th style="padding:11px 20px;text-align:left;font-size:8px;font-weight:900;letter-spacing:.15em;text-transform:uppercase;color:#94a3b8;">Institution</th>
                    <th style="padding:11px 16px;text-align:left;font-size:8px;font-weight:900;letter-spacing:.15em;text-transform:uppercase;color:#94a3b8;">Type</th>
                    <th style="padding:11px 16px;text-align:left;font-size:8px;font-weight:900;letter-spacing:.15em;text-transform:uppercase;color:#94a3b8;">Category</th>
                    <th style="padding:11px 16px;text-align:center;font-size:8px;font-weight:900;letter-spacing:.15em;text-transform:uppercase;color:#94a3b8;">#</th>
                    <th style="padding:11px 20px;text-align:right;font-size:8px;font-weight:900;letter-spacing:.15em;text-transform:uppercase;color:#94a3b8;">Printed At</th>
                </tr>
            </thead>
            <tbody>
                @foreach($printLogs as $log)
                @php
                    $typeColors = [
                        'user'             => ['bg'=>'#f5f3ff','c'=>'#7c3aed','label'=>'Delegate'],
                        'group_member'     => ['bg'=>'#f1f5f9','c'=>'#475569','label'=>'Group'],
                        'onsite_visitor'   => ['bg'=>'#f0f9ff','c'=>'#0891b2','label'=>'Walk-In'],
                    ];
                    $tc = $typeColors[$log->entity_type] ?? ['bg'=>'#f8fafc','c'=>'#64748b','label'=>ucfirst($log->entity_type)];
                    $isReprint = $log->print_number > 1;
                    $ini = collect(preg_split('/\s+/', $log->entity_name))->take(2)->map(fn($p)=>strtoupper(substr($p,0,1)))->implode('');
                @endphp
                <tr style="border-bottom:1px solid #f8fafc;transition:background .1s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                    <td style="padding:13px 20px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:32px;height:32px;border-radius:9px;background:{{ $tc['bg'] }};display:flex;align-items:center;justify-content:center;font-weight:900;font-size:11px;color:{{ $tc['c'] }};flex-shrink:0;">{{ $ini }}</div>
                            <div>
                                <p style="font-weight:800;font-size:13px;color:#0f172a;margin:0 0 1px;">{{ $log->entity_name }}</p>
                                @if($isReprint)
                                <span style="display:inline-block;background:#fffbeb;border:1px solid #fde68a;border-radius:999px;padding:1px 7px;font-size:8px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;color:#d97706;">Reprint #{{ $log->print_number }}</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td style="padding:13px 20px;"><p style="font-size:12px;color:#64748b;font-weight:600;margin:0;">{{ Str::limit($log->entity_institution ?? '—', 30) }}</p></td>
                    <td style="padding:13px 16px;"><span style="background:{{ $tc['bg'] }};color:{{ $tc['c'] }};border-radius:999px;padding:2px 9px;font-size:8px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;">{{ $tc['label'] }}</span></td>
                    <td style="padding:13px 16px;"><p style="font-size:11px;color:#94a3b8;font-weight:600;margin:0;">{{ Str::limit($log->entity_category ?? '—', 20) }}</p></td>
                    <td style="padding:13px 16px;text-align:center;"><span class="rec-mono" style="font-size:12px;font-weight:900;color:{{ $isReprint ? '#d97706' : '#059669' }};">{{ $log->print_number }}</span></td>
                    <td style="padding:13px 20px;text-align:right;">
                        <p class="rec-mono" style="font-size:11px;font-weight:700;color:#475569;margin:0;">{{ $log->printed_at->format('g:i A') }}</p>
                        <p style="font-size:9px;color:#cbd5e1;margin-top:1px;">{{ $log->printed_at->format('d M') }}</p>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($printLogs->hasPages())
    <div style="padding:16px 24px;border-top:1px solid #f1f5f9;">
        {{ $printLogs->withQueryString()->links() }}
    </div>
    @endif
</div>
@endif

@endif
</div>
</div>

{{-- ══ PRE-PRINT MODAL ══════════════════════════════════════════ --}}
<div id="preprint-modal" class="modal-bg fixed inset-0 hidden items-center justify-center z-[100] p-4">
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:20px;width:100%;max-width:580px;overflow:hidden;display:flex;flex-direction:column;max-height:88vh;box-shadow:0 24px 60px rgba(0,0,0,.12);">
        <div style="padding:20px 24px;border-bottom:1px solid #f1f5f9;display:flex;align-items:flex-start;justify-content:space-between;flex-shrink:0;">
            <div>
                <p style="font-size:14px;font-weight:900;color:#0f172a;">Pre-Print Quality Review</p>
                <p style="font-size:11px;color:#94a3b8;margin-top:2px;" id="modal-subtitle">Scanning selected records…</p>
            </div>
            <button onclick="closePreprintModal()" style="background:#f1f5f9;border:none;border-radius:8px;padding:6px;cursor:pointer;color:#64748b;line-height:0;">
                <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div id="modal-scanning" style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:60px 24px;flex:1;">
            <svg style="width:28px;height:28px;color:#7c3aed;margin-bottom:12px;" class="animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            <p style="color:#94a3b8;font-size:12px;font-weight:700;">Scanning badge data…</p>
        </div>
        <div id="modal-clean" class="hidden" style="display:none;flex-direction:column;align-items:center;justify-content:center;padding:60px 24px;text-align:center;flex:1;">
            <div style="width:44px;height:44px;background:#ecfdf5;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                <svg style="width:22px;height:22px;color:#059669;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p style="font-size:14px;font-weight:900;color:#0f172a;">All Clear</p>
            <p style="font-size:11px;color:#94a3b8;margin-top:4px;">No quality issues. Ready to print.</p>
        </div>
        <div id="modal-flags" class="hidden" style="display:none;overflow-y:auto;flex:1;">
            <div style="padding:12px 20px;background:#fef2f2;border-bottom:1px solid #fecaca;">
                <p style="font-size:12px;font-weight:900;color:#dc2626;" id="modal-flag-summary"></p>
                <p style="font-size:10px;color:#94a3b8;margin-top:2px;">Skip flagged records or override and print all.</p>
            </div>
            <div id="modal-flag-list" style="max-height:280px;overflow-y:auto;"></div>
        </div>
        <div id="modal-actions" class="hidden" style="display:none;padding:16px;border-top:1px solid #f1f5f9;gap:8px;flex-shrink:0;">
            <button onclick="printSelection('skip_flagged')" class="rec-btn rec-btn-ghost" style="flex:1;justify-content:center;">Skip Flagged</button>
            <button onclick="printSelection('all')" class="rec-btn rec-btn-primary" style="flex:1;justify-content:center;">Override &amp; Print All</button>
        </div>
        <div id="modal-clean-actions" class="hidden" style="display:none;padding:16px;border-top:1px solid #f1f5f9;flex-shrink:0;">
            <button onclick="printSelection('all')" class="rec-btn rec-btn-primary w-full justify-center">Print All Selected</button>
        </div>
    </div>
</div>

<div id="confirm-printall-modal" class="modal-bg fixed inset-0 hidden items-center justify-center z-[100]">
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:20px;padding:32px;max-width:400px;width:calc(100% - 32px);text-align:center;box-shadow:0 24px 60px rgba(0,0,0,.12);">
        <div style="width:44px;height:44px;background:#fffbeb;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <svg style="width:20px;height:20px;color:#d97706;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        </div>
        <p style="font-size:15px;font-weight:900;color:#0f172a;margin-bottom:6px;">Print All Paid Badges?</p>
        <p style="font-size:12px;color:#94a3b8;margin-bottom:24px;">Generates badges for every verified paid delegate. PDF may be large.</p>
        <div style="display:flex;gap:8px;">
            <button onclick="document.getElementById('confirm-printall-modal').classList.add('hidden');document.getElementById('confirm-printall-modal').classList.remove('flex');" class="rec-btn rec-btn-ghost" style="flex:1;justify-content:center;">Cancel</button>
            <a href="{{ route('registration.print-all-paid') }}" target="_blank"
               onclick="document.getElementById('confirm-printall-modal').classList.add('hidden');document.getElementById('confirm-printall-modal').classList.remove('flex');"
               class="rec-btn rec-btn-primary" style="flex:1;justify-content:center;">Print All</a>
        </div>
    </div>
</div>

@push('scripts')
<script>
// ── Desk ─────────────────────────────────────────────────────────────────────
@if($activeTab === 'desk')
(function(){
    const inp=document.getElementById('search-input');
    const res=document.getElementById('search-results');
    const none=document.getElementById('no-results');
    const icon=document.getElementById('search-icon');
    const spin=document.getElementById('search-spinner');
    const kbd=document.getElementById('search-kbd');
    let t;
    function sp(on){icon.classList.toggle('hidden',on);spin.classList.toggle('hidden',!on);if(kbd)kbd.classList.toggle('hidden',on);}
    inp.addEventListener('input',function(){
        clearTimeout(t);const q=this.value.trim();
        if(q.length<2){res.classList.add('hidden');none.classList.add('hidden');return;}
        t=setTimeout(()=>{
            sp(true);
            fetch(`/registration/search?q=${encodeURIComponent(q)}`).then(r=>r.json()).then(data=>{
                sp(false);
                if(data.attendees&&data.attendees.length){
                    res.innerHTML=data.attendees.map(a=>{
                        const paid=['verified','waived'].includes(a.payment_status);
                        const ini=(a.name||'??').split(' ').map(n=>n[0]).join('').substring(0,2).toUpperCase();
                        const url=a.type==='group_member'?`/registration/group-members/${a.id}`:`/registration/attendees/${a.id}`;
                        const dot=paid?'#059669':'#d97706';
                        return `<a href="${url}" style="display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 24px;border-bottom:1px solid #f1f5f9;text-decoration:none;transition:background .1s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div style="width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,#7c3aed,#4f46e5);display:flex;align-items:center;justify-content:center;font-weight:900;font-size:12px;color:#fff;flex-shrink:0;">${ini}</div>
                                <div><p style="font-weight:800;font-size:13px;color:#0f172a;margin:0 0 2px;">${a.name}</p><p style="font-size:10px;color:#94a3b8;margin:0;">${a.affiliation||a.email||''}</p></div>
                            </div>
                            <div style="display:flex;align-items:center;gap:8px;"><div style="display:flex;align-items:center;gap:4px;"><div style="width:6px;height:6px;border-radius:50%;background:${dot};"></div><span style="font-size:9px;font-weight:900;text-transform:uppercase;color:${dot};">${paid?'Paid':'Unpaid'}</span></div><svg style="width:13px;height:13px;color:#cbd5e1;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></div>
                        </a>`;
                    }).join('');
                    res.classList.remove('hidden');none.classList.add('hidden');
                }else{res.classList.add('hidden');none.classList.remove('hidden');}
            }).catch(()=>sp(false));
        },260);
    });
}());
(function(){
    const form=document.getElementById('walkin-form');
    const name=document.getElementById('walkin-name');
    const inst=document.getElementById('walkin-institution');
    const flagBox=document.getElementById('walkin-flags');
    const flagList=document.getElementById('walkin-flag-list');
    const btn=document.getElementById('walkin-submit');
    function check(){
        const n=name.value.trim(),i=inst.value.trim(),f=[];
        if(n.includes('@')||/\b[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}\b/i.test(n))f.push('Name contains an email address');
        if(n.length>3&&n===n.toUpperCase()&&/^[A-Z\s.\-]+$/.test(n))f.push('Name appears all-caps');
        if(i.includes('@'))f.push('Institution contains an email address');
        if(/https?:\/\//i.test(n+i))f.push('URL detected in a field');
        if(f.length&&n){flagList.innerHTML=f.map(x=>`<p style="font-size:9px;color:#fbbf24;margin:1px 0;">• ${x}</p>`).join('');flagBox.classList.remove('hidden');}
        else flagBox.classList.add('hidden');
    }
    name.addEventListener('input',check);inst.addEventListener('input',check);
    form.addEventListener('submit',e=>{
        e.preventDefault();if(!name.value.trim())return;
        btn.disabled=true;btn.innerHTML='Processing…';
        const fd=new FormData(form);
        fetch('{{ route("registration.onsite.create") }}',{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'},body:fd})
            .then(r=>r.json()).then(d=>{if(d.success){window.open(d.print_url,'_blank');form.reset();flagBox.classList.add('hidden');setTimeout(()=>location.reload(),500);}})
            .catch(()=>{btn.disabled=false;btn.innerHTML='Create &amp; Print Badge';});
    });
    window.focusWalkin=()=>{name.focus();name.scrollIntoView({behavior:'smooth'});};
}());
@endif

// ── Registry ─────────────────────────────────────────────────────────────────
@if($activeTab === 'registry')
(function(){
    const sa=document.getElementById('select-all');
    const ba=document.getElementById('bulk-actions');
    const cn=document.getElementById('selected-count');
    function upd(){const n=document.querySelectorAll('.attendee-checkbox:checked').length;cn.textContent=n;ba.style.display=n?'flex':'none';}
    sa?.addEventListener('change',()=>{document.querySelectorAll('.attendee-checkbox:not(:disabled)').forEach(c=>c.checked=sa.checked);upd();});
    document.querySelectorAll('.attendee-checkbox').forEach(c=>c.addEventListener('change',upd));
}());
let _flaggedIds=[];
function collectSelected(){const r={user_ids:[],group_member_ids:[]};document.querySelectorAll('.attendee-checkbox:checked').forEach(c=>{if(c.dataset.type==='user')r.user_ids.push(c.value);else if(c.dataset.type==='group_member')r.group_member_ids.push(c.value);});return r;}
function showModalEl(id){document.getElementById(id).style.display='';}
function hideModalEl(id){document.getElementById(id).style.display='none';}
function openPrePrintModal(){
    const m=document.getElementById('preprint-modal');m.classList.remove('hidden');m.classList.add('flex');
    showModalEl('modal-scanning');['modal-clean','modal-flags','modal-actions','modal-clean-actions'].forEach(hideModalEl);
    document.getElementById('modal-subtitle').textContent='Scanning selected records…';_flaggedIds=[];
    const p=collectSelected(),fd=new FormData();fd.append('_token',document.querySelector('meta[name="csrf-token"]').content);
    p.user_ids.forEach(id=>fd.append('user_ids[]',id));p.group_member_ids.forEach(id=>fd.append('group_member_ids[]',id));
    fetch('{{ route("registration.quality.scan") }}',{method:'POST',body:fd})
        .then(r=>r.json()).then(data=>{
            hideModalEl('modal-scanning');
            if(!data.flagged_count){
                showModalEl('modal-clean');showModalEl('modal-clean-actions');
                document.getElementById('modal-subtitle').textContent='All clean.';
            }else{
                _flaggedIds=data.records.map(r=>({type:r.type,id:r.id}));
                document.getElementById('modal-flag-summary').textContent=`${data.flagged_count} record${data.flagged_count>1?'s have':' has'} quality issues`;
                document.getElementById('modal-flag-list').innerHTML=data.records.map(r=>`<div style="padding:12px 20px;border-bottom:1px solid #f1f5f9;"><p style="font-weight:800;font-size:13px;color:#0f172a;margin:0 0 4px;">${r.name}</p>${r.flags.map(f=>`<p style="font-size:9px;color:#64748b;margin:1px 0;">• ${f.message}${f.suggestion?` <span style="color:#7c3aed;font-weight:700;">→ ${f.suggestion}</span>`:''}</p>`).join('')}</div>`).join('');
                showModalEl('modal-flags');showModalEl('modal-actions');
                document.getElementById('modal-subtitle').textContent=`${data.flagged_count} issue${data.flagged_count>1?'s':''} found`;
            }
        }).catch(()=>{hideModalEl('modal-scanning');showModalEl('modal-clean');showModalEl('modal-clean-actions');});
}
function closePreprintModal(){const m=document.getElementById('preprint-modal');m.classList.add('hidden');m.classList.remove('flex');}
function printSelection(mode){
    closePreprintModal();const p=collectSelected();
    if(mode==='skip_flagged')_flaggedIds.forEach(({type,id})=>{
        if(type==='user')p.user_ids=p.user_ids.filter(v=>v!=id);
        else if(type==='group_member')p.group_member_ids=p.group_member_ids.filter(v=>v!=id);
    });
    if(!p.user_ids.length&&!p.group_member_ids.length){alert('No eligible records remaining.');return;}
    const form=document.createElement('form');form.method='POST';form.action='{{ route("registration.bulk-print-badges") }}';form.target='_blank';
    const csrf=document.createElement('input');csrf.type='hidden';csrf.name='_token';csrf.value='{{ csrf_token() }}';form.appendChild(csrf);
    p.user_ids.forEach(id=>{const i=document.createElement('input');i.type='hidden';i.name='user_ids[]';i.value=id;form.appendChild(i);});
    p.group_member_ids.forEach(id=>{const i=document.createElement('input');i.type='hidden';i.name='group_member_ids[]';i.value=id;form.appendChild(i);});
    document.body.appendChild(form);form.submit();document.body.removeChild(form);
}
function confirmPrintAll(){const m=document.getElementById('confirm-printall-modal');m.classList.remove('hidden');m.classList.add('flex');}
@endif

// ── Registry inline name editing ─────────────────────────────────────────────
@if($activeTab === 'registry')
const OVERRIDE_URL = '{{ route("registration.registry.name-override") }}';
const REG_CSRF = document.querySelector('meta[name="csrf-token"]').content;

function startRegEdit(rowId) {
    const row = document.getElementById('reg-row-' + rowId);
    if (!row) return;
    row.querySelector('.rname-display').style.display = 'none';
    row.querySelector('.rname-edit').style.display = 'block';
    row.querySelector('.rname-input').focus();
}

function cancelRegEdit(rowId) {
    const row = document.getElementById('reg-row-' + rowId);
    if (!row) return;
    const inp = row.querySelector('.rname-input');
    inp.value = inp.dataset.original;
    row.querySelector('.rname-edit').style.display = 'none';
    row.querySelector('.rname-display').style.display = 'flex';
}

function saveRegName(rowId) {
    const row = document.getElementById('reg-row-' + rowId);
    if (!row) return;
    const inp = row.querySelector('.rname-input');
    const printName = inp.value.trim();
    const entityType = inp.dataset.entityType;
    const entityId   = inp.dataset.entityId;

    fetch(OVERRIDE_URL, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': REG_CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ entity_type: entityType, entity_id: entityId, print_name: printName }),
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) return;
        // Use server's effective name; fall back to the original registration name when override was cleared
        const effectiveName = data.print_name || inp.dataset.baseName;
        row.querySelector('.rname-text').textContent = effectiveName;
        inp.dataset.original = effectiveName;
        inp.value = effectiveName;
        cancelRegEdit(rowId);
    })
    .catch(() => cancelRegEdit(rowId));
}

// Enter = save, Escape = cancel in registry name inputs
document.addEventListener('keydown', function(e) {
    if (e.target.classList.contains('rname-input')) {
        const rowId = e.target.closest('tr')?.id?.replace('reg-row-', '');
        if (!rowId) return;
        if (e.key === 'Enter')  { e.preventDefault(); saveRegName(rowId); }
        if (e.key === 'Escape') { cancelRegEdit(rowId); }
    } else if (e.target.classList.contains('rinst-input')) {
        const rowId = e.target.closest('tr')?.id?.replace('reg-row-', '');
        if (!rowId) return;
        if (e.key === 'Enter')  { e.preventDefault(); saveRegInst(rowId); }
        if (e.key === 'Escape') { cancelInstEdit(rowId); }
    }
});


function startInstEdit(rowId) {
    const row = document.getElementById('reg-row-' + rowId);
    if (!row) return;
    row.querySelector('.rinst-display').style.display = 'none';
    row.querySelector('.rinst-edit').style.display = 'block';
    row.querySelector('.rinst-input').focus();
}

function cancelInstEdit(rowId) {
    const row = document.getElementById('reg-row-' + rowId);
    if (!row) return;
    const inp = row.querySelector('.rinst-input');
    inp.value = inp.dataset.original;
    row.querySelector('.rinst-edit').style.display = 'none';
    row.querySelector('.rinst-display').style.display = 'flex';
}

function saveRegInst(rowId) {
    const row = document.getElementById('reg-row-' + rowId);
    if (!row) return;
    const inp = row.querySelector('.rinst-input');
    const printInstitute = inp.value.trim();
    const entityType = inp.dataset.entityType;
    const entityId   = inp.dataset.entityId;

    fetch(OVERRIDE_URL, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': REG_CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ entity_type: entityType, entity_id: entityId, print_institute: printInstitute }),
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) return;
        // Use server's effective institute; fall back to original registration affiliation when cleared
        const effectiveInst = data.print_institute || inp.dataset.baseInst;
        const displayText = effectiveInst || '—';
        row.querySelector('.rinst-text').textContent = displayText.length > 28 ? displayText.slice(0, 28) + '…' : displayText;
        row.querySelector('.rinst-text').style.color = data.print_institute ? '#7c3aed' : '#64748b';
        inp.dataset.original = effectiveInst;
        inp.value = effectiveInst;
        cancelInstEdit(rowId);
    })
    .catch(() => cancelInstEdit(rowId));
}
@endif
</script>
@endpush
@endsection
