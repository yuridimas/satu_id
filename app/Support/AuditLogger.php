<?php

namespace App\Support;

use App\Models\Audit;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Record a custom (non-model driven) audit entry.
     *
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public static function record(
        Model $auditable,
        string $event,
        ?User $actor = null,
        array $oldValues = [],
        array $newValues = [],
        ?string $tags = null
    ): Audit {
        $actor ??= auth('web')->user();

        $audit = new Audit;

        $audit->auditable_type = $auditable->getMorphClass();
        $audit->auditable_id = $auditable->getKey();
        $audit->event = $event;
        $audit->old_values = $oldValues;
        $audit->new_values = $newValues;
        $audit->tags = $tags;

        if (! is_null($actor)) {
            $audit->user_type = $actor->getMorphClass();
            $audit->user_id = $actor->getAuthIdentifier();
        }

        $audit->ip_address = Request::ip();
        $audit->user_agent = substr((string) Request::userAgent(), 0, 1023);
        $audit->url = Request::fullUrl();

        $audit->save();

        return $audit;
    }
}
