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
            Flux::toast(text: __('Tidak ada data untuk diekspor.'), variant: 'danger');

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

        GenerateExportJob::dispatch($history->id, $this->type, []);

        Flux::toast(text: __('Export diproses — cek progress di bawah.'), variant: 'success');
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
            <flux:subheading size="lg">{{ __('Unduh rekap XLSX — hanya format XLSX') }}</flux:subheading>
        </div>
        <flux:button variant="ghost" :href="route('admin.' . $type . '.index')" wire:navigate icon="arrow-left">
            {{ __('Kembali') }}
        </flux:button>
    </div>

    <flux:card class="mt-6">
        <flux:heading size="sm">{{ __('Apa yang akan diekspor') }}</flux:heading>
        <flux:text class="mt-1 text-sm">
            @if ($type === 'users')
                {{ __('Akan mengekspor :count Users ke XLSX. Kolom: ID, Nama, Email, Status, Dibuat.', ['count' => $this->count]) }}
            @elseif ($type === 'clients')
                {{ __('Akan mengekspor :count OAuth Clients ke XLSX. Kolom: ID, Nama, Grant, Redirect, Confidential, Status, Dibuat.', ['count' => $this->count]) }}
            @else
                {{ __('Akan mengekspor :count Audit Logs ke XLSX. Kolom: Waktu, Event, Auditable, Actor, IP, Tags.', ['count' => $this->count]) }}
            @endif
        </flux:text>
        <div class="mt-4 flex flex-wrap items-center gap-3">
            @if ($type === 'audits')
                <flux:field class="max-w-xs">
                    <flux:label>{{ __('Password (konfirmasi untuk audit)') }}</flux:label>
                    <flux:input wire:model="password" type="password" required autocomplete="current-password" viewable placeholder="••••••••" />
                    <flux:error name="password" />
                </flux:field>
            @endif
            <flux:button variant="primary" icon="arrow-down-tray" wire:click="startExport" :disabled="$this->count === 0" data-test="start-export">
                {{ __('Mulai Export') }}
            </flux:button>
        </div>
        @if ($this->count === 0)
            <flux:text variant="subtle" class="mt-2 text-xs">{{ __('Tidak ada data untuk diekspor.') }}</flux:text>
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
                            {{ __('Menunggu antrian…') }}
                        @else
                            {{ __('Memproses :count baris…', ['count' => $latest->row_count]) }} ({{ $latest->progress }}%)
                        @endif
                    </flux:heading>
                    <flux:progress :value="$latest->progress" max="100" class="mt-2" />
                </div>
                <flux:badge :color="$latest->status === 'processing' ? 'blue' : 'zinc'" size="sm">{{ $latest->status }}</flux:badge>
            </div>
        </flux:card>
    @endif

    <flux:callout variant="info" icon="information-circle" class="mt-6">
        <flux:heading size="sm">{{ __('Info penyimpanan') }}</flux:heading>
        <flux:text class="text-sm">{{ __('Semua riwayat export akan otomatis terhapus 7 hari ke depan. File di storage/S3 ikut terhapus agar tidak membengkak. Link download tanpa batas waktu selama riwayat masih ada.') }}</flux:text>
    </flux:callout>

    <flux:card class="mt-6">
        <div class="flex items-center justify-between">
            <flux:heading size="sm">{{ __('Riwayat Export :type', ['type' => Str::headline($type)]) }}</flux:heading>
            <flux:text variant="subtle" class="text-xs">{{ __('Hanya untuk tipe ini') }}</flux:text>
        </div>
        <div class="mt-4 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column class="w-[160px] first:!ps-4">{{ __('Waktu') }}</flux:table.column>
                    <flux:table.column>{{ __('File') }}</flux:table.column>
                    <flux:table.column class="w-[80px]">{{ __('Baris') }}</flux:table.column>
                    <flux:table.column class="w-[120px]">{{ __('Status') }}</flux:table.column>
                    <flux:table.column class="w-[140px] last:!pe-4 text-right">{{ __('Aksi') }}</flux:table.column>
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
                                    <flux:button :href="route('admin.exports.history.download', $h)" icon="arrow-down-tray" variant="ghost" size="sm" target="_blank">
                                        {{ __('Download') }}
                                    </flux:button>
                                @elseif ($h->status === 'failed')
                                    <flux:badge color="rose" size="sm">{{ __('Gagal') }}</flux:badge>
                                @else
                                    <flux:text variant="subtle" class="text-xs">{{ __('Menunggu…') }}</flux:text>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5"><div class="py-10 text-center"><flux:heading>{{ __('Belum ada riwayat') }}</flux:heading><flux:text class="mt-1">{{ __('Klik Mulai Export di atas.') }}</flux:text></div></flux:table.cell>
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
