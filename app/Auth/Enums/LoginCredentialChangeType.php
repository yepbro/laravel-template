<?php

declare(strict_types=1);

namespace App\Auth\Enums;

enum LoginCredentialChangeType: string
{
    case Email = 'email';
    case Phone = 'phone';
}
