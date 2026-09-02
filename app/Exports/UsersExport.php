<?php

namespace App\Exports;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromQuery, ShouldAutoSize, WithChunkReading, WithHeadings, WithMapping
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(protected array $filters = []) {}

    public function query(): Builder
    {
        return User::query()
            ->where('role', UserRole::User)
            ->when($this->filters['search'] ?? null, function (Builder $q, string $v) {
                $q->where(function (Builder $qq) use ($v) {
                    $qq->where('name', 'ilike', "%{$v}%")
                        ->orWhere('email', 'ilike', "%{$v}%");
                });
            })
            ->when(($this->filters['status'] ?? 'all') !== 'all', function (Builder $q) {
                $q->where('active', ($this->filters['status'] ?? '') === 'active');
            })
            ->when($this->filters['date_from'] ?? null, fn (Builder $q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($this->filters['date_to'] ?? null, fn (Builder $q, $v) => $q->whereDate('created_at', '<=', $v))
            ->select(['id', 'name', 'email', 'active', 'role', 'created_at', 'deleted_at']);
    }

    public function headings(): array
    {
        return [__('ID'), __('Nama'), __('Email'), __('Status'), __('Dibuat')];
    }

    /**
     * @param  User  $user
     */
    public function map($user): array
    {
        return [
            $user->id,
            $user->name,
            $user->email,
            $user->active ? __('Active') : __('Inactive'),
            $user->created_at?->format('d M Y H:i'),
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
