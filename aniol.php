
<?php
session_start();
require_once 'includes/BookManager.php';
require_once 'includes/functions.php';

try {
    $manager = new BookManager();
    $stats = $manager->getStats();
} catch (Exception $e) {
    die('<div class="alert alert-danger m-4">System error: Unable to initialize book manager. Please contact administrator.</div>');
}

// Verify template files
$required_templates = [
    'templates/header.php',
    'templates/alerts.php',
    'templates/tables.php',
    'templates/modals.php'
];

foreach ($required_templates as $template) {
    if (!file_exists($template)) {
        die('<div class="alert alert-danger m-4">System error: Missing required template file: ' . htmlspecialchars($template) . '</div>');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Book Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .modal-dialog { max-width: 700px; }
    </style>
</head>
<body>
    <?php 
    try {
        include 'templates/header.php';
        echo '<div class="container mt-4">';
        include 'templates/alerts.php';
        include 'templates/tables.php';
        echo '</div>';
        include 'templates/modals.php';
    } catch (Exception $e) {
        echo '<div class="alert alert-danger m-4">Critical error: Unable to load template files. Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/bookEditor.js"></script>
</body>
</html>
