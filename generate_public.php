
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

    $html = '<?php' . "\n";
    $html .= '$sort = isset($_GET["sort"]) ? $_GET["sort"] : "title";' . "\n";
    $html .= '$selectedGenre = isset($_GET["genre"]) ? $_GET["genre"] : "";' . "\n";
    $html .= '$selectedSeries = isset($_GET["series"]) ? $_GET["series"] : "";' . "\n";
    
    $html .= '$processedBooks = ' . var_export($processedBooks, true) . ';' . "\n\n";
    
    $html .= 'if ($selectedSeries) {
    $processedBooks = array_filter($processedBooks, function($book) use ($selectedSeries) {
        return $book["series"] === $selectedSeries;
    });
    usort($processedBooks, function($a, $b) {
        if (empty($a["series_position"])) return 1;
        if (empty($b["series_position"])) return -1;
        return $a["series_position"] - $b["series_position"];
    });
} else {
    if ($sort === "author") {
        usort($processedBooks, function($a, $b) {
            return strcasecmp($a["authors"][0]["last_name"], $b["authors"][0]["last_name"]);
        });
    } else {
        usort($processedBooks, function($a, $b) {
            return strcasecmp($a["title"], $b["title"]);
        });
    }
}

if ($selectedGenre && $selectedGenre !== "all") {
    $processedBooks = array_filter($processedBooks, function($book) use ($selectedGenre) {
        return in_array($selectedGenre, $book["genres"]);
    });
}
?>' . "\n";

    $html .= <<<HTML
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
                    <?php if ($selectedGenre): ?>
                        <input type="hidden" name="genre" value="<?php echo htmlspecialchars($selectedGenre); ?>">
                    <?php endif; ?>
                    <button type="submit" class="btn btn-outline-primary btn-sm">Sortuj po tytule</button>
                </form>
                <form method="GET" style="display: inline-block;">
                    <input type="hidden" name="sort" value="author">
                    <?php if ($selectedGenre): ?>
                        <input type="hidden" name="genre" value="<?php echo htmlspecialchars($selectedGenre); ?>">
                    <?php endif; ?>
                    <button type="submit" class="btn btn-outline-primary btn-sm">Sortuj po autorze</button>
                </form>
            </div>

            <div class="filter-buttons mt-2">
                <form method="GET" style="display: inline-block; margin-right: 0.5rem;">
                    <input type="hidden" name="genre" value="all">
                    <?php if ($sort !== "title"): ?>
                        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                    <?php endif; ?>
                    <button type="submit" class="btn <?php echo $selectedGenre === "all" ? 'btn-secondary' : 'btn-outline-secondary'; ?> btn-sm">Wszystkie</button>
                </form>
HTML;

    foreach ($allGenres as $genre) {
        $html .= <<<HTML
                <form method="GET" style="display: inline-block; margin-right: 0.5rem;">
                    <input type="hidden" name="genre" value="<?php echo htmlspecialchars('{$genre}'); ?>">
                    <?php if ($sort !== "title"): ?>
                        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                    <?php endif; ?>
                    <button type="submit" class="btn <?php echo \$selectedGenre === '{$genre}' ? 'btn-secondary' : 'btn-outline-secondary'; ?> btn-sm">{$genre}</button>
                </form>
HTML;
    }

    $html .= <<<HTML
            </div>
        </header>

        <div class="row">
        <?php foreach (\$processedBooks as \$book): ?>
            <div class="col-12 book-card">
                <a href="_ksiazki/<?php echo htmlspecialchars(\$book['file_name']); ?>" class="book-title"><?php echo htmlspecialchars(\$book['title']); ?></a>
                <a href="http://_ksiazki/<?php echo htmlspecialchars(\$book['file_name']); ?>" class="download-icon">⬇</a>
                <div class="book-metadata">
                    <strong>Autorzy:</strong> <?php echo implode(', ', array_map(function(\$author) { return htmlspecialchars(\$author['first_name'] . ' ' . \$author['last_name']); }, \$book['authors'])); ?><br>
                    <strong>Gatunki:</strong> <?php echo htmlspecialchars(implode(', ', \$book['genres'])); ?>
                    <?php if (!empty(\$book['series'])): ?>
                    <div class='book-series'>
                        <a href='?series=<?php echo urlencode(\$book['series']); ?>' class='series-link'><?php echo htmlspecialchars(\$book['series']); ?></a>
                        <?php echo !empty(\$book['series_position']) ? ' #' . htmlspecialchars(\$book['series_position']) : ''; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
        <?php if (\$selectedSeries): ?>
        <div class="text-center mt-4">
            <a href="?" class="btn btn-outline-secondary">Pokaż wszystkie książki</a>
        </div>
        <?php endif; ?>
        <footer class="text-center text-muted mt-4">
            <small>Strona wygenerowana: <?php echo htmlspecialchars('{$generationTime}'); ?></small>
        </footer>
    </div>
</body>
</html>
HTML;

    debug_log("HTML generation completed");
    
    if(file_put_contents('index.php', $html)) {
        debug_log("Successfully wrote index.php");
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Failed to write index.php");
    }
} catch (Exception $e) {
    debug_log("Error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
