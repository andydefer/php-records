<?php

declare(strict_types=1);

namespace AndyDefer\Records\Tests\Unit\Collections\Utility;

use AndyDefer\Records\Collections\Utility\StringTypedCollection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class StringTypedCollectionTest extends TestCase
{
    public function test_constructor_creates_empty_collection(): void
    {
        // Arrange & Act
        $collection = new StringTypedCollection();

        // Assert
        $this->assertTrue($collection->isEmpty());
        $this->assertSame(['string'], $collection->getAllowedTypes());
    }

    public function test_add_strings(): void
    {
        // Arrange
        $collection = new StringTypedCollection();

        // Act
        $collection->add('hello')->add('world');

        // Assert
        $this->assertCount(2, $collection);
        $this->assertSame(['hello', 'world'], $collection->toArray());
    }

    public function test_add_throws_exception_for_non_string(): void
    {
        // Expect exception for non-string value
        $this->expectException(InvalidArgumentException::class);

        // Arrange & Act
        $collection = new StringTypedCollection();
        $collection->add(123);
    }

    public function test_to_lowercase(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('HELLO')->add('WoRlD')->add('PHP');

        // Act
        $result = $collection->toLowercase();

        // Assert
        $this->assertNotSame($collection, $result);
        $this->assertSame(['hello', 'world', 'php'], $result->toArray());
    }

    public function test_to_uppercase(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hello')->add('world')->add('php');

        // Act
        $result = $collection->toUppercase();

        // Assert
        $this->assertNotSame($collection, $result);
        $this->assertSame(['HELLO', 'WORLD', 'PHP'], $result->toArray());
    }

    public function test_contains_substring(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hello world')->add('good morning')->add('hello php');

        // Act
        $result = $collection->containsSubstring('hello');

        // Assert
        $this->assertSame(['hello world', 'hello php'], $result->toArray());
    }

    public function test_starts_with(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hello world')->add('world hello')->add('hello php');

        // Act
        $result = $collection->startsWith('hello');

        // Assert
        $this->assertSame(['hello world', 'hello php'], $result->toArray());
    }

    public function test_ends_with(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hello world')->add('world hello')->add('php world');

        // Act
        $result = $collection->endsWith('world');

        // Assert
        $this->assertSame(['hello world', 'php world'], $result->toArray());
    }

    public function test_filter_empty(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hello')->add('')->add('world')->add('')->add('php');

        // Act
        $result = $collection->filterEmpty();

        // Assert
        $this->assertSame(['hello', 'world', 'php'], $result->toArray());
    }

    public function test_trim_with_default_characters(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('  hello  ')->add('world')->add('  php  ');

        // Act
        $result = $collection->trim();

        // Assert
        $this->assertSame(['hello', 'world', 'php'], $result->toArray());
    }

    public function test_trim_with_custom_characters(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('__hello__')->add('world')->add('__php__');

        // Act
        $result = $collection->trim('_');

        // Assert
        $this->assertSame(['hello', 'world', 'php'], $result->toArray());
    }

    public function test_truncate(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hello world')->add('short')->add('very long string here');

        // Act
        $result = $collection->truncate(5, '...');

        // Assert
        $this->assertSame(['hello...', 'short', 'very ...'], $result->toArray());
    }

    public function test_truncate_without_suffix(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hello world')->add('short');

        // Act
        $result = $collection->truncate(5);

        // Assert
        $this->assertSame(['hello...', 'short'], $result->toArray());
    }

    public function test_truncate_preserves_short_strings(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hi')->add('hey')->add('hello');

        // Act
        $result = $collection->truncate(10);

        // Assert
        $this->assertSame(['hi', 'hey', 'hello'], $result->toArray());
    }

    public function test_matching_regex(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('user@example.com', 'invalid-email', 'admin@test.com', 'not-an-email');

        // Act
        $result = $collection->matchingRegex('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/');

        // Assert
        $this->assertSame(['user@example.com', 'admin@test.com'], $result->toArray());
    }

    public function test_matching_regex_throws_exception_for_invalid_pattern(): void
    {
        // Expect exception for invalid regex pattern
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid regular expression pattern');

        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('test');

        // Act
        $collection->matchingRegex('/invalid(pattern/');
    }

    public function test_matching_regex_returns_empty_when_no_match(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('abc', 'def', 'ghi');

        // Act
        $result = $collection->matchingRegex('/^\d+$/');

        // Assert
        $this->assertTrue($result->isEmpty());
    }

    public function test_join_with_default_separator(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hello', 'world', 'php');

        // Act
        $result = $collection->join();

        // Assert
        $this->assertSame('helloworldphp', $result);
    }

    public function test_join_with_custom_separator(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('apple', 'banana', 'cherry');

        // Act
        $result = $collection->join(', ');

        // Assert
        $this->assertSame('apple, banana, cherry', $result);
    }

    public function test_join_on_empty_collection_returns_empty_string(): void
    {
        // Arrange
        $collection = new StringTypedCollection();

        // Act
        $result = $collection->join(', ');

        // Assert
        $this->assertSame('', $result);
    }

    public function test_lengths(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hello', 'world', 'php', 'test');

        // Act
        $result = $collection->lengths();

        // Assert
        $this->assertSame(['int'], $result->getAllowedTypes());
        $this->assertSame([5, 5, 3, 4], $result->toArray());
    }

    public function test_lengths_on_empty_collection(): void
    {
        // Arrange
        $collection = new StringTypedCollection();

        // Act
        $result = $collection->lengths();

        // Assert
        $this->assertTrue($result->isEmpty());
        $this->assertSame(['int'], $result->getAllowedTypes());
    }

    public function test_pad_with_default_parameters(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hi', 'hello', 'hey');

        // Act
        $result = $collection->pad(10);

        // Assert
        $this->assertSame(['hi        ', 'hello     ', 'hey       '], $result->toArray());
    }

    public function test_pad_with_custom_pad_string(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hi', 'hello', 'hey');

        // Act
        $result = $collection->pad(10, '-=');

        // Assert
        $this->assertSame(['hi-=-=-=-=', 'hello-=-=-', 'hey-=-=-=-'], $result->toArray());
    }

    public function test_pad_left(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hi', 'hello', 'hey');

        // Act
        $result = $collection->pad(10, ' ', STR_PAD_LEFT);

        // Assert
        $this->assertSame(['        hi', '     hello', '       hey'], $result->toArray());
    }

    public function test_pad_both(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hi', 'hello', 'hey');

        // Act
        $result = $collection->pad(10, ' ', STR_PAD_BOTH);

        // Assert
        $this->assertSame(['    hi    ', '  hello   ', '   hey    '], $result->toArray());
    }

    public function test_replace_with_single_values(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hello world', 'good morning', 'hello php');

        // Act
        $result = $collection->replace('hello', 'hi');

        // Assert
        $this->assertSame(['hi world', 'good morning', 'hi php'], $result->toArray());
    }

    public function test_replace_with_arrays(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hello world', 'good morning', 'hello php');

        // Act
        $result = $collection->replace(['hello', 'world'], ['hi', 'earth']);

        // Assert
        $this->assertSame(['hi earth', 'good morning', 'hi php'], $result->toArray());
    }

    public function test_first_character(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hello', 'world', 'php');

        // Act
        $result = $collection->firstCharacter();

        // Assert
        $this->assertSame(['h', 'w', 'p'], $result->toArray());
    }

    public function test_first_character_on_empty_string(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hello', '', 'world');

        // Act
        $result = $collection->firstCharacter();

        // Assert: Empty string returns empty string
        $this->assertSame(['h', '', 'w'], $result->toArray());
    }

    public function test_last_character(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hello', 'world', 'php');

        // Act
        $result = $collection->lastCharacter();

        // Assert
        $this->assertSame(['o', 'd', 'p'], $result->toArray());
    }

    public function test_last_character_on_empty_string(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hello', '', 'world');

        // Act
        $result = $collection->lastCharacter();

        // Assert: Empty string returns empty string
        $this->assertSame(['o', '', 'd'], $result->toArray());
    }

    public function test_substring_without_length(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hello world', 'good morning', 'php programming');

        // Act
        $result = $collection->substring(6);

        // Assert: substr('good morning', 6) returns 'orning' because:
        // g=0, o=1, o=2, d=3, space=4, m=5, o=6, r=7, n=8, i=9, n=10, g=11
        $this->assertSame(['world', 'orning', 'ogramming'], $result->toArray());
    }

    public function test_substring_with_length(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hello world', 'good morning', 'php programming');

        // Act
        $result = $collection->substring(0, 5);

        // Assert
        $this->assertSame(['hello', 'good ', 'php p'], $result->toArray());
    }

    public function test_substring_with_negative_offset(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hello', 'world', 'php');

        // Act
        $result = $collection->substring(-2);

        // Assert
        $this->assertSame(['lo', 'ld', 'hp'], $result->toArray());
    }

    public function test_count_matching_regex(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('abc123', 'def456', 'ghi789', 'no_numbers');

        // Act
        $result = $collection->countMatchingRegex('/\d+/');

        // Assert
        $this->assertSame(3, $result);
    }

    public function test_count_matching_regex_on_empty_collection(): void
    {
        // Arrange
        $collection = new StringTypedCollection();

        // Act
        $result = $collection->countMatchingRegex('/\d+/');

        // Assert
        $this->assertSame(0, $result);
    }

    public function test_has_matching_regex_returns_true_when_match_exists(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('abc', '123', 'def');

        // Act
        $result = $collection->hasMatchingRegex('/\d+/');

        // Assert
        $this->assertTrue($result);
    }

    public function test_has_matching_regex_returns_false_when_no_match(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('abc', 'def', 'ghi');

        // Act
        $result = $collection->hasMatchingRegex('/\d+/');

        // Assert
        $this->assertFalse($result);
    }

    public function test_unique_case_insensitive(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('Hello', 'WORLD', 'hello', 'World', 'PHP', 'php');

        // Act
        $result = $collection->uniqueCaseInsensitive();

        // Assert: Preserves first occurrence's case
        $expected = ['Hello', 'WORLD', 'PHP'];
        $this->assertEquals($expected, $result->toArray());
    }

    public function test_unique_case_insensitive_on_empty_collection(): void
    {
        // Arrange
        $collection = new StringTypedCollection();

        // Act
        $result = $collection->uniqueCaseInsensitive();

        // Assert
        $this->assertTrue($result->isEmpty());
    }

    public function test_sort_case_insensitive_ascending(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('banana', 'Apple', 'cherry', 'apple', 'Banana');

        // Act
        $result = $collection->sortCaseInsensitive();

        // Assert: Verify case-insensitive sorting with deterministic order
        $resultArray = $result->toArray();

        // Check that all expected values are present
        $this->assertCount(5, $resultArray);

        // Case-insensitive comparison should show the order is correct
        $lowercaseResult = array_map('strtolower', $resultArray);
        $expected = ['apple', 'apple', 'banana', 'banana', 'cherry'];

        $this->assertSame($expected, $lowercaseResult);
    }

    public function test_sort_case_insensitive_descending(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('banana', 'Apple', 'cherry', 'apple', 'Banana');

        // Act
        $result = $collection->sortCaseInsensitive(true);

        // Assert: Verify case-insensitive sorting in descending order
        $resultArray = $result->toArray();

        // Check that all expected values are present
        $this->assertCount(5, $resultArray);

        // Case-insensitive comparison should show the order is correct (descending)
        $lowercaseResult = array_map('strtolower', $resultArray);
        $expected = ['cherry', 'banana', 'banana', 'apple', 'apple'];

        $this->assertSame($expected, $lowercaseResult);
    }

    public function test_remove_whitespace(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hello world', 'good  morning', 'php   programming', 'no spaces');

        // Act
        $result = $collection->removeWhitespace();

        // Assert
        $this->assertSame(['helloworld', 'goodmorning', 'phpprogramming', 'nospaces'], $result->toArray());
    }

    public function test_remove_whitespace_with_tabs_and_newlines(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add("hello\tworld", "good\nmorning", "php\r\nprogramming");

        // Act
        $result = $collection->removeWhitespace();

        // Assert
        $this->assertSame(['helloworld', 'goodmorning', 'phpprogramming'], $result->toArray());
    }

    public function test_slugify(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('Hello World!', 'My Awesome Article', 'PHP 8.0 is great!', 'Special @#$ Characters');

        // Act
        $result = $collection->slugify();

        // Assert
        $this->assertSame(
            ['hello-world', 'my-awesome-article', 'php-8-0-is-great', 'special-characters'],
            $result->toArray()
        );
    }

    public function test_slugify_on_already_slugified_string(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('already-slugified', 'another-slug');

        // Act
        $result = $collection->slugify();

        // Assert
        $this->assertSame(['already-slugified', 'another-slug'], $result->toArray());
    }

    public function test_wrap_with_prefix_and_suffix(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hello', 'world', 'php');

        // Act
        $result = $collection->wrap('[', ']');

        // Assert
        $this->assertSame(['[hello]', '[world]', '[php]'], $result->toArray());
    }

    public function test_wrap_with_same_prefix_and_suffix(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hello', 'world', 'php');

        // Act
        $result = $collection->wrap('**');

        // Assert
        $this->assertSame(['**hello**', '**world**', '**php**'], $result->toArray());
    }

    public function test_remove_prefix(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('prefix_hello', 'prefix_world', 'no_prefix', 'prefix_php_prefix');

        // Act
        $result = $collection->removePrefix('prefix_');

        // Assert
        $this->assertSame(['hello', 'world', 'no_prefix', 'php_prefix'], $result->toArray());
    }

    public function test_remove_prefix_when_prefix_not_present(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hello', 'world', 'php');

        // Act
        $result = $collection->removePrefix('prefix_');

        // Assert
        $this->assertSame(['hello', 'world', 'php'], $result->toArray());
    }

    public function test_remove_suffix(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hello_suffix', 'world_suffix', 'no_suffix_here', 'suffix_php_suffix');

        // Act
        $result = $collection->removeSuffix('_suffix');

        // Assert: 'no_suffix_here' doesn't end with '_suffix' so it remains unchanged
        $this->assertSame(['hello', 'world', 'no_suffix_here', 'suffix_php'], $result->toArray());
    }

    public function test_remove_suffix_when_suffix_not_present(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hello', 'world', 'php');

        // Act
        $result = $collection->removeSuffix('_suffix');

        // Assert
        $this->assertSame(['hello', 'world', 'php'], $result->toArray());
    }

    public function test_chained_operations(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('  HELLO WORLD  ')->add('  GOOD MORNING  ')->add('short');

        // Act
        $result = $collection
            ->trim()
            ->toLowercase()
            ->containsSubstring('hello');

        // Assert
        $this->assertSame(['hello world'], $result->toArray());
    }

    public function test_complex_chain_operation(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add(
            '  Hello World!  ',
            '  GOOD MORNING  ',
            '  PHP Programming  ',
            '  short  '
        );

        // Act
        $result = $collection
            ->trim()
            ->toLowercase()
            ->slugify()
            ->removePrefix('hello-')
            ->wrap('**');

        // Assert
        $this->assertSame(
            ['**world**', '**good-morning**', '**php-programming**', '**short**'],
            $result->toArray()
        );
    }

    public function test_original_collection_is_not_modified(): void
    {
        // Arrange
        $original = new StringTypedCollection();
        $original->add('Hello')->add('World');

        // Act
        $original->toLowercase();
        $original->toUppercase();
        $original->trim();
        $original->slugify();

        // Assert
        $this->assertSame(['Hello', 'World'], $original->toArray());
    }

    public function test_empty_collection_operations_return_empty(): void
    {
        // Arrange
        $collection = new StringTypedCollection();

        // Act & Assert
        $this->assertTrue($collection->toLowercase()->isEmpty());
        $this->assertTrue($collection->toUppercase()->isEmpty());
        $this->assertTrue($collection->containsSubstring('test')->isEmpty());
        $this->assertTrue($collection->startsWith('test')->isEmpty());
        $this->assertTrue($collection->endsWith('test')->isEmpty());
        $this->assertTrue($collection->filterEmpty()->isEmpty());
        $this->assertTrue($collection->trim()->isEmpty());
        $this->assertTrue($collection->truncate(5)->isEmpty());
        $this->assertTrue($collection->matchingRegex('/test/')->isEmpty());
        $this->assertTrue($collection->lengths()->isEmpty());
        $this->assertTrue($collection->uniqueCaseInsensitive()->isEmpty());
        $this->assertTrue($collection->removeWhitespace()->isEmpty());
        $this->assertTrue($collection->slugify()->isEmpty());
    }

    public function test_json_serialize(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hello')->add('world');

        // Act
        $json = json_encode($collection);

        // Assert
        $this->assertSame('["hello","world"]', $json);
    }

    public function test_to_array_returns_items(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hello')->add('world');

        // Act & Assert
        $this->assertSame(['hello', 'world'], $collection->toArray());
    }

    public function test_immutability_of_transform_methods(): void
    {
        // Arrange
        $collection = new StringTypedCollection();
        $collection->add('hello', 'world', 'php');

        // Act
        $lowercase = $collection->toLowercase();
        $uppercase = $collection->toUppercase();
        $trimmed = $collection->trim();
        $slugified = $collection->slugify();

        // Assert: Original unchanged
        $this->assertSame(['hello', 'world', 'php'], $collection->toArray());

        // Assert: Each transform created a new collection
        $this->assertNotSame($collection, $lowercase);
        $this->assertNotSame($collection, $uppercase);
        $this->assertNotSame($collection, $trimmed);
        $this->assertNotSame($collection, $slugified);

        // Assert: Each transform has correct values
        $this->assertSame(['hello', 'world', 'php'], $lowercase->toArray());
        $this->assertSame(['HELLO', 'WORLD', 'PHP'], $uppercase->toArray());
        $this->assertSame(['hello', 'world', 'php'], $trimmed->toArray());
        $this->assertSame(['hello', 'world', 'php'], $slugified->toArray());
    }
}
