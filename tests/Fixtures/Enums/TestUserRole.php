<?php

declare(strict_types=1);

namespace AndyDefer\Records\Tests\Fixtures\Enums;

use AndyDefer\Records\Traits\Enumable;

enum TestUserRole: string
{
    use Enumable;

    case ADMIN = 'admin';
    case USER = 'user';
    case GUEST = 'guest';
}
