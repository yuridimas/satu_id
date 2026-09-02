<?php

namespace App\Exports;

use App\Models\OAuthClient;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ClientsExport implements FromQuery, ShouldAutoSize, WithChunkReading, WithHeadings, WithMapping
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(protected array $filters = []) {}

    public function query(): Builder
    {
        return OAuthClient::query()
            ->when($this->filters['search'] ?? null, fn (Builder $q, $v) => $q->where('name', 'ilike', "%{$v}%"))
            ->when(($this->filters['status'] ?? 'all') !== 'all', function (Builder $q) {
                $q->where('revoked', ($this->filters['status'] ?? '') === 'revoked');
            })
            ->when(($this->filters['grant'] ?? 'all') !== 'all', function (Builder $q) {
                $q->where('grant_types', 'ilike', '%'.($this->filters['grant'] ?? '').'%');
            })
            ->select(['id', 'name', 'grant_types', 'redirect_uris', 'revoked', 'created_at']);
    }

    public function headings(): array
    {
        return [__('ID'), __('Nama'), __('Grant'), __('Redirect URIs'), __('Confidential'), __('Status'), __('Dibuat')];
    }

    /**
     * @param  OAuthClient  $client
     */
    public function map($client): array
    {
        $grants = collect($client->grant_types ?? [])->map(fn ($g) => match ($g) {
            'authorization_code' => __('Authorization Code'),
            'client_credentials' => __('Client Credentials'),
            default => $g,
        })->implode(', ');

        return [
            $client->id,
            $client->name,
            $grants,
            implode(', ', $client->redirect_uris),
            $client->confidential() ? __('Yes') : __('No'),
            $client->isActive() ? __('Active') : __('Revoked'),
            $client->created_at?->format('d M Y H:i'),
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
