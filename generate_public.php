
<?php
require_once 'includes/BookManager.php';
$manager = new BookManager();

$processedBooks = $manager->getProcessedBooks();
$unprocessedBooks = $manager->getUnprocessedBooks();
$generationTime = date('Y-m-d H:i:s');

$html = <<<HTML
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="generation-time" content="$generationTime">
    <title>Moja Biblioteka</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Moja Biblioteka</h1>
        
        <div class="row mt-4">
            <div class="col-12">
                <h2>Książki z metadanymi</h2>
                <div class="list-group">
HTML;

foreach ($processedBooks as $book) {
    $authors = implode(', ', array_map(function($author) {
        return $author['first_name'] . ' ' . $author['last_name'];
    }, $book['authors']));
    
    $html .= <<<HTML
    <div class="list-group-item">
        <h5 class="mb-1">{$book['title']}</h5>
        <p class="mb-1">Autorzy: {$authors}</p>
        <small>Plik: {$book['file_name']}</small>
    </div>
HTML;
}

$html .= <<<HTML
                </div>
            </div>
            
            <div class="col-12 mt-4">
                <h2>Książki bez metadanych</h2>
                <div class="list-group">
HTML;

foreach ($unprocessedBooks as $fileName) {
    $html .= <<<HTML
    <div class="list-group-item">
        <p class="mb-1">Plik: {$fileName}</p>
    </div>
HTML;
}

$html .= <<<HTML
                </div>
            </div>
        </div>
        
        <footer class="mt-5 text-muted">
            <p>Strona wygenerowana: $generationTime</p>
        </footer>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
HTML;

file_put_contents('index.html', $html);

header('Content-Type: application/json');
echo json_encode(['success' => true]);
