
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
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="generator" content="Book Manager">
    <meta name="last-generated" content="' . date('Y-m-d H:i:s') . '">
    <title>Biblioteka E-booków</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Biblioteka E-booków</h1>
        
        <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="library-tab" data-bs-toggle="tab" data-bs-target="#library" type="button" role="tab">
                    Moja biblioteka (' . $processedBooksCount . ')
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="preparation-tab" data-bs-toggle="tab" data-bs-target="#preparation" type="button" role="tab">
                    Książki w przygotowaniu (' . $unprocessedBooksCount . ')
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="library" role="tabpanel">
                <div class="row">
                    ' . $this->generateBookCards(array_filter($books, function($book) {
                        return !empty($book['title']) && !empty($book['authors']);
                    })) . '
                </div>
            </div>
            <div class="tab-pane fade" id="preparation" role="tabpanel">
                <div class="row">
                    ' . $this->generateBookCards(array_filter($books, function($book) {
                        return empty($book['title']) || empty($book['authors']);
                    })) . '
                </div>
            </div>
        </div>
    </div>
    
    <footer class="footer mt-5 py-3 bg-light">
        <div class="container text-center">
            <small class="text-muted">Strona wygenerowana: ' . date('Y-m-d H:i:s') . '</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';

file_put_contents('index.html', $html);

header('Content-Type: application/json');
echo json_encode(['success' => true]);

private function generateBookCards($books) {
    $html = '';
    foreach ($books as $book) {
        $html .= '<div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">' . htmlspecialchars($book['title'] ?? 'Bez tytułu') . '</h5>
                    <p class="card-text">
                        <strong>Autorzy:</strong> ' . 
                        (!empty($book['authors']) ? 
                            implode(', ', array_map(function($author) {
                                return htmlspecialchars($author['first_name'] . ' ' . $author['last_name']);
                            }, $book['authors'])) : 
                            'Nieznany'
                        ) . '<br>
                        <strong>Gatunki:</strong> ' . htmlspecialchars(implode(', ', $book['genres'])) . '
                    </p>
                    ' . (!empty($book['series']) ? '<p class="card-text"><small class="text-muted">Seria: ' . htmlspecialchars($book['series']) . ' #' . htmlspecialchars($book['series_position']) . '</small></p>' : '') . '
                    <a href="_ksiazki/' . htmlspecialchars($book['file_name']) . '" class="btn btn-primary">Pobierz</a>
                </div>
            </div>
        </div>';
    }
    return $html;
}
