<?php

use App\Jobs\GenerateExportJob;
use App\Models\ExportHistory;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Export')] class extends Component {
    use WithPagination;

    #[Locked]
    public string $type;

    public string $search = '';
    public string $status = 'all';

    public function mount(string $type): void
    {
        abort_unless(in_array($type, ['users', 'clients', 'audits'], true), 404);
        abort_unless(auth()->user()?->isSuperuser(), 403);

        $this->type = $type;
    }

    #[Computed]
    public function count(): int
    {
        return match ($this->type) {
            'users' => \App\Models\User::where('role', \App\Enums\UserRole::User)->count(),
            'clients' => \App\Models\OAuthClient::count(),
            'audits' => \App\Models\Audit::count(),
            default => 0,
        };
    }

    #[Computed]
    public function histories()
    {
        return ExportHistory::where('type', $this->type)
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(10);
    }

    public function startExport(): void
    {
        // For audits, require recent password confirmation
        if ($this->type === 'audits') {
            $this->validate([
                'password' => ['required', 'current_password'],
            ]);
        }

        if ($this->count === 0) {
            Flux::toast(text: __('No data available to export.'), variant: 'danger');

            return;
        }

        $file = $this->type . '-' . now()->format('Y-m-d_His') . '-' . Str::random(6) . '.xlsx';

        $history = ExportHistory::create([
            'type' => $this->type,
            'file' => $file,
            'row_count' => $this->count,
            'progress' => 0,
            'status' => 'queued',
            'user_id' => auth()->id(),
        ]);

        GenerateExportJob::dispatch($history->id, $this->type, [], app()->getLocale());

        Flux::toast(text: __('Export is processing — check the progress below.'), variant: 'success');
    }

    public string $password = '';
}; ?>

<section class="w-full" wire:poll.2s>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('admin.' . $type . '.index')" wire:navigate>{{ $type === 'users' ? __('Users') : ($type === 'clients' ? __('OAuth2 Clients') : __('Audit Logs')) }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __('Export') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Export :type', ['type' => Str::headline($type)]) }}</flux:heading>
            <flux:subheading size="lg">{{ __('Download the XLSX report — XLSX format only.') }}</flux:subheading>
        </div>
        <flux:button variant="ghost" :href="route('admin.' . $type . '.index')" wire:navigate icon="arrow-left">
            {{ __('Back') }}
        </flux:button>
    </div>

    <flux:card class="mt-6">
        <flux:heading size="sm">{{ __('What will be exported') }}</flux:heading>
        <flux:text class="mt-1 text-sm">
            @if ($type === 'users')
                {{ __('Exporting :count users to XLSX. Columns: ID, Name, Email, Status, Created at.', ['count' => $this->count]) }}
            @elseif ($type === 'clients')
                {{ __('Exporting :count OAuth clients to XLSX. Columns: ID, Name, Grant, Redirect URIs, Confidential, Status, Created at.', ['count' => $this->count]) }}
            @else
                {{ __('Exporting :count audit logs to XLSX. Columns: Time, Event, Auditable, Actor, IP, Tags.', ['count' => $this->count]) }}
            @endif
        </flux:text>
        <div class="mt-4 flex flex-wrap items-center gap-3">
            @if ($type === 'audits')
                <flux:field class="max-w-xs">
                    <flux:label>{{ __('Password (confirmation required for audit export)') }}</flux:label>
                    <flux:input wire:model="password" type="password" required autocomplete="current-password" viewable placeholder="••••••••" />
                    <flux:error name="password" />
                </flux:field>
            @endif
            <flux:button variant="primary" icon="arrow-down-tray" wire:click="startExport" :disabled="$this->count === 0" data-test="start-export">
                {{ __('Start export') }}
            </flux:button>
        </div>
        @if ($this->count === 0)
            <flux:text variant="subtle" class="mt-2 text-xs">{{ __('No data available to export.') }}</flux:text>
        @endif
    </flux:card>

    @php $latest = $this->histories->first(); @endphp
    @if ($latest && in_array($latest->status, ['queued', 'processing']))
        <flux:card class="mt-6">
            <div class="flex items-center gap-3">
                <flux:icon.loading class="size-5 animate-spin text-zinc-500" />
                <div class="flex-1">
                    <flux:heading size="sm">
                        @if ($latest->status === 'queued')
                            {{ __('Waiting in queue…') }}
                        @else
                            {{ __('Processing :count rows…', ['count' => $latest->row_count]) }} ({{ $latest->progress }}%)
                        @endif
                    </flux:heading>
                    <flux:progress :value="$latest->progress" max="100" class="mt-2" />
                </div>
                <flux:badge :color="$latest->status === 'processing' ? 'blue' : 'zinc'" size="sm">{{ $latest->status }}</flux:badge>
            </div>
        </flux:card>
    @endif

    <div class="mt-6 rounded-lg border border-zinc-200 border-l-4 border-l-indigo-600 bg-white p-4 shadow-xs dark:border-zinc-800 dark:border-l-indigo-500 dark:bg-zinc-900">
        <div class="flex items-start gap-3">
            <flux:icon.information-circle class="size-5 text-indigo-600 dark:text-indigo-400 shrink-0 mt-0.5" />
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Storage information') }}</h4>
                    <span class="rounded bg-indigo-50 px-1.5 py-0.5 text-[10px] font-semibold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
                        {{ __('7-DAY RETENTION') }}
                    </span>
                </div>
                <p class="text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">
                    {{ __('All export history is deleted automatically after 7 days. Files in storage/S3 are also removed to prevent storage growth. Download links remain active while the export history exists.') }}
                </p>
            </div>
        </div>
    </div>

    <flux:card class="mt-6">
        <div class="flex items-center justify-between">
            <flux:heading size="sm">{{ __(':type export history', ['type' => Str::headline($type)]) }}</flux:heading>
            <flux:text variant="subtle" class="text-xs">{{ __('This type only') }}</flux:text>
        </div>
        <div class="mt-4 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column class="w-[160px] first:!ps-4">{{ __('Time') }}</flux:table.column>
                    <flux:table.column>{{ __('File') }}</flux:table.column>
                    <flux:table.column class="w-[80px]">{{ __('Rows') }}</flux:table.column>
                    <flux:table.column class="w-[120px]">{{ __('Status') }}</flux:table.column>
                    <flux:table.column class="w-[140px] last:!pe-4 text-right">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->histories as $h)
                        <flux:table.row :key="$h->id">
                            <flux:table.cell class="first:!ps-4 whitespace-nowrap text-xs" :title="$h->created_at">
                                {{ $h->created_at?->diffForHumans() }}
                                <div class="text-[11px] text-zinc-500">{{ $h->created_at?->format('d M Y H:i') }}</div>
                            </flux:table.cell>
                            <flux:table.cell class="max-w-0 truncate font-mono text-xs" :title="$h->file">{{ $h->file }}</flux:table.cell>
                            <flux:table.cell class="text-xs">{{ $h->row_count }}</flux:table.cell>
                            <flux:table.cell>
                                @if ($h->status === 'processing')
                                    <div class="space-y-1">
                                        <flux:badge color="blue" size="sm">{{ $h->progress }}%</flux:badge>
                                        <flux:progress :value="$h->progress" max="100" class="h-1" />
                                    </div>
                                @else
                                    <flux:badge :color="match($h->status){'completed'=>'green','failed'=>'rose','queued'=>'zinc',default=>'zinc'}" size="sm">{{ $h->status }}</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="last:!pe-4 text-right">
                                @if ($h->status === 'completed')
                                    <flux:button :href="route('admin.exports.history.download', $h)" icon="arrow-down-tray" variant="ghost" size="sm" target="_blank" class="text-violet-600! hover:text-violet-700! hover:bg-violet-50! dark:text-violet-400! dark:hover:text-violet-300! dark:hover:bg-violet-950/50!">
                                        {{ __('Download') }}
                                    </flux:button>
                                @elseif ($h->status === 'failed')
                                    <flux:badge color="rose" size="sm">{{ __('Failed') }}</flux:badge>
                                @else
                                    <flux:text variant="subtle" class="text-xs">{{ __('Waiting…') }}</flux:text>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5"><div class="py-10 text-center"><flux:heading>{{ __('No history yet') }}</flux:heading><flux:text class="mt-1">{{ __('Click Start export above.') }}</flux:text></div></flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
        <div class="mt-4">
            {{ $this->histories->links() }}
        </div>
    </flux:card>
</section>
