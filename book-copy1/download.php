<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if file ID is valid
if (!isset($_GET['file']) || !is_numeric($_GET['file'])) {
    header('HTTP/1.1 400 Bad Request');
    echo "Invalid file ID.";
    exit();
}

$file_id = intval($_GET['file']);

// Fetch file details from the database
$stmt = $pdo->prepare('SELECT file_path FROM books WHERE id = ?');
$stmt->execute([$file_id]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    header('HTTP/1.1 404 Not Found');
    echo "File not found.";
    exit();
}

$file_path = $file['file_path'];

// Ensure the file path is correct
$upload_dir = __DIR__ . '/uploads/'; // Ensure this matches your upload directory
$full_path = $upload_dir . basename($file_path);

// Check if the file exists
if (!file_exists($full_path)) {
    header('HTTP/1.1 404 Not Found');
    echo "File does not exist.";
    exit();
}

// Set headers for file download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($full_path) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($full_path));

// Read and output the file
readfile($full_path);
exit();
?>
