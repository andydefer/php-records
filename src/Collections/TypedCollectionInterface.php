<?php

declare(strict_types=1);

namespace AndyDefer\Records\Collections;

use AndyDefer\Records\AbstractRecord;
use ArrayAccess;
use Closure;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use JsonSerializable;
use stdClass;
use Stringable;

/**
 * Type-safe collection for records and scalar values.
 *
 * @template TValue of object|string|int|float|bool
 */
interface TypedCollectionInterface extends ArrayAccess, Countable, IteratorAggregate, JsonSerializable, Stringable
{
    /**
     * Add one or multiple items.
     *
     * @param  TValue  ...$items
     * @return TypedCollection<TValue>
     */
    public function add(int|string|float|bool|null|AbstractRecord|TypedCollection|stdClass ...$items): TypedCollection;

    /**
     * Convert the collection to a plain array.
     *
     * Returns all items as a native PHP array.
     *
     * @return array<TValue>
     */
    public function toArray(): array;

    /**
     * Get all items as a new TypedCollection collection.
     *
     * @return TypedCollection<TValue>
     */
    public function all(): TypedCollection;

    /**
     * Get the allowed types as array.
     *
     * @return array<string>
     */
    public function getAllowedTypes(): array;

    /**
     * Check if empty.
     */
    public function isEmpty(): bool;

    /**
     * Check if not empty.
     */
    public function isNotEmpty(): bool;

    /**
     * Check if all items satisfy the given predicate.
     *
     * Returns true for empty collections (vacuously true).
     *
     * @param  Closure(TValue): bool  $callback
     */
    public function every(Closure $callback): bool;

    /**
     * Check if at least one item satisfies the given predicate.
     *
     * Returns false for empty collections.
     *
     * @param  Closure(TValue): bool  $callback
     */
    public function some(Closure $callback): bool;

    /**
     * Map items to a new collection.
     *
     * @template TReturn
     *
     * @param  Closure(TValue): TReturn  $callback
     * @return TypedCollection<TReturn>
     */
    public function map(Closure $callback): TypedCollection;

    /**
     * Filter items.
     *
     * @param  Closure(TValue): bool  $callback
     * @return TypedCollection<TValue>
     */
    public function filter(Closure $callback): TypedCollection;

    /**
     * Reject items.
     *
     * @param  Closure(TValue): bool  $callback
     * @return TypedCollection<TValue>
     */
    public function reject(Closure $callback): TypedCollection;

    /**
     * Execute callback on each item.
     *
     * @param  Closure(TValue): void  $callback
     * @return TypedCollection<TValue>
     */
    public function each(Closure $callback): TypedCollection;

    /**
     * Get first item.
     *
     * @return TValue|null
     */
    public function firstItem(): null|int|string|float|bool|AbstractRecord|TypedCollection|stdClass;

    /**
     * Get first n items as new collection.
     *
     * @return TypedCollection<TValue>
     */
    public function first(int $limit): TypedCollection;

    /**
     * Get last item.
     *
     * @return TValue|null
     */
    public function lastItem(): null|int|string|float|bool|AbstractRecord|TypedCollection|stdClass;

    /**
     * Get last n items as new collection.
     *
     * @return TypedCollection<TValue>
     */
    public function last(int $limit): TypedCollection;

    /**
     * Sort the collection.
     *
     * @return TypedCollection<TValue>
     */
    public function sort(int $flags = SORT_REGULAR): TypedCollection;

    /**
     * Sort by a callback or key.
     *
     * @param  Closure(TValue): mixed|string  $callback
     * @return TypedCollection<TValue>
     */
    public function sortBy(Closure|string $callback, bool $descending = false): TypedCollection;

    /**
     * Reverse the order.
     *
     * @return TypedCollection<TValue>
     */
    public function reverse(): TypedCollection;

    /**
     * Shuffle items.
     *
     * @return TypedCollection<TValue>
     */
    public function shuffle(): TypedCollection;

    /**
     * Calculate sum.
     *
     * @param  Closure(TValue): int|float|null  $callback
     */
    public function sum(?Closure $callback = null): int|float;

    /**
     * Calculate average.
     *
     * @param  Closure(TValue): int|float|null  $callback
     */
    public function avg(?Closure $callback = null): ?float;

    /**
     * Get maximum value.
     *
     * @param  Closure(TValue): int|float|string|null  $callback
     */
    public function max(?Closure $callback = null): int|float|string|null;

    /**
     * Get minimum value.
     *
     * @param  Closure(TValue): int|float|string|null  $callback
     */
    public function min(?Closure $callback = null): int|float|string|null;

    /**
     * Check if contains a value.
     */
    public function contains(int|string|float|bool|null|AbstractRecord|TypedCollection|stdClass $value): bool;

    /**
     * Get only items of a specific type.
     *
     * @template T of object|string|int|float|bool
     *
     * @param  class-string<AbstractRecord>|string  $type
     * @return TypedCollection<T>
     */
    public function ofType(string $type): TypedCollection;

    /**
     * Get items except those of a specific type.
     *
     * @return TypedCollection<TValue>
     */
    public function exceptType(string $type): TypedCollection;

    /**
     * Get distinct types present in the collection.
     *
     * @return TypedCollection<string>
     */
    public function getTypes(): TypedCollection;

    /**
     * Get items that are records.
     *
     * @return TypedCollection<AbstractRecord>
     */
    public function records(): TypedCollection;

    /**
     * Get items that are scalar values.
     *
     * @return TypedCollection<int|string|float|bool|null>
     */
    public function scalars(): TypedCollection;

    /**
     * Get items of a specific record class.
     *
     * @template TRecord of AbstractRecord
     *
     * @param  class-string<TRecord>  $recordClass
     * @return TypedCollection<TRecord>
     */
    public function ofRecord(string $recordClass): TypedCollection;

    /**
     * Get items that are instances of any record type.
     *
     * @return TypedCollection<AbstractRecord>
     */
    public function anyRecord(): TypedCollection;

    /**
     * Filter items where property equals value (for records only).
     *
     * @return TypedCollection<TValue>
     */
    public function where(string $property, int|string|float|bool|null|AbstractRecord|TypedCollection|stdClass $value): TypedCollection;

    /**
     * Filter items where property is not null (for records only).
     *
     * @return TypedCollection<TValue>
     */
    public function whereNotNull(string $property): TypedCollection;

    /**
     * Filter items where property is null (for records only).
     *
     * @return TypedCollection<TValue>
     */
    public function whereNull(string $property): TypedCollection;

    /**
     * Take first n items.
     *
     * @return TypedCollection<TValue>
     */
    public function take(int $limit): TypedCollection;

    /**
     * Skip first n items.
     *
     * @return TypedCollection<TValue>
     */
    public function skip(int $offset): TypedCollection;

    /**
     * Slice the collection.
     *
     * @return TypedCollection<TValue>
     */
    public function slice(int $offset, ?int $length = null): TypedCollection;

    /**
     * Get unique items.
     *
     * @return TypedCollection<TValue>
     */
    public function unique(?Closure $callback = null): TypedCollection;

    /**
     * Merge with another TypedCollection.
     *
     * @return TypedCollection<TValue>
     */
    public function merge(TypedCollection $collection): TypedCollection;

    /**
     * Intersect with another TypedCollection.
     *
     * @return TypedCollection<TValue>
     */
    public function intersect(TypedCollection $collection): TypedCollection;

    /**
     * Diff with another TypedCollection.
     *
     * @return TypedCollection<TValue>
     */
    public function diff(TypedCollection $collection): TypedCollection;

    /**
     * Flat map items.
     *
     * @template TReturn
     *
     * @param  Closure(TValue): TypedCollection<TReturn>  $callback
     * @return TypedCollection<TReturn>
     */
    public function flatMap(Closure $callback): TypedCollection;

    /**
     * Reset keys to sequential integers.
     *
     * @return TypedCollection<TValue>
     */
    public function values(): TypedCollection;

    /**
     * Get items that are not null.
     *
     * @return TypedCollection<TValue>
     */
    public function filterNull(): TypedCollection;

    /**
     * Get every nth item.
     *
     * @return TypedCollection<TValue>
     */
    public function nth(int $step, int $offset = 0): TypedCollection;

    /**
     * Get random items.
     *
     * @return TypedCollection<TValue>
     */
    public function random(int $number = 1): TypedCollection;

    /**
     * Check if collection contains only items of a specific type.
     */
    public function isOnlyType(string $type): bool;

    /**
     * Check if collection contains any item of a specific type.
     */
    public function containsType(string $type): bool;

    /**
     * Check if all items are of the same type.
     */
    public function isHomogeneous(): bool;

    /**
     * Check if collection contains mixed types.
     */
    public function isHeterogeneous(): bool;

    /**
     * Assert that all items are of a specific type.
     *
     * @return TypedCollection<TValue>
     *
     * @throws InvalidArgumentException
     */
    public function assertAllOfType(string $type): TypedCollection;

    /**
     * Assert that collection is not empty.
     *
     * @return TypedCollection<TValue>
     *
     * @throws InvalidArgumentException
     */
    public function assertNotEmpty(): TypedCollection;

    /**
     * Assert that collection contains at least one item of given type.
     *
     * @return TypedCollection<TValue>
     *
     * @throws InvalidArgumentException
     */
    public function assertContainsType(string $type): TypedCollection;

    /**
     * Assert that all items implement an interface.
     *
     * @param  class-string  $interface
     * @return TypedCollection<TValue>
     *
     * @throws InvalidArgumentException
     */
    public function assertAllImplement(string $interface): TypedCollection;

    /**
     * Ensure all items are scalar values.
     *
     * @return TypedCollection<TValue>
     *
     * @throws InvalidArgumentException
     */
    public function assertScalar(): TypedCollection;

    /**
     * Ensure all items are records.
     *
     * @return TypedCollection<AbstractRecord>
     *
     * @throws InvalidArgumentException
     */
    public function assertRecords(): TypedCollection;

    /**
     * Validate each item with a custom callback.
     *
     * @param  Closure(TValue, int): bool  $validator
     * @return TypedCollection<TValue>
     *
     * @throws InvalidArgumentException
     */
    public function validate(Closure $validator): TypedCollection;
}
