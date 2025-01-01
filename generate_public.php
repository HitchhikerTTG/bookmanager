
<?php
require_once 'includes/BookManager.php';
$manager = new BookManager();

$processedBooks = $manager->getProcessedBooks();
$unprocessedBooks = $manager->getUnprocessedBooks();
$generationTime = date('Y-m-d H:i:s');

// Get sorting parameter
$sort = $_GET['sort'] ?? 'title';
$search = $_GET['search'] ?? '';

// Filter books based on search
if ($search !== '') {
    $processedBooks = array_filter($processedBooks, function($book) use ($search) {
        return stripos($book['title'], $search) !== false;
    });
}

// Sort books
usort($processedBooks, function($a, $b) use ($sort) {
    switch ($sort) {
        case 'author':
            $authorA = $a['authors'][0]['last_name'] . ' ' . $a['authors'][0]['first_name'];
            $authorB = $b['authors'][0]['last_name'] . ' ' . $b['authors'][0]['first_name'];
            return strcasecmp($authorA, $authorB);
        case 'date':
            return $b['upload_date'] - $a['upload_date'];
        case 'genre':
            return strcasecmp($a['genres'][0], $b['genres'][0]);
        default: // title
            return strcasecmp($a['title'], $b['title']);
    }
});

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
            <div class="col-12 mb-3">
                <form class="row g-3" method="get">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="search" placeholder="Szukaj po tytule..." value="{$search}">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="sort">
                            <option value="title" <?php echo $sort === 'title' ? 'selected' : ''; ?>>Sortuj po tytule</option>
                            <option value="author" <?php echo $sort === 'author' ? 'selected' : ''; ?>>Sortuj po autorze</option>
                            <option value="date" <?php echo $sort === 'date' ? 'selected' : ''; ?>>Sortuj po dacie</option>
                            <option value="genre" <?php echo $sort === 'genre' ? 'selected' : ''; ?>>Sortuj po gatunku</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">Zastosuj</button>
                    </div>
                </form>
            </div>
            
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
        <h5 class="mb-1"><a href="_ksiazki/{$book['file_name']}">{$book['title']}</a></h5>
        <p class="mb-1">Autorzy: {$authors}</p>
        <p class="mb-1">Gatunki: {$genres}</p>
        <small>Data dodania: {$date}</small><br>
        <small><a href="http://{$_SERVER['HTTP_HOST']}/_ksiazki/{$book['file_name']}">alternatywnie pobierz po http</a></small>
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
