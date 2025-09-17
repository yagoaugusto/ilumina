<?php

// Simple bootstrap for testing without external dependencies
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    // Create a simple health endpoint without dependencies
    if ($_SERVER['REQUEST_URI'] === '/health') {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'ok',
            'timestamp' => date('Y-m-d H:i:s'),
            'service' => 'Ilumina API',
            'version' => '1.0.0',
            'message' => 'Running in simple mode - install composer dependencies for full functionality'
        ]);
        exit;
    }
    
    // Serve frontend for root path
    if ($_SERVER['REQUEST_URI'] === '/' || $_SERVER['REQUEST_URI'] === '') {
        $frontendPath = __DIR__ . '/../frontend/index.html';
        if (file_exists($frontendPath)) {
            header('Content-Type: text/html');
            echo file_get_contents($frontendPath);
            exit;
        }
    }
    
    // Serve static assets
    if (preg_match('/^\/assets\/(.+)$/', $_SERVER['REQUEST_URI'], $matches)) {
        $filePath = __DIR__ . '/../frontend/assets/' . $matches[1];
        
        if (file_exists($filePath)) {
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            $contentType = 'text/plain';
            
            switch ($extension) {
                case 'css':
                    $contentType = 'text/css';
                    break;
                case 'js':
                    $contentType = 'application/javascript';
                    break;
                case 'png':
                    $contentType = 'image/png';
                    break;
                case 'jpg':
                case 'jpeg':
                    $contentType = 'image/jpeg';
                    break;
            }
            
            header('Content-Type: ' . $contentType);
            echo file_get_contents($filePath);
            exit;
        }
    }
    
    // Serve PWA files
    if ($_SERVER['REQUEST_URI'] === '/manifest.json') {
        $manifestPath = __DIR__ . '/../frontend/manifest.json';
        if (file_exists($manifestPath)) {
            header('Content-Type: application/json');
            echo file_get_contents($manifestPath);
            exit;
        }
    }
    
    if ($_SERVER['REQUEST_URI'] === '/sw.js') {
        $swPath = __DIR__ . '/../frontend/sw.js';
        if (file_exists($swPath)) {
            header('Content-Type: application/javascript');
            echo file_get_contents($swPath);
            exit;
        }
    }
    
    // Simple API endpoints simulation
    if (preg_match('/^\/api\/v1\/(.+)$/', $_SERVER['REQUEST_URI'], $matches)) {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit;
        }
        
        $endpoint = $matches[1];
        
        switch ($endpoint) {
            case 'tickets':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $input = json_decode(file_get_contents('php://input'), true);
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Ticket created successfully',
                        'data' => array_merge($input, ['id' => rand(1000, 9999)])
                    ]);
                } else {
                    echo json_encode([
                        'status' => 'success',
                        'data' => [
                            ['id' => 1, 'title' => 'Poste quebrado', 'status' => 'open', 'priority' => 'high'],
                            ['id' => 2, 'title' => 'Luz fraca', 'status' => 'in_progress', 'priority' => 'medium']
                        ]
                    ]);
                }
                break;
            case 'kpis':
                echo json_encode([
                    'status' => 'success',
                    'data' => [
                        'total_tickets' => 150,
                        'open_tickets' => 25,
                        'in_progress_tickets' => 18,
                        'closed_tickets' => 107,
                        'overdue_tickets' => 5
                    ]
                ]);
                break;
            default:
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Endpoint not found']);
        }
        exit;
    }
    
    // Default response
    header('Content-Type: text/html');
    echo '<h1>Ilumina System</h1><p>Install composer dependencies for full functionality. <a href="/health">Check API Health</a> | <a href="/">View Frontend</a></p>';
    exit;
}

// Normal bootstrap with dependencies
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// Initialize database
App\Config\Database::init();

// Create Container
$container = new Container();

// Set container to create App with on AppFactory
AppFactory::setContainer($container);
$app = AppFactory::create();

// Detect and set base path when app is served from a subdirectory (e.g., /ILUMINA/public)
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$detectedBasePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
if ($detectedBasePath && $detectedBasePath !== '/') {
    $app->setBasePath($detectedBasePath);
}

// Slim 4 requires the routing middleware
$app->addRoutingMiddleware();

// Add CORS middleware
$app->add(function ($request, $handler) {
    // Handle preflight requests
    if (strtoupper($request->getMethod()) === 'OPTIONS') {
        $response = new \Slim\Psr7\Response(204);
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
    }

    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// Add error middleware LAST to catch errors from previous middlewares/handlers
$app->addErrorMiddleware(true, true, true);

// Health check endpoint
$app->get('/health', function (Request $request, Response $response, $args) {
    $data = [
        'status' => 'ok',
        'timestamp' => date('Y-m-d H:i:s'),
        'service' => 'Ilumina API',
        'version' => '1.0.0'
    ];
    
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

// Serve frontend for root path
$app->get('/', function (Request $request, Response $response, $args) {
    $frontendPath = __DIR__ . '/../frontend/index.html';
    if (file_exists($frontendPath)) {
        $response->getBody()->write(file_get_contents($frontendPath));
        return $response->withHeader('Content-Type', 'text/html');
    }
    
    $response->getBody()->write('<h1>Ilumina API</h1><p>Frontend not found. Please check the frontend directory.</p>');
    return $response->withHeader('Content-Type', 'text/html');
});

// Serve static frontend assets
$app->get('/assets/{path:.*}', function (Request $request, Response $response, $args) {
    $filePath = __DIR__ . '/../frontend/assets/' . $args['path'];
    
    if (!file_exists($filePath)) {
        return $response->withStatus(404);
    }
    
    $extension = pathinfo($filePath, PATHINFO_EXTENSION);
    $contentType = 'text/plain';
    
    switch ($extension) {
        case 'css':
            $contentType = 'text/css';
            break;
        case 'js':
            $contentType = 'application/javascript';
            break;
        case 'png':
            $contentType = 'image/png';
            break;
        case 'jpg':
        case 'jpeg':
            $contentType = 'image/jpeg';
            break;
    }
    
    $response->getBody()->write(file_get_contents($filePath));
    return $response->withHeader('Content-Type', $contentType);
});

// Serve PWA manifest and service worker
$app->get('/manifest.json', function (Request $request, Response $response, $args) {
    $manifestPath = __DIR__ . '/../frontend/manifest.json';
    if (file_exists($manifestPath)) {
        $response->getBody()->write(file_get_contents($manifestPath));
        return $response->withHeader('Content-Type', 'application/json');
    }
    return $response->withStatus(404);
});

$app->get('/sw.js', function (Request $request, Response $response, $args) {
    $swPath = __DIR__ . '/../frontend/sw.js';
    if (file_exists($swPath)) {
        $response->getBody()->write(file_get_contents($swPath));
        return $response->withHeader('Content-Type', 'application/javascript');
    }
    return $response->withStatus(404);
});

// Load routes
require_once __DIR__ . '/../routes/api.php';

$app->run();