
<?php
require_once 'includes/BookManager.php';
require_once 'includes/functions.php';

function debug_log($message) {
    error_log("[" . date('Y-m-d H:i:s') . "] " . $message);
}

try {
    debug_log("Starting public page generation");
    $manager = new BookManager();
    $processedBooks = $manager->getProcessedBooks();
    $generationTime = date('Y-m-d H:i:s');
    
    // Get all unique genres and series
    $allGenres = [];
    $allSeries = [];
    foreach ($processedBooks as $book) {
        foreach ($book['genres'] as $genre) {
            if (!in_array($genre, $allGenres)) {
                $allGenres[] = $genre;
            }
        }
        if (!empty($book['series']) && !in_array($book['series'], $allSeries)) {
            $allSeries[] = $book['series'];
        }
    }
    sort($allGenres);
    sort($allSeries);
    debug_log("Found " . count($allGenres) . " unique genres and " . count($allSeries) . " series");

    // Handle sorting and filtering
    $sort = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'title';
    $selectedGenre = isset($_GET['genre']) ? sanitizeInput($_GET['genre']) : '';
    $selectedSeries = isset($_GET['series']) ? sanitizeInput($_GET['series']) : '';

    // Sort books
    if ($selectedSeries) {
        usort($processedBooks, function($a, $b) {
            if (empty($a['series_position'])) return 1;
            if (empty($b['series_position'])) return -1;
            return $a['series_position'] - $b['series_position'];
        });
    } else {
        switch($sort) {
            case 'author':
                usort($processedBooks, function($a, $b) {
                    return strcasecmp($a['authors'][0]['last_name'], $b['authors'][0]['last_name']);
                });
                break;
            default:
                usort($processedBooks, function($a, $b) {
                    return strcasecmp($a['title'], $b['title']);
                });
        }
    }

    debug_log("Books sorted by: " . ($selectedSeries ? 'series position' : $sort));

    // Filter by genre if selected
    if ($selectedGenre && $selectedGenre !== 'all') {
        $processedBooks = array_filter($processedBooks, function($book) use ($selectedGenre) {
            return in_array($selectedGenre, $book['genres']);
        });
    }

    // Filter by series if selected
    if ($selectedSeries) {
        $processedBooks = array_filter($processedBooks, function($book) use ($selectedSeries) {
            return $book['series'] === $selectedSeries;
        });
    }

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
        .book-card { 
            margin-bottom: 1rem; 
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            padding: 1rem;
        }
        .book-title { 
            font-weight: bold;
            color: #0d6efd;
            text-decoration: none;
        }
        .book-metadata { 
            margin: 0.5rem 0;
            color: #666;
        }
        .book-series { 
            font-style: italic;
            color: #28a745;
        }
        .filter-buttons {
            margin-bottom: 1rem;
        }
        .filter-buttons form {
            display: inline-block;
            margin-right: 0.5rem;
        }
        .download-icon {
            margin-left: 0.5rem;
            font-size: 0.8em;
            color: #666;
        }
        .series-link {
            color: #28a745;
            text-decoration: none;
        }
        .series-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <header class="pb-3 mb-4 border-bottom">
            <h1>Moja Biblioteka</h1>
            
            <div class="filter-buttons">
                <form method="GET" style="display: inline-block; margin-right: 1rem;">
                    <input type="hidden" name="sort" value="title">
                    <button type="submit" class="btn btn-outline-primary btn-sm">Sortuj po tytule</button>
                </form>
                <form method="GET" style="display: inline-block;">
                    <input type="hidden" name="sort" value="author">
                    <button type="submit" class="btn btn-outline-primary btn-sm">Sortuj po autorze</button>
                </form>
            </div>

            <div class="filter-buttons mt-2">
                <form method="GET" style="display: inline-block; margin-right: 0.5rem;">
                    <input type="hidden" name="genre" value="all">
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Wszystkie</button>
                </form>
HTML;

    foreach ($allGenres as $genre) {
        $activeClass = ($genre === $selectedGenre) ? 'btn-secondary' : 'btn-outline-secondary';
        $html .= <<<HTML
                <form method="GET" style="display: inline-block; margin-right: 0.5rem;">
                    <input type="hidden" name="genre" value="{$genre}">
                    <button type="submit" class="btn {$activeClass} btn-sm">{$genre}</button>
                </form>
HTML;
    }

    $html .= <<<HTML
            </div>
        </header>

        <div class="row">
HTML;

    if ($selectedSeries) {
        $html .= <<<HTML
            <div class="col-12 mb-4">
                <h2>Seria: {$selectedSeries}</h2>
                <a href="?" class="btn btn-outline-secondary btn-sm">Powrót do wszystkich książek</a>
            </div>
HTML;
    }

    foreach ($processedBooks as $book) {
        $authors = implode(', ', array_map(function($author) {
            return htmlspecialchars($author['first_name'] . ' ' . $author['last_name']);
        }, $book['authors']));
        
        $genres = implode(', ', array_map('htmlspecialchars', $book['genres']));
        $seriesInfo = '';
        if (!empty($book['series'])) {
            $seriesLink = htmlspecialchars($book['series']);
            $position = htmlspecialchars($book['series_position']);
            $seriesInfo = "<div class='book-series'>" . 
                         "<a href='?series={$seriesLink}' class='series-link'>" . 
                         $seriesLink . "</a> #" . $position . 
                         "</div>";
        }
        
        $fileName = htmlspecialchars($book['file_name']);
        $title = htmlspecialchars($book['title']);
        
        $html .= <<<HTML
            <div class="col-12 book-card">
                <a href="_ksiazki/{$fileName}" class="book-title">{$title}</a>
                <a href="http://_ksiazki/{$fileName}" class="download-icon">⬇</a>
                <div class="book-metadata">
                    <strong>Autorzy:</strong> {$authors}<br>
                    <strong>Gatunki:</strong> {$genres}
                    {$seriesInfo}
                </div>
            </div>
HTML;
    }

    $html .= <<<HTML
        </div>
        <footer class="text-center text-muted mt-4">
            <small>Strona wygenerowana: {$generationTime}</small>
        </footer>
    </div>
</body>
</html>
HTML;

    debug_log("HTML generation completed");
    
    if(file_put_contents('index.html', $html)) {
        debug_log("Successfully wrote index.html");
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Failed to write index.html");
    }
} catch (Exception $e) {
    debug_log("Error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
