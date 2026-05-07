<?php

declare(strict_types=1);

namespace App\Events\Auth;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched immediately after an account row is soft-deleted.
 *
 * Payload keeps the hydrated user instance (including identifiers) while
 * `deleted_at` is already stamped for listeners that enqueue side effects.
 */
class AccountDeleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public User $user) {}
}
