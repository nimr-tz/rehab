<x-layouts.portal title="Users & roles">
    <x-slot:header>
        <x-page-header eyebrow="Administration" title="Users & roles"
            description="Give staff their roles. A person can hold several, for example participant and reviewer." />
    </x-slot:header>

    @error('roles') <x-alert tone="danger">{{ $message }}</x-alert> @enderror

    <x-card :padding="false">
        <form method="GET" class="flex flex-col gap-3 border-b border-ink-100 p-4 sm:flex-row">
            <div class="relative flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-400" />
                <input name="q" value="{{ $filters['search'] }}" placeholder="Name or email" class="field h-11 pl-12">
            </div>
            <select name="role" class="field h-11 sm:w-60" onchange="this.form.submit()">
                <option value="">All roles</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->value }}" @selected($filters['role'] === $role->value)>{{ $role->label() }}</option>
                @endforeach
            </select>
            <x-button variant="secondary" icon="search">Search</x-button>
        </form>

        <ul class="divide-y divide-ink-100">
            @forelse ($users as $user)
                <li x-data="{ edit: false }" class="px-5 py-4 sm:px-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-brand-100 text-sm font-bold text-brand-800">{{ $user->initials() }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-ink-900">{{ $user->name }}</p>
                            <p class="text-xs text-ink-500">{{ $user->email }}</p>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            @forelse ($user->roles as $role)
                                <x-status :tone="$role->name === 'admin' ? 'danger' : ($role->name === 'participant' ? 'neutral' : 'info')">{{ \App\Enums\Role::tryFrom($role->name)?->label() ?? $role->name }}</x-status>
                            @empty
                                <span class="text-xs text-ink-400">No roles</span>
                            @endforelse
                        </div>
                        <x-button type="button" variant="ghost" size="sm" icon="pencil" x-on:click="edit = ! edit">Roles</x-button>
                    </div>

                    <form x-show="edit" x-cloak method="POST" action="{{ route('admin.users.roles', $user) }}" class="mt-4 rounded-2xl bg-canvas p-4">
                        @csrf
                        @method('PUT')
                        <div class="grid gap-2 sm:grid-cols-3">
                            @foreach ($roles as $role)
                                <label class="flex items-center gap-2 text-sm text-ink-700">
                                    <input type="checkbox" name="roles[]" value="{{ $role->value }}" @checked($user->hasRole($role->value)) class="h-4 w-4 accent-brand-700">
                                    {{ $role->label() }}
                                </label>
                            @endforeach
                        </div>
                        <x-button size="sm" class="mt-4" icon="check">Save roles</x-button>
                    </form>
                </li>
            @empty
                <li><x-empty icon="users" title="No users match" /></li>
            @endforelse
        </ul>
        <div class="border-t border-ink-100 px-5 py-3">{{ $users->links() }}</div>
    </x-card>
</x-layouts.portal>
