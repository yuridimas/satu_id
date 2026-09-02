<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportHistory extends Model
{
    protected $fillable = [
        'type',
        'file',
        'row_count',
        'progress',
        'status',
        'user_id',
    ];

    protected $casts = [
        'progress' => 'integer',
        'row_count' => 'integer',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
