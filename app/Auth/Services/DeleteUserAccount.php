<?php

declare(strict_types=1);

namespace App\Auth\Services;

use App\Events\Auth\AccountDeleted;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteUserAccount
{
    public function handle(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->delete();

            AccountDeleted::dispatch($user);
        });
    }
}
