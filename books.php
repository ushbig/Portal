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

// Connect to database
function getDbConnection()
{
    $servername = $_ENV['DB_HOST'];
    $username = $_ENV['DB_USERNAME'];
    $password = $_ENV['DB_PASSWORD'];
    $dbname = $_ENV['DB_NAME'];

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
    }

    return $conn;
}

// Fetch all books
function index()
{
    $conn = getDbConnection();
    $userid = $_SESSION['userID'];

    // SQL query to get the latest status for each book
    $sql = "
        SELECT 
            b.BookID, 
            b.BookTitle, 
            b.Author, 
            b.Publisher, 
            b.Language, 
            b.Category, 
            bs.Status, 
            bs.AppliedDate, 
            us.MemberType
        FROM 
            Book b
        LEFT JOIN 
            (
                SELECT 
                    bs1.BookID, 
                    bs1.Status, 
                    bs1.AppliedDate 
                FROM 
                    BookStatus bs1
                INNER JOIN 
                    (
                        SELECT 
                            BookID, 
                            MAX(AppliedDate) AS MaxAppliedDate 
                        FROM 
                            BookStatus 
                        GROUP BY 
                            BookID
                    ) bs2 
                ON 
                    bs1.BookID = bs2.BookID 
                    AND bs1.AppliedDate = bs2.MaxAppliedDate
            ) bs 
        ON 
            b.BookID = bs.BookID
        LEFT JOIN 
            User us 
        ON 
            us.MemberID = $userid
        ORDER BY 
            bs.AppliedDate DESC, b.BookID
    ";

    $result = $conn->query($sql);

    $books = [];
    $seenBooks = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if (!in_array($row['BookID'], $seenBooks)) {
                $books[] = $row;
                $seenBooks[] = $row['BookID'];
            }
        }
    }
    echo json_encode($books);
    $conn->close();
}




// Fetch available books
function available_books()
{
    $conn = getDbConnection();

    $sql = "SELECT b.*, bs.Status 
            FROM Book b
            JOIN BookStatus bs ON b.BookID = bs.BookID
            WHERE bs.Status = 'Available'";
    $result = $conn->query($sql);

    $books = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
    }

    echo json_encode($books);
    $conn->close();
}

// Borrow a book
function borrowBook()
{
    session_start();

    $conn = getDbConnection();
    if (!$conn) {
        echo json_encode(['error' => 'Database connection failed']);
        return;
    }

    // Check if bookId is set in POST request and sanitize it
    if (!isset($_POST['bookId'])) {
        echo json_encode(['error' => 'Invalid request']);
        return;
    }

    $bookId = intval($_POST['bookId']);
    $memberId = intval($_SESSION['userID']);

    // Check if the book is available
    $sql = "SELECT Status FROM BookStatus WHERE BookID = ? AND Status = 'Available'";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['error' => 'Failed to prepare statement']);
        return;
    }
    $stmt->bind_param("i", $bookId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Book is available, proceed with borrowing
        $updateSql = "UPDATE BookStatus SET Status = 'Onloan', MemberID = ? WHERE BookID = ?";
        $updateStmt = $conn->prepare($updateSql);
        if (!$updateStmt) {
            echo json_encode(['error' => 'Failed to prepare update statement']);
            return;
        }
        $updateStmt->bind_param("ii", $memberId, $bookId);

        if ($updateStmt->execute()) {
            echo json_encode(['success' => 'Book borrowed successfully']);
        } else {
            echo json_encode(['error' => 'Failed to borrow book']);
        }

        $updateStmt->close();
    } else {
        echo json_encode(['error' => 'Book is not available for borrowing']);
    }

    $stmt->close();
    $conn->close();
}

// Add or edit a book
function saveBook()
{
    session_start();
    if ($_SESSION["userType"] !== 'Admin') {
        echo json_encode(['error' => 'You are not allowed to perform this operation']);
        return;
    }

    $conn = getDbConnection();
    if (!$conn) {
        echo json_encode(['error' => 'Database connection failed']);
        return;
    }

    $bookId = isset($_POST['book_id']) ? intval($_POST['book_id']) : null;
    $bookTitle = $_POST['BookTitle'];
    $author = $_POST['Author'];
    $publisher = $_POST['Publisher'];
    $category = $_POST['Category'];
    $status = $_POST['Status'];
    $memberId = intval($_SESSION['userID']);

    if ($bookId) {
        // Update existing book
        $sql = "UPDATE Book SET BookTitle = ?, Author = ?, Publisher = ?, Category = ? WHERE BookID = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['error' => 'Failed to prepare update statement']);
            return;
        }
        $stmt->bind_param("ssssi", $bookTitle, $author, $publisher, $category, $bookId);
    } else {
        // Insert new book
        $sql = "INSERT INTO Book (BookTitle, Author, Publisher, Category) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['error' => 'Failed to prepare insert statement']);
            return;
        }
        $stmt->bind_param("ssss", $bookTitle, $author, $publisher, $category);
    }

    if ($stmt->execute()) {
        if (!$bookId) {
            $bookId = $stmt->insert_id; // Get the ID of the newly inserted book
        }

        // Check if the status record exists
        $checkStatusSql = "SELECT * FROM BookStatus WHERE BookID = ? AND MemberID = ?";
        $checkStatusStmt = $conn->prepare($checkStatusSql);
        if (!$checkStatusStmt) {
            echo json_encode(['error' => 'Failed to prepare check status statement']);
            return;
        }
        $checkStatusStmt->bind_param("ii", $bookId, $memberId);
        $checkStatusStmt->execute();
        $checkStatusResult = $checkStatusStmt->get_result();

        if ($checkStatusResult->num_rows > 0) {
            // Update existing status
            $updateStatusSql = "UPDATE BookStatus SET Status = ?, AppliedDate = NOW() WHERE BookID = ? AND MemberID = ?";
            $updateStatusStmt = $conn->prepare($updateStatusSql);
            if (!$updateStatusStmt) {
                echo json_encode(['error' => 'Failed to prepare update status statement']);
                return;
            }
            $updateStatusStmt->bind_param("sii", $status, $bookId, $memberId);
        } else {
            // Insert new status
            $insertStatusSql = "INSERT INTO BookStatus (BookID, MemberID, Status, AppliedDate) VALUES (?, ?, ?, NOW())";
            $insertStatusStmt = $conn->prepare($insertStatusSql);
            if (!$insertStatusStmt) {
                echo json_encode(['error' => 'Failed to prepare insert status statement']);
                return;
            }
            $insertStatusStmt->bind_param("iis", $bookId, $memberId, $status);
        }

        if (isset($updateStatusStmt) && $updateStatusStmt->execute() || isset($insertStatusStmt) && $insertStatusStmt->execute()) {
            echo json_encode(['success' => 'Book saved successfully']);
        } else {
            echo json_encode(['error' => 'Failed to save book status']);
        }

        if (isset($updateStatusStmt)) {
            $updateStatusStmt->close();
        }
        if (isset($insertStatusStmt)) {
            $insertStatusStmt->close();
        }

        $checkStatusStmt->close();
    } else {
        echo json_encode(['error' => 'Failed to save book']);
    }

    $stmt->close();
    $conn->close();
}



// Determine which function to call based on the request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'borrowBook':
                borrowBook();
                break;
            case 'saveBook':
                saveBook();
                break;
        }
    }

}

function viewBooks()
{
    include 'views/html/books.html';
}

// Delete a book
// function deleteBook()
// {
//     session_start();
//     if ($_SESSION["userType"] !== 'Admin') {
//         echo json_encode(['error' => 'You are not allowed to perform this operation']);
//         return;
//     }

//     $conn = getDbConnection();
//     $bookId = $_POST['book_id'];

//     $statusSql = "DELETE FROM BookStatus WHERE BookID = ?";
//     $statusStmt = $conn->prepare($statusSql);
//     $statusStmt->bind_param("i", $bookId);

//     if ($statusStmt->execute()) {
//         $sql = "DELETE FROM Book WHERE BookID = ?";
//         $stmt = $conn->prepare($sql);
//         $stmt->bind_param("i", $bookId);

//         if ($stmt->execute()) {
//             echo json_encode(['success' => 'Book deleted successfully']);
//         } else {
//             echo json_encode(['error' => 'Failed to delete book']);
//         }

//         $stmt->close();
//     } else {
//         echo json_encode(['error' => 'Failed to delete book status']);
//     }

//     $statusStmt->close();
//     $conn->close();
// }

function viewBooksOnLoan()
{
    session_start();


    $conn = getDbConnection();
    if (!$conn) {
        echo json_encode(['error' => 'Database connection failed']);
        return;
    }

    // Get the user ID from the session
    if (!isset($_SESSION['userID'])) {
        echo json_encode(['error' => 'User not logged in']);
        return;
    }

    $memberId = intval($_SESSION['userID']);

    // Query to get distinct books on loan for the user
    $sql = "SELECT DISTINCT b.BookID, b.BookTitle, b.Author, b.Category, b.Language, bs.Status
            FROM Book b
            JOIN BookStatus bs ON b.BookID = bs.BookID
            WHERE bs.MemberID = ? AND bs.Status = 'Onloan'";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['error' => 'Failed to prepare statement']);
        return;
    }
    $stmt->bind_param("i", $memberId);
    $stmt->execute();
    $result = $stmt->get_result();

    $books = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
    }

    echo json_encode($books);
    $stmt->close();
    $conn->close();
}

function returnBook()
{
    session_start();


    $conn = getDbConnection();
    if (!$conn) {
        echo json_encode(['error' => 'Database connection failed']);
        return;
    }

    // Check if bookId is set in POST request and sanitize it
    if (!isset($_POST['bookId'])) {
        echo json_encode(['error' => 'Invalid request']);
        return;
    }

    $bookId = intval($_POST['bookId']);
    $memberId = intval($_SESSION['userID']);

    // Check if the book is currently on loan by this member
    $sql = "SELECT Status FROM BookStatus WHERE BookID = ? AND MemberID = ? AND Status = 'Onloan'";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['error' => 'Failed to prepare statement']);
        return;
    }
    $stmt->bind_param("ii", $bookId, $memberId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Book is on loan, proceed with returning
        $updateSql = "UPDATE BookStatus SET Status = 'Available', MemberID = NULL WHERE BookID = ? AND MemberID = ?";
        $updateStmt = $conn->prepare($updateSql);
        if (!$updateStmt) {
            echo json_encode(['error' => 'Failed to prepare update statement']);
            return;
        }
        $updateStmt->bind_param("ii", $bookId, $memberId);

        if ($updateStmt->execute()) {
            echo json_encode(['success' => 'Book returned successfully']);
        } else {
            echo json_encode(['error' => 'Failed to return book']);
        }

        $updateStmt->close();
    } else {
        echo json_encode(['error' => 'Book is not on loan or does not belong to this user']);
    }

    $stmt->close();
    $conn->close();
}




