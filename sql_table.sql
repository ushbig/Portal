CREATE DATABASE library;

USE library;

CREATE TABLE User (
    MemberID INT AUTO_INCREMENT PRIMARY KEY,
    MemberType ENUM('Member', 'Admin') DEFAULT 'Member',
    FirstName VARCHAR(20),
    LastName VARCHAR(20),
    Email VARCHAR(50),
    Password CHAR(32)
);

CREATE TABLE Book (
    BookID INT AUTO_INCREMENT PRIMARY KEY,
    BookTitle VARCHAR(30),
    Author VARCHAR(30),
    Publisher VARCHAR(30),
    Language ENUM('English', 'French', 'German', 'Mandarin', 'Japanese', 'Russian', 'Other') DEFAULT 'English',
    Category ENUM('Fiction', 'Nonfiction', 'Reference') DEFAULT 'Fiction'
);

CREATE TABLE BookStatus (
    BookID INT,
    MemberID INT,
    Status ENUM('Available', 'Onloan', 'Deleted') DEFAULT 'Available',
    AppliedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (BookID) REFERENCES Book(BookID),
    FOREIGN KEY (MemberID) REFERENCES User(MemberID)
);
