# API Reference

Complete API reference for all FastAPI classes, methods, and properties.

## Table of Contents

- [App Class](#app-class)
- [Router Class](#router-class)
- [Request Class](#request-class)
- [Response Class](#response-class)
- [Token Class](#token-class)
- [CustomTime Class](#customtime-class)
- [StringMethods Class](#stringmethods-class)
- [ArrayMethods Class](#arraymethods-class)
- [WebSocket Classes](#websocket-classes)
- [Middleware Interface](#middleware-interface)

## App Class

The main application class that manages the application lifecycle.

### Methods

#### `getInstance(): App`
Returns the singleton instance of the App class.

**Returns:** `App` - The application instance

```php
$app = App::getInstance();
```

#### `get(string $uri, callable $handler): App`
Register a GET route.

**Parameters:**
- `$uri` (string) - The route URI
- `$handler` (callable) - The route handler

**Returns:** `App` - For method chaining

```php
$app->get('/users', function($request) {
    return new Response();
});
```

#### `post(string $uri, callable $handler): App`
Register a POST route.

**Parameters:**
- `$uri` (string) - The route URI
- `$handler` (callable) - The route handler

**Returns:** `App` - For method chaining

#### `put(string $uri, callable $handler): App`
Register a PUT route.

**Parameters:**
- `$uri` (string) - The route URI
- `$handler` (callable) - The route handler

**Returns:** `App` - For method chaining

#### `delete(string $uri, callable $handler): App`
Register a DELETE route.

**Parameters:**
- `$uri` (string) - The route URI
- `$handler` (callable) - The route handler

**Returns:** `App` - For method chaining

#### `patch(string $uri, callable $handler): App`
Register a PATCH route.

**Parameters:**
- `$uri` (string) - The route URI
- `$handler` (callable) - The route handler

**Returns:** `App` - For method chaining

#### `group(array $attributes, callable $callback): App`
Create a route group with common attributes.

**Parameters:**
- `$attributes` (array) - Group attributes (prefix, middleware, namespace)
- `$callback` (callable) - Group callback function

**Returns:** `App` - For method chaining

```php
$app->group(['prefix' => 'api'], function($app) {
    $app->get('/users', $handler);
});
```

#### `websocket(int $port = 8080, string $host = '0.0.0.0'): WebSocketServer`
Create a WebSocket server instance.

**Parameters:**
- `$port` (int) - Server port (default: 8080)
- `$host` (string) - Server host (default: '0.0.0.0')

**Returns:** `WebSocketServer` - WebSocket server instance

```php
$websocket = $app->websocket(8080, 'localhost');
```

#### `addMiddleware(callable $middleware): App`
Add global middleware to the application.

**Parameters:**
- `$middleware` (callable) - Middleware function

**Returns:** `App` - For method chaining

#### `setRateLimit(int $limit, int $window): App`
Set rate limiting for the application.

**Parameters:**
- `$limit` (int) - Maximum requests per window
- `$window` (int) - Time window in seconds

**Returns:** `App` - For method chaining

#### `setNotFoundHandler(callable $handler): App`
Set custom 404 handler.

**Parameters:**
- `$handler` (callable) - 404 handler function

**Returns:** `App` - For method chaining

#### `run(): void`
Start the application and handle requests.

```php
$app->run();
```

## Router Class

Advanced routing with support for groups and middleware.

### Methods

#### `__construct()`
Create a new Router instance.

```php
$router = new Router();
```

#### `addRoute(string $method, string $uri, callable $handler): Router`
Add a route with custom HTTP method.

**Parameters:**
- `$method` (string) - HTTP method (GET, POST, PUT, DELETE, PATCH)
- `$uri` (string) - Route URI
- `$handler` (callable) - Route handler

**Returns:** `Router` - For method chaining

#### `get(string $uri, callable $handler): Router`
Register a GET route.

**Parameters:**
- `$uri` (string) - Route URI
- `$handler` (callable) - Route handler

**Returns:** `Router` - For method chaining

#### `post(string $uri, callable $handler): Router`
Register a POST route.

**Parameters:**
- `$uri` (string) - Route URI
- `$handler` (callable) - Route handler

**Returns:** `Router` - For method chaining

#### `put(string $uri, callable $handler): Router`
Register a PUT route.

**Parameters:**
- `$uri` (string) - Route URI
- `$handler` (callable) - Route handler

**Returns:** `Router` - For method chaining

#### `delete(string $uri, callable $handler): Router`
Register a DELETE route.

**Parameters:**
- `$uri` (string) - Route URI
- `$handler` (callable) - Route handler

**Returns:** `Router` - For method chaining

#### `patch(string $uri, callable $handler): Router`
Register a PATCH route.

**Parameters:**
- `$uri` (string) - Route URI
- `$handler` (callable) - Route handler

**Returns:** `Router` - For method chaining

#### `group(array $attributes, callable $callback): Router`
Create a route group.

**Parameters:**
- `$attributes` (array) - Group attributes
- `$callback` (callable) - Group callback

**Returns:** `Router` - For method chaining

#### `registerMiddleware(string $name, string $class): Router`
Register middleware for string resolution.

**Parameters:**
- `$name` (string) - Middleware name
- `$class` (string) - Middleware class name

**Returns:** `Router` - For method chaining

#### `dispatch(Request $request): void`
Dispatch a request to the appropriate route.

**Parameters:**
- `$request` (Request) - Request object

#### `getRoutes(): array`
Get all registered routes.

**Returns:** `array` - Array of routes

#### `getCompiledRoutes(): array`
Get routes with group information.

**Returns:** `array` - Array of compiled routes

## Request Class

Rich request object with dynamic properties and validation.

### Properties

- `$method` (string) - HTTP method
- `$uri` (string) - Request URI
- `$data` (array) - Request data
- `$headers` (array) - Request headers
- `$attributes` (array) - Request attributes

### Methods

#### `__construct(string $method, string $uri, array $data = [])`
Create a new Request instance.

**Parameters:**
- `$method` (string) - HTTP method
- `$uri` (string) - Request URI
- `$data` (array) - Request data

#### `getMethod(): string`
Get the HTTP method.

**Returns:** `string` - HTTP method

#### `getUri(): string`
Get the request URI.

**Returns:** `string` - Request URI

#### `getData(): array`
Get request data.

**Returns:** `array` - Request data

#### `getHeaders(): array`
Get request headers.

**Returns:** `array` - Request headers

#### `getHeader(string $name): ?string`
Get specific header value.

**Parameters:**
- `$name` (string) - Header name

**Returns:** `?string` - Header value or null

#### `withHeader(string $name, string $value): Request`
Add header to request.

**Parameters:**
- `$name` (string) - Header name
- `$value` (string) - Header value

**Returns:** `Request` - Modified request

#### `setAttribute(string $name, mixed $value): void`
Set request attribute.

**Parameters:**
- `$name` (string) - Attribute name
- `$value` (mixed) - Attribute value

#### `getAttribute(string $name): mixed`
Get request attribute.

**Parameters:**
- `$name` (string) - Attribute name

**Returns:** `mixed` - Attribute value

#### `getFiles(): array`
Get uploaded files.

**Returns:** `array` - Uploaded files

#### `getJsonData(): array`
Get JSON request data.

**Returns:** `array` - JSON data

#### `getQueryParam(string $name): ?string`
Get query parameter.

**Parameters:**
- `$name` (string) - Parameter name

**Returns:** `?string` - Parameter value or null

#### `validateData(array $rules): bool`
Validate request data.

**Parameters:**
- `$rules` (array) - Validation rules

**Returns:** `bool` - Validation result

#### `toArray(): array`
Convert request to array.

**Returns:** `array` - Request as array

## Response Class

Comprehensive response handling with multiple content types.

### Methods

#### `__construct()`
Create a new Response instance.

#### `setJsonResponse(array $data, int $statusCode = 200): Response`
Set JSON response.

**Parameters:**
- `$data` (array) - Response data
- `$statusCode` (int) - HTTP status code

**Returns:** `Response` - For method chaining

#### `setHtmlResponse(string $html, int $statusCode = 200): Response`
Set HTML response.

**Parameters:**
- `$html` (string) - HTML content
- `$statusCode` (int) - HTTP status code

**Returns:** `Response` - For method chaining

#### `setFileResponse(string $filePath, string $filename = null): Response`
Set file download response.

**Parameters:**
- `$filePath` (string) - File path
- `$filename` (string) - Download filename

**Returns:** `Response` - For method chaining

#### `setErrorResponse(string $message, int $statusCode = 500): Response`
Set error response.

**Parameters:**
- `$message` (string) - Error message
- `$statusCode` (int) - HTTP status code

**Returns:** `Response` - For method chaining

#### `renderHtmlTemplate(string $templatePath, array $data = []): Response`
Render HTML template.

**Parameters:**
- `$templatePath` (string) - Template file path
- `$data` (array) - Template data

**Returns:** `Response` - For method chaining

#### `setHeader(string $name, string $value): Response`
Set response header.

**Parameters:**
- `$name` (string) - Header name
- `$value` (string) - Header value

**Returns:** `Response` - For method chaining

#### `addCookie(string $name, string $value, int $expiry = 0): Response`
Add cookie to response.

**Parameters:**
- `$name` (string) - Cookie name
- `$value` (string) - Cookie value
- `$expiry` (int) - Cookie expiry time

**Returns:** `Response` - For method chaining

#### `setStatusCode(int $code): Response`
Set HTTP status code.

**Parameters:**
- `$code` (int) - HTTP status code

**Returns:** `Response` - For method chaining

#### `setStreamingResponse(callable $callback): Response`
Set streaming response.

**Parameters:**
- `$callback` (callable) - Streaming callback

**Returns:** `Response` - For method chaining

#### `setEtag(string $etag): Response`
Set ETag header.

**Parameters:**
- `$etag` (string) - ETag value

**Returns:** `Response` - For method chaining

#### `setLastModified(DateTime $date): Response`
Set Last-Modified header.

**Parameters:**
- `$date` (DateTime) - Last modified date

**Returns:** `Response` - For method chaining

#### `send(): void`
Send the response to the client.

## Token Class

JWT token handling with encryption support.

### Methods

#### `__construct(string $audience, ?string $issuer = null, bool $useSSL = false)`
Create a new Token instance.

**Parameters:**
- `$audience` (string) - Token audience
- `$issuer` (?string) - Token issuer
- `$useSSL` (bool) - Use SSL/RSA

#### `set_secret_key(string $key): Token`
Set secret key for token signing.

**Parameters:**
- `$key` (string) - Secret key

**Returns:** `Token` - For method chaining

#### `make(array $payload, ?int $expiry = null): string`
Generate JWT token.

**Parameters:**
- `$payload` (array) - Token payload
- `$expiry` (?int) - Expiry timestamp

**Returns:** `string` - JWT token

#### `verify(string $token): ?array`
Verify JWT token.

**Parameters:**
- `$token` (string) - JWT token

**Returns:** `?array` - Decoded payload or null

#### `refresh(string $token, int $newExpiry): string`
Refresh JWT token.

**Parameters:**
- `$token` (string) - Original token
- `$newExpiry` (int) - New expiry time

**Returns:** `string` - Refreshed token

#### `is_expired(string $token): bool`
Check if token is expired.

**Parameters:**
- `$token` (string) - JWT token

**Returns:** `bool` - True if expired

#### `get_token(): string`
Get the generated token.

**Returns:** `string` - JWT token

#### `get_data(): array`
Get token payload data.

**Returns:** `array` - Token payload

#### `encrypt_token_payload(array $data, string $key): string`
Encrypt token payload.

**Parameters:**
- `$data` (array) - Data to encrypt
- `$key` (string) - Encryption key

**Returns:** `string` - Encrypted data

#### `decrypt_token_payload(string $encrypted, string $key): array`
Decrypt token payload.

**Parameters:**
- `$encrypted` (string) - Encrypted data
- `$key` (string) - Decryption key

**Returns:** `array` - Decrypted data

## CustomTime Class

Advanced date/time manipulation with timezone support.

### Methods

#### `__construct(?string $date = null, ?DateTimeZone $timezone = null)`
Create a new CustomTime instance.

**Parameters:**
- `$date` (?string) - Date string
- `$timezone` (?DateTimeZone) - Timezone

#### `now(?string $format = null): string|int`
Get current time.

**Parameters:**
- `$format` (?string) - Date format

**Returns:** `string|int` - Current time

#### `add_years(int $years): CustomTime`
Add years to date.

**Parameters:**
- `$years` (int) - Years to add

**Returns:** `CustomTime` - New instance

#### `add_months(int $months): CustomTime`
Add months to date.

**Parameters:**
- `$months` (int) - Months to add

**Returns:** `CustomTime` - New instance

#### `add_weeks(int $weeks): CustomTime`
Add weeks to date.

**Parameters:**
- `$weeks` (int) - Weeks to add

**Returns:** `CustomTime` - New instance

#### `add_days(int $days): CustomTime`
Add days to date.

**Parameters:**
- `$days` (int) - Days to add

**Returns:** `CustomTime` - New instance

#### `add_hours(int $hours): CustomTime`
Add hours to date.

**Parameters:**
- `$hours` (int) - Hours to add

**Returns:** `CustomTime` - New instance

#### `add_minutes(int $minutes): CustomTime`
Add minutes to date.

**Parameters:**
- `$minutes` (int) - Minutes to add

**Returns:** `CustomTime` - New instance

#### `add_seconds(int $seconds): CustomTime`
Add seconds to date.

**Parameters:**
- `$seconds` (int) - Seconds to add

**Returns:** `CustomTime` - New instance

#### `subtract_years(int $years): CustomTime`
Subtract years from date.

**Parameters:**
- `$years` (int) - Years to subtract

**Returns:** `CustomTime` - New instance

#### `subtract_months(int $months): CustomTime`
Subtract months from date.

**Parameters:**
- `$months` (int) - Months to subtract

**Returns:** `CustomTime` - New instance

#### `subtract_weeks(int $weeks): CustomTime`
Subtract weeks from date.

**Parameters:**
- `$weeks` (int) - Weeks to subtract

**Returns:** `CustomTime` - New instance

#### `subtract_days(int $days): CustomTime`
Subtract days from date.

**Parameters:**
- `$days` (int) - Days to subtract

**Returns:** `CustomTime` - New instance

#### `subtract_hours(int $hours): CustomTime`
Subtract hours from date.

**Parameters:**
- `$hours` (int) - Hours to subtract

**Returns:** `CustomTime` - New instance

#### `subtract_minutes(int $minutes): CustomTime`
Subtract minutes from date.

**Parameters:**
- `$minutes` (int) - Minutes to subtract

**Returns:** `CustomTime` - New instance

#### `subtract_seconds(int $seconds): CustomTime`
Subtract seconds from date.

**Parameters:**
- `$seconds` (int) - Seconds to subtract

**Returns:** `CustomTime` - New instance

#### `set_timezone(string $timezone): CustomTime`
Set timezone.

**Parameters:**
- `$timezone` (string) - Timezone name

**Returns:** `CustomTime` - New instance

#### `get_date(?string $format = null): string`
Get formatted date.

**Parameters:**
- `$format` (?string) - Date format

**Returns:** `string` - Formatted date

#### `get_timestamp(): int`
Get Unix timestamp.

**Returns:** `int` - Unix timestamp

#### `get_utc_time(?string $format = null): string`
Get UTC time.

**Parameters:**
- `$format` (?string) - Time format

**Returns:** `string` - UTC time

#### `isBefore(CustomTime|string $date): bool`
Check if date is before another.

**Parameters:**
- `$date` (CustomTime|string) - Date to compare

**Returns:** `bool` - True if before

#### `isAfter(CustomTime|string $date): bool`
Check if date is after another.

**Parameters:**
- `$date` (CustomTime|string) - Date to compare

**Returns:** `bool` - True if after

#### `equals(CustomTime|string $date): bool`
Check if dates are equal.

**Parameters:**
- `$date` (CustomTime|string) - Date to compare

**Returns:** `bool` - True if equal

#### `diffInDays(CustomTime|string $date, bool $absolute = true): int`
Get difference in days.

**Parameters:**
- `$date` (CustomTime|string) - Date to compare
- `$absolute` (bool) - Return absolute value

**Returns:** `int` - Days difference

#### `diffInHours(CustomTime|string $date, bool $absolute = true): int`
Get difference in hours.

**Parameters:**
- `$date` (CustomTime|string) - Date to compare
- `$absolute` (bool) - Return absolute value

**Returns:** `int` - Hours difference

#### `diffInMinutes(CustomTime|string $date, bool $absolute = true): int`
Get difference in minutes.

**Parameters:**
- `$date` (CustomTime|string) - Date to compare
- `$absolute` (bool) - Return absolute value

**Returns:** `int` - Minutes difference

## StringMethods Class

Static utility methods for string manipulation.

### Methods

#### `match(string $string, string $pattern): array`
Find regex matches in string.

**Parameters:**
- `$string` (string) - Input string
- `$pattern` (string) - Regex pattern

**Returns:** `array` - Matches array

#### `matchAll(string $string, string $pattern): array`
Find all regex matches in string.

**Parameters:**
- `$string` (string) - Input string
- `$pattern` (string) - Regex pattern

**Returns:** `array` - All matches array

#### `isMatch(string $string, string $pattern): bool`
Check if string matches pattern.

**Parameters:**
- `$string` (string) - Input string
- `$pattern` (string) - Regex pattern

**Returns:** `bool` - True if matches

#### `split(string $string, string $delimiter, ?int $limit = null): array`
Split string by delimiter.

**Parameters:**
- `$string` (string) - Input string
- `$delimiter` (string) - Split delimiter
- `$limit` (?int) - Maximum splits

**Returns:** `array` - Split parts

#### `join(array $array, string $glue): string`
Join array with glue string.

**Parameters:**
- `$array` (array) - Array to join
- `$glue` (string) - Glue string

**Returns:** `string` - Joined string

#### `sanitize(string $string, array $characters): string`
Remove characters from string.

**Parameters:**
- `$string` (string) - Input string
- `$characters` (array) - Characters to remove

**Returns:** `string` - Sanitized string

#### `unique(string $string): string`
Remove duplicate characters.

**Parameters:**
- `$string` (string) - Input string

**Returns:** `string` - String with unique characters

#### `toCamelCase(string $string): string`
Convert to camel case.

**Parameters:**
- `$string` (string) - Input string

**Returns:** `string` - Camel case string

#### `toSnakeCase(string $string): string`
Convert to snake case.

**Parameters:**
- `$string` (string) - Input string

**Returns:** `string` - Snake case string

#### `toKebabCase(string $string): string`
Convert to kebab case.

**Parameters:**
- `$string` (string) - Input string

**Returns:** `string` - Kebab case string

#### `replaceString(string $string, string|array $search, string|array $replace): string`
Replace string content.

**Parameters:**
- `$string` (string) - Input string
- `$search` (string|array) - Search string(s)
- `$replace` (string|array) - Replace string(s)

**Returns:** `string` - Replaced string

#### `indexOf(string $string, string $substring): int`
Find substring index.

**Parameters:**
- `$string` (string) - Input string
- `$substring` (string) - Substring to find

**Returns:** `int` - Substring index

#### `contains(string $string, string $substring): bool`
Check if string contains substring.

**Parameters:**
- `$string` (string) - Input string
- `$substring` (string) - Substring to find

**Returns:** `bool` - True if contains

#### `startsWith(string $string, string $substring): bool`
Check if string starts with substring.

**Parameters:**
- `$string` (string) - Input string
- `$substring` (string) - Substring to check

**Returns:** `bool` - True if starts with

#### `endsWith(string $string, string $substring): bool`
Check if string ends with substring.

**Parameters:**
- `$string` (string) - Input string
- `$substring` (string) - Substring to check

**Returns:** `bool` - True if ends with

#### `plural(string $string): string`
Make string plural.

**Parameters:**
- `$string` (string) - Input string

**Returns:** `string` - Plural string

#### `singular(string $string): string`
Make string singular.

**Parameters:**
- `$string` (string) - Input string

**Returns:** `string` - Singular string

## ArrayMethods Class

Static utility methods for array manipulation.

### Methods

#### `clean(array $array, mixed $values = null): array`
Remove empty values from array.

**Parameters:**
- `$array` (array) - Input array
- `$values` (mixed) - Values to remove

**Returns:** `array` - Cleaned array

#### `trim(array $array, mixed $characters = null): array`
Trim array values.

**Parameters:**
- `$array` (array) - Input array
- `$characters` (mixed) - Characters to trim

**Returns:** `array` - Trimmed array

#### `toObject(array $array): object`
Convert array to object.

**Parameters:**
- `$array` (array) - Input array

**Returns:** `object` - stdClass object

#### `flatten(array $array, int $depth = null): array`
Flatten nested array.

**Parameters:**
- `$array` (array) - Input array
- `$depth` (int) - Flatten depth

**Returns:** `array` - Flattened array

#### `first(array $array): mixed`
Get first array element.

**Parameters:**
- `$array` (array) - Input array

**Returns:** `mixed` - First element

#### `last(array $array): mixed`
Get last array element.

**Parameters:**
- `$array` (array) - Input array

**Returns:** `mixed` - Last element

#### `get(array $array, mixed $key, mixed $default = null): mixed`
Get array element with default.

**Parameters:**
- `$array` (array) - Input array
- `$key` (mixed) - Array key
- `$default` (mixed) - Default value

**Returns:** `mixed` - Array element

#### `has(array $array, mixed $key): bool`
Check if array has key.

**Parameters:**
- `$array` (array) - Input array
- `$key` (mixed) - Array key

**Returns:** `bool` - True if has key

#### `keys(array $array): array`
Get array keys.

**Parameters:**
- `$array` (array) - Input array

**Returns:** `array` - Array keys

#### `values(array $array): array`
Get array values.

**Parameters:**
- `$array` (array) - Input array

**Returns:** `array` - Array values

#### `count(array $array): int`
Count array elements.

**Parameters:**
- `$array` (array) - Input array

**Returns:** `int` - Element count

#### `remove(array $array, mixed $keys): array`
Remove elements from array.

**Parameters:**
- `$array` (array) - Input array
- `$keys` (mixed) - Keys to remove

**Returns:** `array` - Array without removed elements

#### `filter(array $array, callable $callback): array`
Filter array elements.

**Parameters:**
- `$array` (array) - Input array
- `$callback` (callable) - Filter callback

**Returns:** `array` - Filtered array

#### `map(array $array, callable $callback): array`
Map array elements.

**Parameters:**
- `$array` (array) - Input array
- `$callback` (callable) - Map callback

**Returns:** `array` - Mapped array

#### `sort(array $array, string|callable $direction = 'asc'): array`
Sort array elements.

**Parameters:**
- `$array` (array) - Input array
- `$direction` (string|callable) - Sort direction or callback

**Returns:** `array` - Sorted array

## WebSocket Classes

### WebSocketServer

#### `__construct(?App $app = null)`
Create WebSocket server.

**Parameters:**
- `$app` (?App) - App instance

#### `on(string $path, callable $handler): WebSocketServer`
Register WebSocket route.

**Parameters:**
- `$path` (string) - WebSocket path
- `$handler` (callable) - Connection handler

**Returns:** `WebSocketServer` - For method chaining

#### `port(int $port): WebSocketServer`
Set server port.

**Parameters:**
- `$port` (int) - Server port

**Returns:** `WebSocketServer` - For method chaining

#### `host(string $host): WebSocketServer`
Set server host.

**Parameters:**
- `$host` (string) - Server host

**Returns:** `WebSocketServer` - For method chaining

#### `start(): void`
Start WebSocket server.

#### `stop(): void`
Stop WebSocket server.

#### `broadcast(string $event, mixed $payload = null): void`
Broadcast message to all connections.

**Parameters:**
- `$event` (string) - Event name
- `$payload` (mixed) - Event payload

#### `getConnectionCount(): int`
Get active connection count.

**Returns:** `int` - Connection count

#### `getConnections(): array`
Get all connections.

**Returns:** `array` - Connection array

### WebSocketConnection

#### `__construct(resource|\Socket $socket)`
Create WebSocket connection.

**Parameters:**
- `$socket` (resource|\Socket) - Socket resource

#### `handshake(): bool`
Perform WebSocket handshake.

**Returns:** `bool` - Handshake success

#### `read(): ?string`
Read WebSocket message.

**Returns:** `?string` - Message or null

#### `send(string $message): bool`
Send WebSocket message.

**Parameters:**
- `$message` (string) - Message to send

**Returns:** `bool` - Send success

#### `close(): void`
Close connection.

#### `isConnected(): bool`
Check connection status.

**Returns:** `bool` - True if connected

#### `getPath(): string`
Get connection path.

**Returns:** `string` - Request path

#### `getHeaders(): array`
Get request headers.

**Returns:** `array` - Headers array

#### `getHeader(string $name): ?string`
Get specific header.

**Parameters:**
- `$name` (string) - Header name

**Returns:** `?string` - Header value

## Middleware Interface

### Methods

#### `handle(Request $request, \Closure $next): void`
Handle middleware logic.

**Parameters:**
- `$request` (Request) - Request object
- `$next` (\Closure) - Next middleware callback

This comprehensive API reference provides all the information needed to use FastAPI effectively, with proper method signatures, parameters, return types, and usage examples. 