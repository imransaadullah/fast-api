<?php

require_once '../src/Router.php';
require_once '../src/Request.php';
require_once '../src/Response.php';
require_once '../src/Middlewares/MiddlewareInterface.php';

use FASTAPI\Router;
use FASTAPI\Request;
use FASTAPI\Response;
use FASTAPI\Middlewares\MiddlewareInterface;

// Example middleware classes that your code would use
class AuthMiddleware implements MiddlewareInterface {
    public function handle(Request $request, \Closure $next): void {
        echo "🔒 AuthMiddleware: Checking authentication...\n";
        // Your auth logic here
        $next();
    }
}

class RoleMiddleware implements MiddlewareInterface {
    private $role;
    
    public function __construct($role) {
        $this->role = $role;
    }
    
    public function handle(Request $request, \Closure $next): void {
        echo "👤 RoleMiddleware: Checking role '{$this->role}'...\n";
        // Your role checking logic here
        $next();
    }
}

// Example controller classes (normally these would be in separate files)
// For demo purposes, we'll simulate them here
if (!class_exists('App\Controllers\Doctor\DoctorController')) {
    class DoctorController {
        public function dashboard(Request $request) {
            echo "📊 DoctorController@dashboard: Showing doctor dashboard\n";
            (new Response())->setJsonResponse(['page' => 'doctor_dashboard'])->send();
        }
        
        public function patients(Request $request) {
            echo "👥 DoctorController@patients: Listing patients\n";
            (new Response())->setJsonResponse(['patients' => ['patient1', 'patient2']])->send();
        }
    }
    
    class ScheduleController {
        public function index(Request $request) {
            echo "📅 ScheduleController@index: Showing schedule\n";
            (new Response())->setJsonResponse(['schedule' => 'doctor_schedule'])->send();
        }
        
        public function update(Request $request) {
            echo "✏️ ScheduleController@update: Updating schedule\n";
            (new Response())->setJsonResponse(['message' => 'Schedule updated'])->send();
        }
    }
    
    class ConsultationController {
        public function index(Request $request) {
            echo "💬 ConsultationController@index: Listing consultations\n";
            (new Response())->setJsonResponse(['consultations' => ['consultation1']])->send();
        }
        
        public function store(Request $request) {
            echo "➕ ConsultationController@store: Creating consultation\n";
            (new Response())->setJsonResponse(['message' => 'Consultation created'])->send();
        }
        
        public function update(Request $request, $id) {
            echo "✏️ ConsultationController@update: Updating consultation $id\n";
            (new Response())->setJsonResponse(['message' => "Consultation $id updated"])->send();
        }
    }
    
    // Register classes in the global namespace with aliases
    class_alias('DoctorController', 'App\Controllers\Doctor\DoctorController');
    class_alias('ScheduleController', 'App\Controllers\Doctor\ScheduleController');
    class_alias('ConsultationController', 'App\Controllers\Doctor\ConsultationController');
}

// Initialize the router
$router = new Router();

// Register your middleware (this is the setup step)
$router->registerMiddleware('auth', AuthMiddleware::class);
$router->registerMiddleware('role', RoleMiddleware::class);

// Set controller namespaces (optional, defaults to App\Controllers)
$router->setControllerNamespaces(['App\\Controllers\\']);

echo "🎉 Laravel-Style Syntax Demo\n";
echo "============================\n\n";

// YOUR EXACT EXAMPLE - IT WORKS! 🎯
// Doctor Routes (Protected, Doctor Role)
$router->group(['middleware' => ['auth', 'role:doctor']], function($router) {
    
    // Doctor Dashboard
    $router->get('/doctors/dashboard', 'App\Controllers\Doctor\DoctorController@dashboard');
    $router->get('/doctors/patients', 'App\Controllers\Doctor\DoctorController@patients');
    $router->get('/doctors/schedule', 'App\Controllers\Doctor\ScheduleController@index');
    $router->post('/doctors/schedule', 'App\Controllers\Doctor\ScheduleController@update');
    
    // Consultations
    $router->get('/consultations', 'App\Controllers\Doctor\ConsultationController@index');
    $router->post('/consultations', 'App\Controllers\Doctor\ConsultationController@store');
    $router->put('/consultations/{id}', 'App\Controllers\Doctor\ConsultationController@update');
});

echo "✅ Routes registered successfully!\n\n";

// Show what got registered
echo "📋 Registered Routes:\n";
echo "=====================\n";
$routes = $router->getCompiledRoutes();
foreach ($routes as $route) {
    $middlewareCount = count($route['middleware']);
    echo "• {$route['method']} {$route['final_uri']} → {$route['handler']} ($middlewareCount middleware)\n";
}

echo "\n🔧 Setup Required:\n";
echo "==================\n";
echo "1. Register your middleware:\n";
echo "   \$router->registerMiddleware('auth', AuthMiddleware::class);\n";
echo "   \$router->registerMiddleware('role', RoleMiddleware::class);\n\n";

echo "2. Set controller namespaces (optional):\n";
echo "   \$router->setControllerNamespaces(['App\\Controllers\\']);\n\n";

echo "3. Create your middleware classes implementing MiddlewareInterface\n";
echo "4. Create your controller classes with the methods\n\n";

echo "🚀 Additional Features:\n";
echo "=======================\n";
echo "• Both :param and {param} syntax supported\n";
echo "• Parameterized middleware: 'role:doctor', 'permission:admin'\n";
echo "• Auto-resolution of common middleware\n";
echo "• Full backward compatibility\n";
echo "• Nested route groups with middleware inheritance\n\n";

echo "🔒 Safety Features:\n";
echo "===================\n";
echo "• Graceful fallback if middleware not found\n";
echo "• Class existence checking\n";
echo "• Method existence validation\n";
echo "• Proper error handling\n";
echo "• No breaking changes to existing code\n\n";

echo "🎯 Your exact syntax works perfectly!\n";
echo "No changes needed to your route definitions.\n"; 