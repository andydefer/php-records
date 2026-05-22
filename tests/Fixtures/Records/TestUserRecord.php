<?php

declare(strict_types=1);

namespace AndyDefer\Records\Tests\Fixtures\Records;

use AndyDefer\Records\AbstractRecord;
use AndyDefer\Records\Collections\TypedCollection;
use AndyDefer\Records\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\Records\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\Records\Tests\Fixtures\Enums\TestUserStatus;

/**
 * Test record for unit tests.
 *
 * PURE RECORD - No logic, just data structure.
 * Used for create/update operations in TestUserRepository.
 */
final class TestUserRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?TestUserStatus $status = TestUserStatus::ACTIVE,
        public readonly ?TestUserRole $role = TestUserRole::USER,
        public readonly ?TestUserGrade $grade = TestUserGrade::BRONZE,
        public readonly ?string $emailVerifiedAt = null,
        public readonly TypedCollection $tags = new TypedCollection('string'),
        public readonly TypedCollection $products = new TypedCollection(TestProductRecord::class),
        public readonly ?TestProductRecord $featuredProduct = null,
    ) {}
}
