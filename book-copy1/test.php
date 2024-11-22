<?php
$file = 'uploads/cd.pdf';
if (file_exists($file)) {
    echo "File exists.";
} else {
    echo "File does not exist.";
}
?>