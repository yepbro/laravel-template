<?php

declare(strict_types=1);

namespace App\Models;

use App\Auth\Enums\LoginCredentialChangeType;
use Database\Factories\UserLoginChangeRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property LoginCredentialChangeType $type
 * @property string $new_value
 * @property string $token_hash
 * @property \Illuminate\Support\Carbon $expires_at
 */
class UserLoginChangeRequest extends Model
{
    /** @use HasFactory<UserLoginChangeRequestFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'new_value',
        'token_hash',
        'expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type'       => LoginCredentialChangeType::class,
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
