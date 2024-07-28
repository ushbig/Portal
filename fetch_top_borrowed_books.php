<?php
session_start(); // Start or resume session

// Check if user is logged in
if (!isset($_SESSION['userID'])) {
    // Redirect to login page if not logged in
    header("Location: /login");
    exit();
}
// Load environment variables
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
// Database connection
$servername = $_ENV['DB_HOST'];
$username = $_ENV['DB_USERNAME'];
$password = $_ENV['DB_PASSWORD'];
$dbname = $_ENV['DB_NAME'];

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Fetch top borrowed books from the database
$sql =  "SELECT *, COUNT(*) AS BorrowCount 
            FROM BookStatus 
            JOIN Book ON BookStatus.BookID = Book.BookID 
            GROUP BY BookStatus.BookID 
            ORDER BY BorrowCount DESC ";
$result = $conn->query($sql);
$books = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($books);

$conn->close();

function topBorrow(){
    include 'views/html/fetch_top_borrowed_books.html';
}
?>
