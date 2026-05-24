<?php

declare(strict_types=1);

namespace AndyDefer\Records\Tests\Fixtures\Records;

use AndyDefer\Records\AbstractRecord;
use AndyDefer\Records\Collections\TypedCollection;
use stdClass;

/**
 * Fixture record for testing validation rules.
 *
 * This record is specifically designed to test the validation rules of AbstractRecord:
 * - TypedCollection cannot be null
 * - TypedCollection cannot have union types
 * - Arrays are not allowed
 *
 * @author Andy Defer
 */
final class TestValidationRecord extends AbstractRecord
{
    public function __construct(
        public readonly string $validString = 'default',
        public readonly int $validInt = 0,
        public readonly TypedCollection $validCollection = new TypedCollection('string'),
        public readonly ?TypedCollection $invalidNullableCollection = new TypedCollection('string'),
        public readonly TypedCollection|stdClass $invalidUnionCollection = new TypedCollection('string'),
        public readonly array $invalidArray = [],
    ) {
        parent::__construct();
    }
}
