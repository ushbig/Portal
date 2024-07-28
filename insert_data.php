<?php
require 'db.php';

// Number of records to be inserted into the db 
$numUsers = 20;
$numBooks = 20;
$numBookStatus = 15;
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

function getRandomBookTitle() {
    $titles = ['The Great Adventure', 'Mystery of the Lost Island', 'Journey to the Unknown', 'Secrets of the Ancient', 'The Final Chapter'];
    return $titles[array_rand($titles)];
}

function getRandomAuthor() {
    $authors = ['Author One', 'Author Two', 'Author Three', 'Author Four', 'Author Five'];
    return $authors[array_rand($authors)];
}

function getRandomPublisher() {
    $publishers = ['Publisher A', 'Publisher B', 'Publisher C', 'Publisher D', 'Publisher E'];
    return $publishers[array_rand($publishers)];
}

function getRandomCategory() {
    $categories = ['Fiction', 'Nonfiction', 'Reference'];
    return $categories[array_rand($categories)];
}

function getRandomLanguage() {
    $languages = ['English', 'French', 'German', 'Mandarin', 'Japanese', 'Russian', 'Other'];
    return $languages[array_rand($languages)];
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
for ($i = 0; $i < $numBooks; $i++) {
    $bookTitle = getRandomBookTitle();
    $author = getRandomAuthor();
    $publisher = getRandomPublisher();
    $language = getRandomLanguage();
    $category = getRandomCategory();

    try {
        $sql = "INSERT INTO Book (BookTitle, Author, Publisher, Language, Category) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$bookTitle, $author, $publisher, $language, $category]);
    } catch (PDOException $e) {
        echo "Error inserting book: " . $e->getMessage() . "\n";
    }
}

// insert data book statuses
for ($i = 0; $i < $numBookStatus; $i++) {
    $bookID = getRandomBookID($numBooks);
    $memberID = getRandomMemberID($numUsers);
    $status = getRandomStatus();
    $appliedDate = date('Y-m-d H:i:s', strtotime('-' . rand(0, 365) . ' days'));

    try {
        $sql = "INSERT INTO BookStatus (BookID, MemberID, Status, AppliedDate) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$bookID, $memberID, $status, $appliedDate]);
    } catch (PDOException $e) {
        echo "Error inserting book status: " . $e->getMessage() . "\n";
    }
}

echo "Random data insertion completed.";
?>
