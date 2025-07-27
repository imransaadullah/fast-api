# CustomTime Class Documentation

The CustomTime class provides advanced date and time manipulation capabilities with timezone support, extending PHP's `DateTimeImmutable` for immutability and method chaining.

## Table of Contents

- [Quick Start](#quick-start)
- [Basic Usage](#basic-usage)
- [Static Methods](#static-methods)
- [Date Arithmetic](#date-arithmetic)
- [Timezone Handling](#timezone-handling)
- [Formatting](#formatting)
- [Comparisons](#comparisons)
- [Advanced Features](#advanced-features)
- [Examples](#examples)

## Quick Start

```php
use FASTAPI\CustomTime\CustomTime;

// Create current time
$time = new CustomTime();

// Create specific date
$specific = new CustomTime('2024-01-15 14:30:00');

// Use static method
$now = CustomTime::now();
```

## Basic Usage

### Creating CustomTime Instances

```php
use FASTAPI\CustomTime\CustomTime;

// Current time (default)
$time = new CustomTime();

// Specific date string
$time = new CustomTime('2024-01-15');
$time = new CustomTime('2024-01-15 14:30:00');

// Unix timestamp
$time = new CustomTime('@1642248600');

// DateTime object
$dateTime = new DateTime('2024-01-15');
$time = new CustomTime($dateTime);

// With timezone
$time = new CustomTime('2024-01-15', new DateTimeZone('America/New_York'));
```

### Static Methods

```php
// Get current timestamp
$timestamp = CustomTime::now();

// Get formatted current time
$formatted = CustomTime::now('Y-m-d H:i:s'); // 2024-01-15 14:30:00

// Get current time with custom format
$date = CustomTime::now('Y-m-d'); // 2024-01-15
$time = CustomTime::now('H:i:s'); // 14:30:00
```

## Date Arithmetic

### Adding Time

```php
$time = new CustomTime('2024-01-15 14:30:00');

// Add specific units
$future = $time->add_years(1)
               ->add_months(6)
               ->add_weeks(2)
               ->add_days(10)
               ->add_hours(5)
               ->add_minutes(30)
               ->add_seconds(45);

// Add multiple units at once
$future = $time->extend_date(7, 2, 30, 0); // 7 days, 2 hours, 30 minutes
```

### Subtracting Time

```php
$time = new CustomTime('2024-01-15 14:30:00');

// Subtract specific units
$past = $time->subtract_years(1)
              ->subtract_months(6)
              ->subtract_weeks(2)
              ->subtract_days(10)
              ->subtract_hours(5)
              ->subtract_minutes(30)
              ->subtract_seconds(45);
```

### Method Chaining

```php
// Chain multiple operations
$result = (new CustomTime())
    ->add_days(7)
    ->add_hours(2)
    ->format('Y-m-d H:i:s');

// Complex calculations
$deadline = (new CustomTime())
    ->add_months(3)
    ->add_weeks(2)
    ->add_days(5)
    ->format('Y-m-d H:i:s');
```

## Timezone Handling

### Setting Timezone

```php
$time = new CustomTime('2024-01-15 14:30:00');

// Set timezone
$time->set_timezone('America/New_York');

// Get UTC time
$utcTime = $time->get_utc_time('H:i:s');

// Get timezone info
$timezone = $time->getTimezone();
$timezoneName = $timezone->getName();
```

### Timezone Conversions

```php
$time = new CustomTime('2024-01-15 14:30:00', new DateTimeZone('UTC'));

// Convert to different timezone
$nyTime = $time->set_timezone('America/New_York');
$tokyoTime = $time->set_timezone('Asia/Tokyo');

// Get formatted time in specific timezone
$nyFormatted = $time->set_timezone('America/New_York')->format('Y-m-d H:i:s T');
```

## Formatting

### Basic Formatting

```php
$time = new CustomTime('2024-01-15 14:30:00');

// Standard formats
$date = $time->get_date('Y-m-d');           // 2024-01-15
$time = $time->get_date('H:i:s');           // 14:30:00
$datetime = $time->get_date('Y-m-d H:i:s'); // 2024-01-15 14:30:00

// Custom formats
$iso = $time->get_date('c');                // ISO 8601
$rfc = $time->get_date('r');                // RFC 2822
$atom = $time->get_date('Y-m-d\TH:i:sP');  // Atom format
```

### Advanced Formatting

```php
$time = new CustomTime('2024-01-15 14:30:00');

// With timezone
$formatted = $time->set_timezone('America/New_York')
                  ->set_format('Y-m-d H:i:s T');

// Relative time
$relative = $time->diffForHumans();

// Custom format with timezone
$custom = $time->set_format('l, F j, Y \a\t g:i A T');
```

## Comparisons

### Comparing Dates

```php
$time1 = new CustomTime('2024-01-15 14:30:00');
$time2 = new CustomTime('2024-01-16 14:30:00');

// Basic comparisons
$isBefore = $time1->isBefore($time2);   // true
$isAfter = $time1->isAfter($time2);     // false
$isEqual = $time1->equals($time2);      // false

// Compare with strings
$isBefore = $time1->isBefore('2024-01-16');
$isAfter = $time1->isAfter('2024-01-14');
```

### Date Differences

```php
$time1 = new CustomTime('2024-01-15 14:30:00');
$time2 = new CustomTime('2024-01-20 14:30:00');

// Get differences
$daysDiff = $time1->diffInDays($time2);     // 5
$hoursDiff = $time1->diffInHours($time2);   // 120
$minutesDiff = $time1->diffInMinutes($time2); // 7200

// Absolute differences
$absDays = $time1->diffInDays($time2, false); // Always positive
```

## Advanced Features

### Serialization

```php
$time = new CustomTime('2024-01-15 14:30:00');

// Serialize
$serialized = $time->serialize();

// Deserialize
$restored = CustomTime::deserialize($serialized);
```

### Timestamp Operations

```php
$time = new CustomTime('2024-01-15 14:30:00');

// Get timestamp
$timestamp = $time->get_timestamp();

// Create from timestamp
$fromTimestamp = new CustomTime('@' . $timestamp);

// Get microtime
$microtime = $time->get_microtime();
```

### Validation

```php
// Check if date is valid
$isValid = CustomTime::isValid('2024-01-15'); // true
$isValid = CustomTime::isValid('invalid-date'); // false

// Parse date safely
$time = CustomTime::parse('2024-01-15');
if ($time) {
    echo "Valid date: " . $time->format('Y-m-d');
}
```

## Examples

### Business Logic Examples

```php
// Calculate project deadline
$startDate = new CustomTime('2024-01-15');
$deadline = $startDate->add_weeks(8)->add_days(3);

// Calculate age
$birthDate = new CustomTime('1990-05-15');
$today = new CustomTime();
$age = $birthDate->diffInYears($today);

// Calculate working days
$start = new CustomTime('2024-01-15');
$end = new CustomTime('2024-01-25');
$workingDays = $start->diffInWeekdays($end);

// Schedule next meeting
$lastMeeting = new CustomTime('2024-01-15 14:00:00');
$nextMeeting = $lastMeeting->add_weeks(2)->set_time(9, 0, 0);
```

### API Response Examples

```php
// Format for API response
$time = new CustomTime();

$response = [
    'timestamp' => $time->get_timestamp(),
    'iso8601' => $time->get_date('c'),
    'formatted' => $time->get_date('Y-m-d H:i:s'),
    'timezone' => $time->getTimezone()->getName(),
    'utc' => $time->get_utc_time('H:i:s')
];

// Relative time for UI
$relative = [
    'created_at' => $time->diffForHumans(),
    'updated_at' => $time->add_hours(2)->diffForHumans()
];
```

### Database Integration

```php
// Store in database
$time = new CustomTime();
$dbRecord = [
    'created_at' => $time->get_date('Y-m-d H:i:s'),
    'timestamp' => $time->get_timestamp(),
    'timezone' => $time->getTimezone()->getName()
];

// Retrieve from database
$dbTime = new CustomTime($dbRecord['created_at']);
$formatted = $dbTime->set_timezone($dbRecord['timezone'])
                    ->format('Y-m-d H:i:s T');
```

### Event Scheduling

```php
// Schedule recurring events
$startDate = new CustomTime('2024-01-15 09:00:00');

// Weekly meetings
$weeklyMeetings = [];
for ($i = 0; $i < 12; $i++) {
    $weeklyMeetings[] = $startDate->add_weeks($i)->get_date('Y-m-d H:i:s');
}

// Monthly reminders
$monthlyReminders = [];
for ($i = 1; $i <= 12; $i++) {
    $monthlyReminders[] = $startDate->add_months($i)->get_date('Y-m-d H:i:s');
}
```

## Best Practices

### 1. Immutability

```php
// Good: Create new instances
$time = new CustomTime('2024-01-15');
$future = $time->add_days(7); // Original unchanged

// Avoid: Modifying original
// $time->add_days(7); // Don't do this
```

### 2. Method Chaining

```php
// Good: Chain operations
$result = (new CustomTime())
    ->add_days(7)
    ->add_hours(2)
    ->format('Y-m-d H:i:s');

// Avoid: Multiple assignments
// $time = new CustomTime();
// $time = $time->add_days(7);
// $time = $time->add_hours(2);
```

### 3. Timezone Handling

```php
// Good: Explicit timezone handling
$time = new CustomTime('2024-01-15 14:30:00', new DateTimeZone('UTC'));
$localTime = $time->set_timezone('America/New_York');

// Avoid: Assuming timezone
// $time = new CustomTime('2024-01-15 14:30:00'); // May cause issues
```

### 4. Validation

```php
// Good: Validate dates
if (CustomTime::isValid($dateString)) {
    $time = new CustomTime($dateString);
}

// Avoid: Blind creation
// $time = new CustomTime($dateString); // May throw exception
```

## Performance Considerations

- **Immutable Design**: Each operation returns a new instance, ensuring thread safety
- **Efficient Formatting**: Use appropriate format strings for your use case
- **Timezone Caching**: Timezone objects are cached for better performance
- **Memory Management**: Old instances are garbage collected automatically

## Error Handling

```php
try {
    $time = new CustomTime('invalid-date');
} catch (Exception $e) {
    // Handle invalid date
    $time = new CustomTime(); // Use current time as fallback
}

// Safe parsing
$time = CustomTime::parse('2024-01-15');
if ($time === null) {
    // Handle parsing failure
}
```

The CustomTime class provides a robust, immutable, and chainable interface for date and time manipulation, making it perfect for modern PHP applications that require precise time handling with timezone support. 