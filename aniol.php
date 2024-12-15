
<?php
session_start();

function debug_log($message) {
    echo "<div style='background: #f8f9fa; border: 1px solid #ddd; margin: 2px; padding: 5px;'>DEBUG: " . htmlspecialchars($message) . "</div>";
}

debug_log("Script started");

require_once 'includes/BookManager.php';
$manager = new BookManager();
?>
<!DOCTYPE html>

    <style>
        .bootstrap-tagsinput {
            width: 100%;
            padding: 8px;
            border-radius: 4px;
        }
        .bootstrap-tagsinput .tag {
            margin-right: 4px;
            padding: 4px 8px;
            color: white !important;
            background-color: #0d6efd;
            border-radius: 3px;
            display: inline-block;
        }
        .tt-menu {
            width: 100%;
            padding: 8px;
            background: white;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .tt-suggestion {
            padding: 4px 8px;
            cursor: pointer;
        }
        .tt-suggestion:hover {
            background-color: #f8f9fa;
        }
    </style>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Book Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/typeahead.js-bootstrap-css/1.2.1/typeaheadjs.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/typeahead.js/0.11.1/typeahead.bundle.min.js"></script>
    <style>
    .bootstrap-tagsinput {
        width: 100%;
    }
    .bootstrap-tagsinput .tag {
        margin-right: 2px;
        color: white;
        background-color: #0d6efd;
        padding: 0.2rem 0.6rem;
        border-radius: 0.25rem;
    }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>Book Manager</h1>
        <?php include 'templates/library.php'; ?>
    </div>
    
    <?php include 'templates/modals.php'; ?>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/typeahead.js/0.11.1/typeahead.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.min.js"></script>
    <script src="js/bookEditor.js"></script>
</body>
</html>
