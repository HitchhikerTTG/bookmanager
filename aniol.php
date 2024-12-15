
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
    <?php include 'templates/header.php'; ?>
    
    <div class="container mt-4">
        <?php include 'templates/alerts.php'; ?>
        <?php include 'templates/tables.php'; ?>
    </div>

    <?php include 'templates/modals.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/bookEditor.js"></script>
</body>
</html>
