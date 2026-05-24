<?php

declare(strict_types=1);

namespace AndyDefer\Records\Tests\Fixtures\Records;

use AndyDefer\Records\AbstractRecord;
use AndyDefer\Records\Collections\TypedCollection;

/**
 * Fixture record for testing collections of collections.
 *
 * Used to test that AbstractRecord can handle properties of type
 * TypedCollection where the collection itself contains collections.
 */
final class TestCollectionsRecord extends AbstractRecord
{
    public function __construct(
        public readonly TypedCollection $stringCollections = new TypedCollection(TypedCollection::class),
        public readonly TypedCollection $intCollections = new TypedCollection(TypedCollection::class),
    ) {}
}
