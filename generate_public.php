
<?php
require_once 'includes/BookManager.php';

try {
    $manager = new BookManager();
    $processedBooks = $manager->getProcessedBooks();
    $generationTime = date('Y-m-d H:i:s');
    
    // Get all unique genres
    $allGenres = [];
    foreach ($processedBooks as $book) {
        foreach ($book['genres'] as $genre) {
            if (!in_array($genre, $allGenres)) {
                $allGenres[] = $genre;
            }
        }
    }
    sort($allGenres);

    // Handle filtering
    $searchQuery = isset($_GET['search']) ? strtolower(trim($_GET['search'])) : '';
    $selectedGenre = isset($_GET['genre']) ? $_GET['genre'] : '';

    // Filter books
    if ($searchQuery || $selectedGenre) {
        $processedBooks = array_filter($processedBooks, function($book) use ($searchQuery, $selectedGenre) {
            if ($selectedGenre && $selectedGenre !== 'all' && !in_array($selectedGenre, $book['genres'])) {
                return false;
            }
            
            if ($searchQuery) {
                $titleMatch = strpos(strtolower($book['title']), $searchQuery) !== false;
                $authorMatch = false;
                foreach ($book['authors'] as $author) {
                    $fullName = strtolower($author['first_name'] . ' ' . $author['last_name']);
                    if (strpos($fullName, $searchQuery) !== false) {
                        $authorMatch = true;
                        break;
                    }
                }
                return $titleMatch || $authorMatch;
            }
            return true;
        });
    }

    // Default sort by title
    usort($processedBooks, function($a, $b) {
        return strcasecmp($a['title'], $b['title']);
    });

    $html = <<<HTML
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="generation-time" content="{$generationTime}">
    <title>Moja Biblioteka</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .book-card { margin-bottom: 1rem; }
        .book-title { font-weight: bold; }
        .book-metadata { margin: 0.5rem 0; }
        .book-series { font-style: italic; color: #28a745; }
        .search-form { margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="container py-4">
        <header class="pb-3 mb-4 border-bottom">
            <h1>Moja Biblioteka</h1>
            <p>Ostatnia aktualizacja: {$generationTime}</p>
        </header>

        <form method="GET" class="search-form">
            <div class="row g-3">
                <div class="col-sm-6">
                    <input type="text" name="search" class="form-control" placeholder="Wyszukaj..." value="{$searchQuery}">
                </div>
                <div class="col-sm-4">
                    <select name="genre" class="form-control">
                        <option value="all">Wszystkie gatunki</option>
HTML;

    foreach ($allGenres as $genre) {
        $selected = $genre === $selectedGenre ? 'selected' : '';
        $html .= "<option value=\"" . htmlspecialchars($genre) . "\" {$selected}>" . htmlspecialchars($genre) . "</option>\n";
    }

    $html .= <<<HTML
                    </select>
                </div>
                <div class="col-sm-2">
                    <button type="submit" class="btn btn-primary w-100">Filtruj</button>
                </div>
            </div>
        </form>

        <div class="row">
HTML;

    foreach ($processedBooks as $book) {
        $authors = implode(', ', array_map(function($author) {
            return $author['first_name'] . ' ' . $author['last_name'];
        }, $book['authors']));
        
        $genres = implode(', ', $book['genres']);
        $seriesInfo = '';
        if (!empty($book['series'])) {
            $seriesInfo = "<div class='book-series'>{$book['series']} #{$book['series_position']}</div>";
        }
        
        $html .= <<<HTML
            <div class="col-12 book-card">
                <div class="card">
                    <div class="card-body">
                        <a href="_ksiazki/{$book['file_name']}" class="book-title">{$book['title']}</a>
                        <div class="book-metadata">
                            <strong>Autorzy:</strong> {$authors}<br>
                            <strong>Gatunki:</strong> {$genres}
                            {$seriesInfo}
                        </div>
                    </div>
                </div>
            </div>
HTML;
    }

    $html .= <<<HTML
        </div>
    </div>
</body>
</html>
HTML;

    if(file_put_contents('index.html', $html)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Failed to write index.html");
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
