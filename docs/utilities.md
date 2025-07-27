# Utilities Documentation

FastAPI provides powerful utility classes for common string and array operations, making development faster and more efficient.

## Table of Contents

- [StringMethods](#stringmethods)
- [ArrayMethods](#arraymethods)
- [Usage Examples](#usage-examples)
- [Best Practices](#best-practices)

## StringMethods

The StringMethods class provides comprehensive string manipulation and analysis capabilities.

### Basic Usage

```php
use FASTAPI\StringMethods;

// Pattern matching
$matches = StringMethods::match('Hello World', 'l+');
$parts = StringMethods::split('a,b,c', ',');

// String manipulation
$sanitized = StringMethods::sanitize('hello@#world', ['@', '#']);
$unique = StringMethods::unique('hello'); // 'helo'
```

### Pattern Matching

```php
use FASTAPI\StringMethods;

// Find matches
$matches = StringMethods::match('Hello World', 'l+');
// Returns: ['ll', 'l']

// Find all occurrences
$allMatches = StringMethods::matchAll('hello world', 'o');
// Returns: ['o', 'o']

// Check if string matches pattern
$isMatch = StringMethods::isMatch('hello123', '\d+');
// Returns: true
```

### String Splitting and Joining

```php
use FASTAPI\StringMethods;

// Split string
$parts = StringMethods::split('a,b,c', ',');
// Returns: ['a', 'b', 'c']

// Split with limit
$parts = StringMethods::split('a,b,c,d', ',', 2);
// Returns: ['a', 'b,c,d']

// Join array
$joined = StringMethods::join(['a', 'b', 'c'], '-');
// Returns: 'a-b-c'
```

### String Sanitization

```php
use FASTAPI\StringMethods;

// Remove specific characters
$clean = StringMethods::sanitize('hello@#world', ['@', '#']);
// Returns: 'helloworld'

// Remove special characters
$clean = StringMethods::sanitize('hello@#$%world', ['@', '#', '$', '%']);
// Returns: 'helloworld'

// Keep only alphanumeric
$clean = StringMethods::sanitize('hello123!@#', ['!', '@', '#']);
// Returns: 'hello123'
```

### String Uniqueness

```php
use FASTAPI\StringMethods;

// Remove duplicate characters
$unique = StringMethods::unique('hello');
// Returns: 'helo'

$unique = StringMethods::unique('mississippi');
// Returns: 'misp'
```

### Case Conversion

```php
use FASTAPI\StringMethods;

// Convert to camel case
$camelCase = StringMethods::toCamelCase('hello-world');
// Returns: 'helloWorld'

$camelCase = StringMethods::toCamelCase('hello_world');
// Returns: 'helloWorld'

// Convert to snake case
$snakeCase = StringMethods::toSnakeCase('helloWorld');
// Returns: 'hello_world'

// Convert to kebab case
$kebabCase = StringMethods::toKebabCase('helloWorld');
// Returns: 'hello-world'
```

### String Replacement

```php
use FASTAPI\StringMethods;

// Replace string
$replaced = StringMethods::replaceString('hello-world', '-', '_');
// Returns: 'hello_world'

// Replace multiple occurrences
$replaced = StringMethods::replaceString('hello-world-test', '-', '_');
// Returns: 'hello_world_test'

// Replace with array
$replaced = StringMethods::replaceString('hello world', ['hello', 'world'], ['hi', 'earth']);
// Returns: 'hi earth'
```

### String Analysis

```php
use FASTAPI\StringMethods;

// Find index of substring
$index = StringMethods::indexOf('hello world', 'world');
// Returns: 6

// Check if string contains substring
$contains = StringMethods::contains('hello world', 'world');
// Returns: true

// Check if string starts with
$startsWith = StringMethods::startsWith('hello world', 'hello');
// Returns: true

// Check if string ends with
$endsWith = StringMethods::endsWith('hello world', 'world');
// Returns: true
```

### Pluralization

```php
use FASTAPI\StringMethods;

// Make plural
$plural = StringMethods::plural('cat');
// Returns: 'cats'

$plural = StringMethods::plural('city');
// Returns: 'cities'

// Make singular
$singular = StringMethods::singular('cats');
// Returns: 'cat'

$singular = StringMethods::singular('cities');
// Returns: 'city'
```

## ArrayMethods

The ArrayMethods class provides comprehensive array manipulation and analysis capabilities.

### Basic Usage

```php
use FASTAPI\ArrayMethods;

$array = ['', 'hello', null, 'world', false, 'test'];

// Array cleaning
$clean = ArrayMethods::clean($array);
$trimmed = ArrayMethods::trim([' hello ', ' world ']);
```

### Array Cleaning

```php
use FASTAPI\ArrayMethods;

$array = ['', 'hello', null, 'world', false, 'test'];

// Remove empty values
$clean = ArrayMethods::clean($array);
// Returns: ['hello', 'world', 'test']

// Remove specific values
$clean = ArrayMethods::clean($array, ['', null, false]);
// Returns: ['hello', 'world', 'test']

// Remove with callback
$clean = ArrayMethods::clean($array, function($value) {
    return empty($value) || $value === false;
});
// Returns: ['hello', 'world', 'test']
```

### Array Trimming

```php
use FASTAPI\ArrayMethods;

// Trim whitespace
$trimmed = ArrayMethods::trim([' hello ', ' world ']);
// Returns: ['hello', 'world']

// Trim specific characters
$trimmed = ArrayMethods::trim(['...hello...', '...world...'], '.');
// Returns: ['hello', 'world']

// Trim with callback
$trimmed = ArrayMethods::trim(['  hello  ', '  world  '], function($value) {
    return trim($value);
});
// Returns: ['hello', 'world']
```

### Array Structure Manipulation

```php
use FASTAPI\ArrayMethods;

// Convert to object
$object = ArrayMethods::toObject(['name' => 'John', 'age' => 30]);
// Returns: stdClass object

// Flatten nested array
$flat = ArrayMethods::flatten([1, [2, [3, 4]]]);
// Returns: [1, 2, 3, 4]

// Flatten with depth limit
$flat = ArrayMethods::flatten([1, [2, [3, 4]]], 1);
// Returns: [1, 2, [3, 4]]
```

### Array Element Access

```php
use FASTAPI\ArrayMethods;

$array = [1, 2, 3, 4, 5];

// Get first element
$first = ArrayMethods::first($array);
// Returns: 1

// Get last element
$last = ArrayMethods::last($array);
// Returns: 5

// Get element with default
$value = ArrayMethods::get($array, 'key', 'default');
// Returns: 'default'

// Get element by index
$value = ArrayMethods::get($array, 2);
// Returns: 3
```

### Array Inspection

```php
use FASTAPI\ArrayMethods;

$array = ['a' => 1, 'b' => 2, 'c' => 3];

// Check if key exists
$hasKey = ArrayMethods::has($array, 'a');
// Returns: true

// Get all keys
$keys = ArrayMethods::keys($array);
// Returns: ['a', 'b', 'c']

// Get all values
$values = ArrayMethods::values($array);
// Returns: [1, 2, 3]

// Count elements
$count = ArrayMethods::count($array);
// Returns: 3
```

### Array Modification

```php
use FASTAPI\ArrayMethods;

$array = ['a' => 1, 'b' => 2, 'c' => 3];

// Remove element
$removed = ArrayMethods::remove($array, 'b');
// Returns: ['a' => 1, 'c' => 3]

// Remove multiple elements
$removed = ArrayMethods::remove($array, ['a', 'c']);
// Returns: ['b' => 2]

// Remove with callback
$removed = ArrayMethods::remove($array, function($value, $key) {
    return $value > 2;
});
// Returns: ['a' => 1, 'b' => 2]
```

### Array Filtering

```php
use FASTAPI\ArrayMethods;

$array = [1, 2, 3, 4, 5, 6];

// Filter even numbers
$even = ArrayMethods::filter($array, function($value) {
    return $value % 2 === 0;
});
// Returns: [2, 4, 6]

// Filter by key
$filtered = ArrayMethods::filter($array, function($value, $key) {
    return $key % 2 === 0;
});
// Returns: [1, 3, 5]
```

### Array Mapping

```php
use FASTAPI\ArrayMethods;

$array = [1, 2, 3, 4, 5];

// Double each value
$doubled = ArrayMethods::map($array, function($value) {
    return $value * 2;
});
// Returns: [2, 4, 6, 8, 10]

// Map with key
$mapped = ArrayMethods::map($array, function($value, $key) {
    return "Item {$key}: {$value}";
});
// Returns: ['Item 0: 1', 'Item 1: 2', ...]
```

### Array Sorting

```php
use FASTAPI\ArrayMethods;

$array = [3, 1, 4, 1, 5, 9];

// Sort ascending
$sorted = ArrayMethods::sort($array);
// Returns: [1, 1, 3, 4, 5, 9]

// Sort descending
$sorted = ArrayMethods::sort($array, 'desc');
// Returns: [9, 5, 4, 3, 1, 1]

// Sort with callback
$sorted = ArrayMethods::sort($array, function($a, $b) {
    return $b - $a; // Descending
});
// Returns: [9, 5, 4, 3, 1, 1]
```

## Usage Examples

### Data Processing

```php
use FASTAPI\StringMethods;
use FASTAPI\ArrayMethods;

// Process user input
$userInput = "  hello@world.com  ";
$cleanEmail = StringMethods::sanitize(trim($userInput), [' ']);

// Process form data
$formData = ['', 'john', null, 'doe', false];
$cleanData = ArrayMethods::clean($formData);

// Validate email format
$isValidEmail = StringMethods::isMatch($cleanEmail, '^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$');
```

### API Response Processing

```php
use FASTAPI\StringMethods;
use FASTAPI\ArrayMethods;

// Process API response
$response = [
    'user_name' => 'john_doe',
    'email_address' => 'john@example.com',
    'phone_number' => '123-456-7890'
];

// Convert keys to camel case
$processed = ArrayMethods::map($response, function($value, $key) {
    return [StringMethods::toCamelCase($key) => $value];
});

// Clean phone number
$phone = StringMethods::sanitize($response['phone_number'], ['-']);
```

### Database Integration

```php
use FASTAPI\StringMethods;
use FASTAPI\ArrayMethods;

// Process database results
$dbResults = [
    ['id' => 1, 'name' => '  John  ', 'email' => 'john@example.com'],
    ['id' => 2, 'name' => '  Jane  ', 'email' => 'jane@example.com'],
    ['id' => 3, 'name' => '', 'email' => null]
];

// Clean and validate data
$cleanResults = ArrayMethods::filter($dbResults, function($row) {
    return !empty($row['name']) && !empty($row['email']);
});

$processedResults = ArrayMethods::map($cleanResults, function($row) {
    return [
        'id' => $row['id'],
        'name' => StringMethods::trim($row['name']),
        'email' => StringMethods::sanitize($row['email'], [' '])
    ];
});
```

### File Processing

```php
use FASTAPI\StringMethods;
use FASTAPI\ArrayMethods;

// Process CSV data
$csvData = "name,email,phone\nJohn,john@example.com,123-456-7890\nJane,jane@example.com,987-654-3210";

$lines = StringMethods::split($csvData, "\n");
$headers = StringMethods::split($lines[0], ",");

$data = ArrayMethods::map(array_slice($lines, 1), function($line) use ($headers) {
    $values = StringMethods::split($line, ",");
    return array_combine($headers, $values);
});
```

### Validation Examples

```php
use FASTAPI\StringMethods;
use FASTAPI\ArrayMethods;

// Validate form data
$formData = [
    'username' => 'john_doe',
    'email' => 'john@example.com',
    'phone' => '123-456-7890'
];

$validationRules = [
    'username' => function($value) {
        return StringMethods::isMatch($value, '^[a-zA-Z0-9_]{3,20}$');
    },
    'email' => function($value) {
        return StringMethods::isMatch($value, '^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$');
    },
    'phone' => function($value) {
        $clean = StringMethods::sanitize($value, ['-', '(', ')', ' ']);
        return StringMethods::isMatch($clean, '^\d{10}$');
    }
];

$errors = [];
foreach ($validationRules as $field => $rule) {
    if (!isset($formData[$field]) || !$rule($formData[$field])) {
        $errors[] = "Invalid {$field}";
    }
}
```

## Best Practices

### 1. String Operations

```php
// Good: Use appropriate methods
$clean = StringMethods::sanitize($input, ['@', '#']);
$camelCase = StringMethods::toCamelCase($snake_case);

// Avoid: Manual string manipulation
// $clean = str_replace(['@', '#'], '', $input);
```

### 2. Array Operations

```php
// Good: Use utility methods
$clean = ArrayMethods::clean($array);
$first = ArrayMethods::first($array);

// Avoid: Manual array operations
// $clean = array_filter($array, function($value) { return !empty($value); });
```

### 3. Performance Considerations

```php
// Good: Chain operations efficiently
$result = StringMethods::sanitize(
    StringMethods::trim($input),
    ['@', '#', '$']
);

// Good: Use appropriate data structures
$unique = StringMethods::unique($string);
$clean = ArrayMethods::clean($array);
```

### 4. Error Handling

```php
// Good: Validate input
if (empty($input)) {
    return null;
}

$result = StringMethods::sanitize($input, ['@', '#']);

// Good: Handle edge cases
$array = [1, 2, 3];
$first = ArrayMethods::first($array) ?? 0;
```

The utility classes provide a comprehensive set of tools for common string and array operations, making development faster and more maintainable while ensuring consistent behavior across your application. 