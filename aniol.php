
<?php
session_start();
require_once 'includes/BookManager.php';
require_once 'includes/functions.php';

function debug_log($message) {
    echo "<div style='background: #f8f9fa; border: 1px solid #ddd; margin: 2px; padding: 5px;'>DEBUG: " . htmlspecialchars($message) . "</div>";
}

debug_log("Script started");

try {
    debug_log("Initializing BookManager");
    $manager = new BookManager();
    debug_log("BookManager initialized successfully");
    
    debug_log("Getting stats");
    $stats = $manager->getStats();
    debug_log("Stats retrieved successfully");
} catch (Exception $e) {
    debug_log("ERROR: " . $e->getMessage());
    die('<div class="alert alert-danger m-4">System error: Unable to initialize book manager. Please contact administrator.</div>');
}

debug_log("Checking template files");
$required_templates = [
    'templates/header.php',
    'templates/alerts.php',
    'templates/tables.php',
    'templates/modals.php'
];

foreach ($required_templates as $template) {
    debug_log("Checking template: " . $template);
    if (!file_exists($template)) {
        debug_log("ERROR: Template missing: " . $template);
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
        debug_log("Including header.php");
        include 'templates/header.php';
        
        debug_log("Including main container");
        echo '<div class="container mt-4">';
        
        debug_log("Including alerts.php");
        include 'templates/alerts.php';
        
        debug_log("Including tables.php");
        include 'templates/tables.php';
        
        echo '</div>';
        
        debug_log("Including modals.php");
        include 'templates/modals.php';
    } catch (Exception $e) {
        debug_log("ERROR: Template include failed: " . $e->getMessage());
        echo '<div class="alert alert-danger m-4">Critical error: Unable to load template files. Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/bookEditor.js"></script>
</body>
</html>
