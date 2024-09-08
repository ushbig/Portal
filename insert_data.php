<?php
require 'db.php';

// Number of records to be inserted into the db 
$numUsers = 10;
$numBookStatus = 5;
$pdo->exec("USE $db");
// Random data to tgenerate
function getRandomFirstName() {
    $firstNames = ['John', 'Jane', 'Alex', 'Emily', 'Chris', 'Katie', 'Tom', 'Laura', 'David', 'Sarah'];
    return $firstNames[array_rand($firstNames)];
}

function getRandomLastName() {
    $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez'];
    return $lastNames[array_rand($lastNames)];
}

function getRandomEmail($firstName, $lastName) {
    $domains = ['example.com', 'test.com', 'sample.org', 'mail.com'];
    return strtolower($firstName . '.' . $lastName . '@' . $domains[array_rand($domains)]);
}


 function getBooks() {
    $books = [
        [
            "title"=> "great expectations",
            "author"=> "charles dickens",
            "publisher"=> "macmillan collectors library",
            "language"=>"English",
            "category"=> "Fiction",
            "bookCoverURL" => "storage\imgs\book_1.png"
        ],
        [
            "title" => "an inconvenient truth",
            "author" => "al gore",
            "publisher" => "penguin books",
            "language" => "English",
            "category" => "Nonfiction",
            "bookCoverURL" => "storage\imgs\book_2.png"
        ],
        [
            "title" => "oxford dictionary",
            "author" => "oxford press",
            "publisher" => "oxford press",
            "language" => "English",
            "category" => "Reference",
            "bookCoverURL" => "storage\imgs\book_3.png"
        ],
        [
            "title" => "anna karenina",
            "author" => "leo tolstoy",
            "publisher" => "kinokuniya",
            "language" => "Russian",
            "category" => "Fiction",
            "bookCoverURL" => "storage\imgs\book_4.png"
        ],
        [
            "title" => "the tale of genji",
            "author" => "murasaki shikibu",
            "publisher" => "kinokuniya",
            "language" => "Japanese",
            "category" => "Fiction",
            "bookCoverURL"=> "storage\imgs\book_5.png"
        ]
    ];
    return $books;
}


function getRandomStatus() {
    $statuses = ['Available', 'Onloan', 'Deleted'];
    return $statuses[array_rand($statuses)];
}

function getRandomMemberID($numUsers) {
    return rand(1, $numUsers);
}

function getRandomBookID($numBooks) {
    return rand(1, $numBooks);
}

// insert data users
for ($i = 0; $i < $numUsers; $i++) {
    $firstName = getRandomFirstName();
    $lastName = getRandomLastName();
    $email = getRandomEmail($firstName, $lastName);
    $password = md5('password'); 

    try {
        $sql = "INSERT INTO User (FirstName, LastName, Email, Password) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$firstName, $lastName, $email, $password]);
    } catch (PDOException $e) {
        echo "Error inserting user: " . $e->getMessage() . "\n";
    }
}

// insert data books
foreach(getBooks() as $book) {

    try {
        $sql = "INSERT INTO Book (BookTitle, Author, Publisher, Language, Category,BookCoverURL) VALUES (?, ?, ?, ?, ?,?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$book['title'], $book['author'], $book['publisher'], $book['language'], $book['category'], $book['bookCoverURL']]);
    } catch (PDOException $e) {
        echo "Error inserting book: " . $e->getMessage() . "\n";
    }
}

//insert status 
$numBooks = $pdo->query("SELECT COUNT(*) FROM Book")->fetchColumn();
$numUsers = $pdo->query("SELECT COUNT(*) FROM User")->fetchColumn();

for ($i = 0; $i < $numBookStatus; $i++) {
    $bookID = getRandomBookID($numBooks);
    $memberID = getRandomMemberID($numUsers);
    $status = getRandomStatus();
    $appliedDate = date('Y-m-d H:i:s', strtotime('-' . rand(0, 365) . ' days'));

    // Validate BookID and MemberID
    if ($bookID > 0 && $bookID <= $numBooks && $memberID > 0 && $memberID <= $numUsers) {
        try {
            $sql = "INSERT INTO BookStatus (BookID, MemberID, Status, AppliedDate) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$bookID, $memberID, $status, $appliedDate]);
        } catch (PDOException $e) {
            echo "Error inserting book status: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Invalid BookID or MemberID: BookID=$bookID, MemberID=$memberID\n";
    }
}

echo "Random data insertion completed.";
?>
