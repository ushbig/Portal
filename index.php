<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$viewDir = '/';

// Define routes
$routes = [
    '/' => ['file' => 'signup.php', 'method' => 'view_signup', 'access' => 'GET'],
    '/signup' => ['file' => 'signup.php', 'method' => 'signup', 'access' => 'POST'],
    '/login' => ['file' => 'login.php', 'method' => 'viewLogin', 'access' => 'GET'],
    '/signin' => ['file' => 'login.php', 'method' => 'login', 'access' => 'POST'],
    '/books' => ['file' => 'books.php', 'method' => 'viewBooks', 'access' => 'GET'],
    '/book' => ['file' => 'books.php', 'method' => 'index', 'access' => 'GET'],
    '/books/available' => ['file' => 'books.php', 'method' => 'available_books', 'access' => 'GET'],
    '/books/update' => ['file' => 'books.php', 'method' => 'saveBook', 'access' => 'POST'],
    '/books/delete' => ['file' => 'books.php', 'method' => 'deleteBook', 'access' => 'POST'],
    '/borrow' => ['file' => 'books.php', 'method' => 'borrowBook', 'access' => 'POST'],
    '/books/borrow' => ['file' => 'views/html/available_books.html', 'method' => null, 'access' => 'GET'],
    '/books/return' => ['file' => 'views/html/Onloan.html', 'method' => null, 'access' => 'GET'],
    '/books/onloan' => ['file' => 'books.php', 'method' => 'viewBooksOnLoan', 'access' => 'GET'],
    '/return' => ['file' => 'books.php', 'method' => 'returnBook', 'access' => 'POST'],
    '/top-borrows' => ['file' => 'fetch_top_borrowed_books.php', 'method' => 'topBorrow', 'access' => 'GET'],
    '/profile' => ['file' => 'views/html/user_details.html', 'method' => null, 'access' => 'GET'],
    '/user' => ['file' => 'user.php', 'method' => 'userDetails', 'access' => 'GET'],
    '/user/update' => ['file' => 'user.php', 'method' => 'updateDetails', 'access' => 'POST'],
    '/signout' => ['file' => 'logout.php', 'method' => null, 'access' => 'GET'],
];

// Check if the requested route exists
if (array_key_exists($request, $routes)) {
    // Get the route information
    $route = $routes[$request];
    
    // Check if the request method matches the allowed access method
    if ($_SERVER['REQUEST_METHOD'] !== $route['access']) {
        http_response_code(405);
        echo '405 - Method Not Allowed';
        exit();
    }
    
    // Include the corresponding file
    require __DIR__ . $viewDir . $route['file'];
    
    // Call the method if it exists
    if ($route['method'] && function_exists($route['method'])) {
        call_user_func($route['method']);
    }
} else {
    // Handle 404 error
    http_response_code(404);
    echo '404 - Not Found';
}
?>
