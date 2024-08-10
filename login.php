<?php
require_once __DIR__ . '/vendor/autoload.php';

// use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

session_start(); // Start or resume session

// Function to redirect to login page 
function redirectToLogin($error = "")
{
    if (!empty($error)) {
        header("Location: views/html/login.html?error=" . urlencode($error));
    } else {
        header("Location: views/html/login.html");
    }
    exit();
}

// Function to redirect to dashboard page
function redirectToDashboard()
{
    header("Location: books");
    exit();
}

// Function to check session and handle redirection
function checkSession()
{
    if (isset($_SESSION['userID']) && isset($_SESSION['userType']) && isset($_SESSION['loginTime'])) {
        $currentTime = time();
        $sessionTime = $_SESSION['loginTime'];
        $sessionDuration = 2 * 3600; // Two hours in seconds

        // If session duration exceeds two hours, destroy session and redirect to login page
        if (($currentTime - $sessionTime) > $sessionDuration) {
            session_destroy();
            redirectToLogin("Session expired. Please log in again.");
        }

        // Update login time
        $_SESSION['loginTime'] = $currentTime;

        // Redirect to dashboard
        redirectToDashboard();
    }
}

// Function to establish database connection
function getDbConnection()
{
    $servername = $_ENV['DB_HOST'];
    $username = $_ENV['DB_USERNAME'];
    $password = $_ENV['DB_PASSWORD'];
    $dbname = $_ENV['DB_NAME'];

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}

// Handle form submission
function login($conn)
{
    $email = $_POST["email"];
    $password = md5($_POST["password"]); // Hash password using MD5

    // Prepare SQL statement to retrieve user data
    $sql = "SELECT * FROM user WHERE Email=? AND Password=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // User found, set session variables
        $row = $result->fetch_assoc();
        $_SESSION["userID"] = $row["MemberID"];
        $_SESSION["userType"] = $row["MemberType"];
        $_SESSION["loginTime"] = time(); // Store login time

        // Redirect to dashboard
        redirectToDashboard();
    } else {
        // Invalid credentials, redirect to login page with error message
        redirectToLogin("Invalid email or password. Please try again.");
    }

    $stmt->close();
    // Close database connection
    $conn->close();
}

// Main execution starts here
checkSession();
$conn = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    login($conn);
} else {
    // Display login form if not a POST request (e.g., GET request)
    include 'views/html/login.html';
}
