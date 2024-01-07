
# Fast API - Lightweight PHP Framework

Fast API is a lightweight and flexible PHP framework designed for building fast and expressive web applications. It provides a simple yet powerful structure to help developers create robust and efficient web solutions.

## Features

- **Expressive Routing:** Define routes easily using a simple and expressive syntax.
- **Middleware Support:** Implement and use middleware for handling requests at various stages of the application lifecycle.
- **Dependency Injection:** Promote flexibility and testability by injecting dependencies into your classes.
- **PSR-4 Autoloading:** Follows PSR-4 standards for autoloading, ensuring an organized and efficient codebase.

## Installation

Install Fast API in your project using Composer:

```bash
composer require progrmanial/fast-api
```

## Getting Started

### Basic Usage

```php
<?php

require 'vendor/autoload.php';

use FastAPI\App;
use FastAPI\Request;

$app = new App();

$app->get('/', function (Request $request) {
    echo json_encode(['message' => 'Welcome to the home page!'], JSON_PRETTY_PRINT);
});

$app->get('/:name/profile', function (Request $request, $name) {
    echo json_encode(['profile' => $name], JSON_PRETTY_PRINT);
});

$app->get('/:id/details', function (Request $request, $id) {
    echo json_encode(['details' => $id], JSON_PRETTY_PRINT);
});

$app->get('/:date/event', function (Request $request, $date) {
    echo json_encode(['event' => $date], JSON_PRETTY_PRINT);
});

$app->run();
```

### Define Routes

```php
$app->get('/about', function (Request $request) {
    echo "About Us Page";
});

$app->post('/submit-form', function (Request $request) {
    // Handle form submission
});
```

## Documentation

For detailed documentation and examples, please refer to the [official documentation](link-to-documentation).

## Contributing

If you'd like to contribute to Fast API, please follow our [contribution guidelines](CONTRIBUTING.md).

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.