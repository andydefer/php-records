<?php

declare(strict_types=1);

namespace AndyDefer\Records\Tests\Fixtures\Records;

use AndyDefer\Records\AbstractRecord;
use AndyDefer\Records\Tests\Fixtures\Enums\TestBackedStringEnum;

final class TestUserUpdateRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?TestBackedStringEnum $status = null,
    ) {}
}
