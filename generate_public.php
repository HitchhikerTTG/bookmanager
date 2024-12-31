
<?php
require_once 'includes/BookManager.php';
$manager = new BookManager();
$books = $manager->getProcessedBooks();

$processedBooksCount = count(array_filter($books, function($book) {
    return !empty($book['title']) && !empty($book['authors']);
}));

$unprocessedBooksCount = count(array_filter($books, function($book) {
    return empty($book['title']) || empty($book['authors']);
}));

$html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="generator" content="Book Manager">
    <meta name="last-generated" content="' . date('Y-m-d H:i:s') . '">
    <title>E-Book Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>E-Book Library</h1>
        
        <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="library-tab" data-bs-toggle="tab" data-bs-target="#library" type="button">
                    My Library (' . $processedBooksCount . ')
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="preparation-tab" data-bs-toggle="tab" data-bs-target="#preparation" type="button">
                    Books in Preparation (' . $unprocessedBooksCount . ')
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="library" role="tabpanel">
                <div class="alert alert-info">
                    Number of books with title and author: ' . $processedBooksCount . '
                </div>
            </div>
            <div class="tab-pane fade" id="preparation" role="tabpanel">
                <div class="alert alert-warning">
                    Number of books without title or author: ' . $unprocessedBooksCount . '
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';

file_put_contents('index.html', $html);

header('Content-Type: application/json');
echo json_encode(['success' => true]);
