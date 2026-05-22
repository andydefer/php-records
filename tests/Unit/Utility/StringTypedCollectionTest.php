<?php

declare(strict_types=1);

namespace AndyDefer\Records\Tests\Unit\Collections\Utility;

use AndyDefer\Records\Collections\Utility\StringTypedCollection;
use PHPUnit\Framework\TestCase;

final class StringTypedCollectionTest extends TestCase
{
    public function test_constructor_creates_empty_collection(): void
    {
        $collection = new StringTypedCollection;

        $this->assertTrue($collection->isEmpty());
        $this->assertSame(['string'], $collection->getAllowedTypes());
    }

    public function test_add_strings(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello')->add('world');

        $this->assertCount(2, $collection);
        $this->assertSame(['hello', 'world'], $collection->toArray());
    }

    public function test_add_throws_exception_for_non_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $collection = new StringTypedCollection;
        $collection->add(123); // int not allowed
    }

    public function test_to_lowercase(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('HELLO')->add('WoRlD')->add('PHP');

        $result = $collection->toLowercase();

        $this->assertNotSame($collection, $result);
        $this->assertSame(['hello', 'world', 'php'], $result->toArray());
    }

    public function test_to_uppercase(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello')->add('world')->add('php');

        $result = $collection->toUppercase();

        $this->assertNotSame($collection, $result);
        $this->assertSame(['HELLO', 'WORLD', 'PHP'], $result->toArray());
    }

    public function test_contains_substring(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello world')->add('good morning')->add('hello php');

        $result = $collection->containsSubstring('hello');

        $this->assertSame(['hello world', 'hello php'], $result->toArray());
    }

    public function test_starts_with(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello world')->add('world hello')->add('hello php');

        $result = $collection->startsWith('hello');

        $this->assertSame(['hello world', 'hello php'], $result->toArray());
    }

    public function test_ends_with(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello world')->add('world hello')->add('php world');

        $result = $collection->endsWith('world');

        $this->assertSame(['hello world', 'php world'], $result->toArray());
    }

    public function test_filter_empty(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello')->add('')->add('world')->add('')->add('php');

        $result = $collection->filterEmpty();

        $this->assertSame(['hello', 'world', 'php'], $result->toArray());
    }

    public function test_trim(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('  hello  ')->add('world')->add('  php  ');

        $result = $collection->trim();

        $this->assertSame(['hello', 'world', 'php'], $result->toArray());
    }

    public function test_truncate(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello world')->add('short')->add('very long string here');

        $result = $collection->truncate(5, '...');

        $this->assertSame(['hello...', 'short', 'very ...'], $result->toArray());
    }

    public function test_truncate_without_suffix(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello world')->add('short');

        $result = $collection->truncate(5);

        $this->assertSame(['hello...', 'short'], $result->toArray());
    }

    public function test_chained_operations(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('  HELLO WORLD  ')->add('  GOOD MORNING  ')->add('short');

        $result = $collection
            ->trim()
            ->toLowercase()
            ->containsSubstring('hello');

        $this->assertSame(['hello world'], $result->toArray());
    }

    public function test_original_collection_is_not_modified(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('Hello')->add('World');

        $collection->toLowercase();

        $this->assertSame(['Hello', 'World'], $collection->toArray());
    }

    public function test_empty_collection_operations_return_empty(): void
    {
        $collection = new StringTypedCollection;

        $this->assertTrue($collection->toLowercase()->isEmpty());
        $this->assertTrue($collection->toUppercase()->isEmpty());
        $this->assertTrue($collection->containsSubstring('test')->isEmpty());
        $this->assertTrue($collection->startsWith('test')->isEmpty());
        $this->assertTrue($collection->endsWith('test')->isEmpty());
        $this->assertTrue($collection->filterEmpty()->isEmpty());
        $this->assertTrue($collection->trim()->isEmpty());
        $this->assertTrue($collection->truncate(5)->isEmpty());
    }

    public function test_json_serialize(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello')->add('world');

        $json = json_encode($collection);

        $this->assertSame('["hello","world"]', $json);
    }

    public function test_to_array_returns_items(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello')->add('world');

        $this->assertSame(['hello', 'world'], $collection->toArray());
    }
}
