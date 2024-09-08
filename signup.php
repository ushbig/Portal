<?php
// Function to view signup page
function view_signup() {
    include "views/html/signup.html"; // Serve HTML page
}

// Function to redirect to login page 
function redirectToLogin($message = "")
{
        header("Location: login?success=" . urlencode($message));
    
    exit();
}

// Function to handle form submission
function signup($conn) {
    // Check if form fields are set
    if(isset($_POST["firstName"], $_POST["lastName"], $_POST["email"], $_POST["password"])) {
        $firstName = $_POST["firstName"];
        $lastName = $_POST["lastName"];
        $email = $_POST["email"];
        $password = md5($_POST["password"]); // Hash password using MD5
        // Set member type
        $memberType = "Member";
        
        // Prepare and bind SQL statement
        $stmt = $conn->prepare("INSERT INTO user (memberType, firstName, lastName, email, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $memberType, $firstName, $lastName, $email, $password);
        
        // Execute SQL statement
        if ($stmt->execute() === TRUE) {
            redirectToLogin("User Created Successfully");
        } else {
            echo "Error: " . $stmt->error;
        }
        
        // Close statement
        $stmt->close();
    } else {
        echo "Form fields are not set";
    }
}

// Load environment variables
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$servername = $_ENV['DB_HOST'];
$username = $_ENV['DB_USERNAME'];
$password = $_ENV['DB_PASSWORD'];
$dbname = $_ENV['DB_NAME'];

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission if it's a POST request
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    signup($conn);
}

// Close database connection
$conn->close();
?>
