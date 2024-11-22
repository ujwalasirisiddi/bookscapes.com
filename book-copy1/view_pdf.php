<?php
require 'db.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    try {
        $stmt = $pdo->prepare('SELECT file_data, file_name FROM uploaded_books WHERE id = ?');
        $stmt->execute([$id]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($book) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $book['file_name'] . '"');
            echo $book['file_data'];
            exit();
        } else {
            echo "Book not found.";
        }
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
    }
} else {
    echo "No book ID specified.";
}