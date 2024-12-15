
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
    <script>
        let editModal = null;

        function initializeModal() {
            const modalElement = document.getElementById('editBookModal');
            if (modalElement) {
                editModal = new bootstrap.Modal(modalElement);
            }
        }

        function editBook(fileName) {
            if (!editModal) {
                initializeModal();
            }
            
            document.getElementById('edit_file_name').value = fileName;
            document.getElementById('edit_title').value = '';
            
            // Clear existing authors
            const authorsContainer = document.getElementById('authors-container');
            authorsContainer.innerHTML = '';
            
            // Add first author
            addAuthorEntry('', '');
            
            document.getElementById('edit_genres').value = '';
            document.getElementById('edit_series').value = '';
            document.getElementById('edit_series_position').value = '';
            
            editModal.show();
        }

        function handleAuthorSelect(select) {
            const container = select.closest('.author-entry');
            const [firstName, lastName] = select.value ? select.value.split('|') : ['', ''];
            const inputs = container.querySelector('.author-inputs');
            inputs.querySelector('input[name$="[first_name]"]').value = firstName;
            inputs.querySelector('input[name$="[last_name]"]').value = lastName;
        }

        function addAuthorEntry(firstName = '', lastName = '') {
            const container = document.getElementById('authors-container');
            const index = container.children.length;
            const authorEntry = document.createElement('div');
            authorEntry.className = 'author-entry mb-2';
            authorEntry.innerHTML = `
                <div class="author-inputs">
                    <input type="text" class="form-control mb-2" name="authors[${index}][first_name]" value="${firstName}" placeholder="First Name" required>
                    <input type="text" class="form-control" name="authors[${index}][last_name]" value="${lastName}" placeholder="Last Name" required>
                </div>
                ${index > 0 ? '<button type="button" class="btn btn-danger btn-sm mt-1" onclick="this.parentElement.remove()">Remove</button>' : ''}
            `;
            container.appendChild(authorEntry);
        }

        function addAuthor() {
            addAuthorEntry();
        }

        document.addEventListener('DOMContentLoaded', initializeModal);
    </script>
</body>
</html>
