# FastAPI Framework

FastPHP is a lightweight PHP framework designed to make building APIs fast, simple, and efficient. It provides a set of powerful features and tools to streamline the development process, allowing developers to focus on writing clean and maintainable code.

## Features

- **Routing**: FastAPI uses a simple and intuitive routing system to define API endpoints and their corresponding handlers.
- **Middleware**: Middleware can be added to the request-response cycle to perform tasks such as authentication, logging, or request modification.
- **Dependency Injection**: FastAPI supports dependency injection to manage and inject dependencies into route handlers and middleware.
- **JWT Token Handling**: The framework includes classes for generating, verifying, and refreshing JWT tokens, making authentication and authorization easy to implement.
- **Custom Time Handling**: FastAPI provides a custom time class with additional functionalities for date and time manipulation.
- **Error Handling**: Error handling is built into the framework, allowing developers to handle errors gracefully and return appropriate responses to clients.
- **Customizable**: FastAPI is highly customizable and can be extended with additional functionality as needed.

## Installation

To install FastAPI, simply run:

```bash
composer require progrmanial/fast-api
```

## Getting Started

### Creating Routes

Routes in FastAPI are defined using the `App` class. Here's an example of defining routes for a simple API:

```php
use FASTAPI\App;

$app = new App();

$app->get('/users', function($request, $response) {
    // Handle GET request to /users
});

$app->post('/users', function($request, $response) {
    // Handle POST request to /users
});

// More routes...
```

### Adding Middleware

Middleware can be added to the request-response cycle using the `addMiddleware` method:

```php
use FASTAPI\App;
use FASTAPI\Middleware\AuthMiddleware;

$app = new App();

$app->addMiddleware(new AuthMiddleware());

// Define routes...
```

### Generating JWT Tokens

FastAPI includes a `Token` class for generating and verifying JWT tokens:

```php
use FASTAPI\Token\Token;

$token = new Token('audience');

$jwtToken = $token->make(['user_id' => 123]);

// Verify token
$decodedToken = $token->verify($jwtToken);

// Access token data
$user_id = $decodedToken->data['user_id'];
```

### Custom Time Handling

FastAPI provides a `CustomTime` class for handling custom date and time operations:

```php
use FASTAPI\CustomTime\CustomTime;

$time = new CustomTime();

$currentTime = $time->get_date();
```

## Use Cases

### Building RESTful APIs

FastAPI is perfect for building RESTful APIs for web and mobile applications. Its simple routing system and middleware support make it easy to define API endpoints and add functionality such as authentication and error handling.

### Token-based Authentication

With FastAPI's built-in `Token` class, implementing token-based authentication is straightforward. Developers can generate, verify, and refresh JWT tokens with ease, ensuring secure authentication for their APIs.

### Custom Time Manipulation

The `CustomTime` class in FastAPI allows developers to perform various date and time manipulations, such as adding days, weeks, months, or years to a given date, comparing dates, or formatting dates in different formats.

### Error Handling

FastAPI comes with built-in error handling capabilities, allowing developers to handle errors gracefully and return meaningful responses to clients. This ensures a smooth and consistent user experience when interacting with the API.

### Middleware Integration

FastAPI supports middleware integration, enabling developers to add custom middleware to the request-response cycle. This allows for tasks such as authentication, logging, request modification, or response formatting to be easily implemented and applied to specific routes.

### Rapid Prototyping

FastAPI's lightweight and flexible architecture makes it ideal for rapid prototyping of API projects. Developers can quickly define routes, add middleware, and implement functionality without the need for extensive configuration or setup.

### Data Transformation

FastAPI can be used for data transformation tasks such as converting data between different formats (e.g., JSON to XML) or manipulating data structures. Its customizable nature allows developers to easily extend and adapt the framework to suit their specific data transformation needs.

### Real-time Applications

FastAPI can be used to build real-time applications such as chat applications, live updates, or real-time analytics dashboards. Its asynchronous capabilities and event-driven architecture make it well-suited for handling concurrent connections and processing real-time data streams.

## Contributing

FastAPI is an open-source project, and contributions are welcome! If you'd like to contribute, please fork the repository, make your changes, and submit a pull request. Be sure to follow the project's coding standards and guidelines.

## License

FastAPI is licensed under the MIT License.