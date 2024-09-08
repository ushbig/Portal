<?php
// create_library_db.php
require 'db.php';

try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS lmslibrary");
    $pdo->exec("USE lmslibrary");

    $sql = "
    CREATE TABLE IF NOT EXISTS User (
        MemberID INT AUTO_INCREMENT PRIMARY KEY,
        MemberType ENUM('Member', 'Admin') DEFAULT 'Member',
        FirstName VARCHAR(20),
        LastName VARCHAR(20),
        Email VARCHAR(50),
        Password CHAR(32)
    );
    
    CREATE TABLE IF NOT EXISTS Book (
        BookID INT AUTO_INCREMENT PRIMARY KEY,
        BookTitle VARCHAR(30),
        Author VARCHAR(30),
        Publisher VARCHAR(30),
        Language ENUM('English', 'French', 'German', 'Mandarin', 'Japanese', 'Russian', 'Other') DEFAULT 'English',
        Category ENUM('Fiction', 'Nonfiction', 'Reference') DEFAULT 'Fiction',
        BookCoverURL VARCHAR(255)
    );
    
    CREATE TABLE IF NOT EXISTS BookStatus (
        BookID INT,
        MemberID INT,
        Status ENUM('Available', 'Onloan', 'Deleted') DEFAULT 'Available',
        AppliedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (BookID) REFERENCES Book(BookID),
        FOREIGN KEY (MemberID) REFERENCES User(MemberID)
    );
    ";

    $pdo->exec($sql);
    echo "Database and tables created successfully.";
} catch (\PDOException $e) {
    echo "Error creating database and tables: " . $e->getMessage();
}
?>
