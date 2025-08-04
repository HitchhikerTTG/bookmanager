
<?php

// Funkcja do wczytywania danych z pliku JSON
function loadJsonFile($filePath) {
    if (!file_exists($filePath)) {
        die("Błąd: Plik $filePath nie istnieje.");
    }

    $jsonData = file_get_contents($filePath);

    if ($jsonData === false) {
        die("Błąd: Nie można odczytać pliku $filePath. Sprawdź uprawnienia.");
    }

    $data = json_decode($jsonData, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        die("Błąd: Nie można zdekodować JSON z pliku $filePath. " . json_last_error_msg());
    }

    return $data;
}

// Wczytanie danych z JSON
$booksFile = 'data/books.json';
$booksData = loadJsonFile($booksFile);

if (!isset($booksData['books']) || !is_array($booksData['books'])) {
    die("Błąd: Nieprawidłowa struktura pliku JSON.");
}

$books = $booksData['books'];

// Data i godzina generowania strony
$generationTime = date('Y-m-d H:i:s');

// Przygotowanie kodu PHP do wygenerowanego pliku
$booksJson = json_encode($books, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

$indexContent = <<<PHP
<?php

// Dane książek
\$books = json_decode('{$booksJson}', true);

// Dynamiczne pobieranie domeny
\$protocol = (!empty(\$_SERVER['HTTPS']) && \$_SERVER['HTTPS'] !== 'off') ? "https" : "http";
\$domain = \$protocol . "://" . \$_SERVER['HTTP_HOST'];

// Parametry URL
\$sort = \$_GET['sort'] ?? null;
\$filterGenre = \$_GET['genre'] ?? null;
\$filterAuthor = \$_GET['author'] ?? null;
\$filterSeries = \$_GET['series'] ?? null;

// Filtry i sortowanie
\$filteredBooks = \$books;

if (\$filterGenre) {
    \$filteredBooks = array_filter(\$filteredBooks, function (\$book) use (\$filterGenre) {
        return in_array(\$filterGenre, \$book['genres']);
    });
}

if (\$filterAuthor) {
    \$filteredBooks = array_filter(\$filteredBooks, function (\$book) use (\$filterAuthor) {
        foreach (\$book['authors'] as \$author) {
            \$authorFullName = \$author['first_name'] . ' ' . \$author['last_name'];
            if (\$authorFullName === \$filterAuthor) {
                return true;
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
    $html .= '$processedBooks = ' . var_export($processedBooks, true) . ';' . "\n\n";
    
    $html .= '// Get parameters
$page = max(1, intval($_GET["page"] ?? 1));
$perPage = 30;
$search = trim($_GET["search"] ?? "");
$genre = $_GET["genre"] ?? "";
$sort = $_GET["sort"] ?? "title";
$series = $_GET["series"] ?? "";

// Filter books
$filteredBooks = $processedBooks;

// Search filter
if (!empty($search)) {
    $filteredBooks = array_filter($filteredBooks, function($book) use ($search) {
        $searchLower = mb_strtolower($search, "UTF-8");
        $titleMatch = mb_strpos(mb_strtolower($book["title"], "UTF-8"), $searchLower) !== false;
        $authorMatch = false;
        foreach ($book["authors"] as $author) {
            $authorName = mb_strtolower($author["first_name"] . " " . $author["last_name"], "UTF-8");
            if (mb_strpos($authorName, $searchLower) !== false) {
                $authorMatch = true;
                break;
            }
        }
        return $titleMatch || $authorMatch;
    });
}

// Genre filter
if (!empty($genre) && $genre !== "all") {
    $filteredBooks = array_filter($filteredBooks, function($book) use ($genre) {
        return in_array($genre, $book["genres"]);
    });
}

// Series filter
if (!empty($series)) {
    $filteredBooks = array_filter($filteredBooks, function($book) use ($series) {
        return $book["series"] === $series;
    });
}

// Sort books
if ($series) {
    // Sort by series position when viewing series
    usort($filteredBooks, function($a, $b) {
        if (empty($a["series_position"])) return 1;
        if (empty($b["series_position"])) return -1;
        return $a["series_position"] - $b["series_position"];
    });
} else {
    switch($sort) {
        case "author":
            usort($filteredBooks, function($a, $b) {
                return strcasecmp($a["authors"][0]["last_name"], $b["authors"][0]["last_name"]);
            });
            break;
        case "date":
            usort($filteredBooks, function($a, $b) {
                return strcmp($b["file_name"], $a["file_name"]); // Newer files first
            });
            break;
        default: // title
            usort($filteredBooks, function($a, $b) {
                return strcasecmp($a["title"], $b["title"]);
            });
    }
}

// Pagination
$totalBooks = count($filteredBooks);
$totalPages = ceil($totalBooks / $perPage);
$offset = ($page - 1) * $perPage;
$booksForPage = array_slice($filteredBooks, $offset, $perPage);

// Helper function for building URLs
function buildUrl($params = []) {
    $current = [
        "search" => $_GET["search"] ?? "",
        "genre" => $_GET["genre"] ?? "",
        "sort" => $_GET["sort"] ?? "title",
        "series" => $_GET["series"] ?? "",
        "page" => $_GET["page"] ?? 1
    ];
    $merged = array_merge($current, $params);
    $query = http_build_query(array_filter($merged, function($v) { return $v !== ""; }));
    return "?" . $query;

}

// Generowanie informacji o filtrze
\$filterDescription = "Wszystkie książki";
if (\$filterGenre) {
    \$filterDescription = "Książki z gatunku: " . htmlspecialchars(\$filterGenre);
}
if (\$filterAuthor) {
    \$filterDescription .= " autora: " . htmlspecialchars(\$filterAuthor);
}
if (\$filterSeries) {
    \$filterDescription = "Książki z serii: " . htmlspecialchars(\$filterSeries);
}

if (empty(\$filteredBooks)) {
    \$filterDescription .= " (Brak wyników)";
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="generation-time" content="{$generationTime}">
    <title>Moja Biblioteka</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 10px;
            background: #ffffff;
            color: #000000;
            line-height: 1.4;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        h1 {
            margin: 10px 0;
            font-size: 24px;
            font-weight: bold;
        }
        
        .controls {
            border: 2px solid #000000;
            padding: 10px;
            margin: 10px 0;
            background: #f8f8f8;
        }
        
        .controls h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        
        .control-row {
            margin: 5px 0;
        }
        
        label {
            font-weight: bold;
            margin-right: 10px;
        }
        
        input[type="text"], select {
            padding: 5px;
            border: 1px solid #000000;
            font-size: 14px;
            margin-right: 10px;
        }
        
        button, input[type="submit"] {
            padding: 8px 12px;
            border: 2px solid #000000;
            background: #ffffff;
            color: #000000;
            font-size: 14px;
            cursor: pointer;
            margin-right: 5px;
            margin-bottom: 5px;
        }
        
        button:hover, input[type="submit"]:hover {
            background: #000000;
            color: #ffffff;
        }
        
        .book-list {
            border: 1px solid #000000;
            margin: 10px 0;
        }
        
        .book-header {
            background: #e0e0e0;
            padding: 8px;
            border-bottom: 1px solid #000000;
            font-weight: bold;
        }
        
        .book-row {
            padding: 8px;
            border-bottom: 1px solid #cccccc;
            display: table;
            width: 100%;
        }
        
        .book-row:nth-child(even) {
            background: #f8f8f8;
        }
        
        .book-title {
            font-weight: bold;
            margin-right: 15px;
        }
        
        .book-author {
            color: #333333;
            margin-right: 15px;
        }
        
        .book-genres {
            color: #666666;
            font-size: 12px;
            margin-right: 15px;
        }
        
        .book-series {
            color: #006600;
            font-style: italic;
            margin-right: 15px;
        }
        
        .download-links {
            white-space: nowrap;
        }
        
        .download-links a {
            color: #000000;
            text-decoration: underline;
            margin-right: 10px;
            font-weight: bold;
        }
        
        .download-links a:hover {
            background: #000000;
            color: #ffffff;
        }
        
        .pagination {
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            border: 1px solid #000000;
        }
        
        .pagination a, .pagination span {
            display: inline-block;
            padding: 5px 10px;
            margin: 2px;
            border: 1px solid #000000;
            text-decoration: none;
            color: #000000;
        }
        
        .pagination a:hover {
            background: #000000;
            color: #ffffff;
        }
        
        .pagination .current {
            background: #000000;
            color: #ffffff;
            font-weight: bold;
        }
        
        .stats {
            text-align: center;
            margin: 10px 0;
            color: #666666;
        }
        
        .footer {
            text-align: center;
            margin: 20px 0;
            color: #666666;
            font-size: 12px;
        }
        
        .clear-filters {
            margin: 10px 0;
        }
        
        .series-link {
            color: #006600;
            text-decoration: underline;
        }
        
        .series-link:hover {
            background: #006600;
            color: #ffffff;

        }
    </style>
</head>
<body>

    <div class="container">
        <h1>Moja Biblioteka</h1>
        
        <div class="controls">
            <h3>Wyszukiwanie i filtrowanie</h3>
            
            <form method="GET">
                <div class="control-row">
                    <label for="search">Szukaj:</label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tytuł lub autor">
                    <input type="hidden" name="genre" value="<?php echo htmlspecialchars($genre); ?>">
                    <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                    <input type="submit" value="Szukaj">
                </div>
            </form>
            
            <form method="GET">
                <div class="control-row">
                    <label for="genre">Gatunek:</label>
                    <select id="genre" name="genre">
                        <option value="">Wszystkie</option>

HTML;

    foreach ($allGenres as $g) {
        $html .= '<option value="' . htmlspecialchars($g) . '"<?php echo $genre === "' . htmlspecialchars($g) . '" ? " selected" : ""; ?>>' . htmlspecialchars($g) . '</option>' . "\n";
    }


    $html .= <<<HTML
                    </select>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                    <input type="submit" value="Filtruj">

                </div>
            </form>
            
            <form method="GET">
                <div class="control-row">
                    <label for="sort">Sortuj:</label>
                    <select id="sort" name="sort">
                        <option value="title"<?php echo $sort === "title" ? " selected" : ""; ?>>Alfabetycznie (tytuł)</option>
                        <option value="author"<?php echo $sort === "author" ? " selected" : ""; ?>>Alfabetycznie (autor)</option>
                        <option value="date"<?php echo $sort === "date" ? " selected" : ""; ?>>Najnowsze pierwsze</option>
                    </select>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <input type="hidden" name="genre" value="<?php echo htmlspecialchars($genre); ?>">
                    <input type="submit" value="Sortuj">
                </div>
            </form>
            
            <?php if (!empty($search) || !empty($genre) || !empty($series)): ?>
            <div class="clear-filters">
                <a href="?" style="color: #cc0000; font-weight: bold;">Wyczyść wszystkie filtry</a>
            </div>

            <?php endif; ?>
        </div>
        
        <?php if ($series): ?>
        <div style="text-align: center; margin: 10px 0; padding: 10px; border: 1px solid #006600; background: #f0f8f0;">
            <strong>Seria: <?php echo htmlspecialchars($series); ?></strong>
            <br><a href="<?php echo buildUrl(['series' => '', 'page' => 1]); ?>">← Powrót do wszystkich książek</a>
        </div>
        <?php endif; ?>
        
        <div class="stats">
            Znaleziono <?php echo $totalBooks; ?> książek
            <?php if ($totalPages > 1): ?>
                | Strona <?php echo $page; ?> z <?php echo $totalPages; ?>
            <?php endif; ?>
        </div>
        
        <div class="book-list">
            <div class="book-header">
                Tytuł | Autor | Gatunki | Seria | Pobierz
            </div>
            
            <?php foreach ($booksForPage as $book): ?>
            <div class="book-row">
                <div class="book-title"><?php echo htmlspecialchars($book['title']); ?></div>
                <div class="book-author">
                    <?php echo implode(', ', array_map(function($author) {
                        return htmlspecialchars($author['first_name'] . ' ' . $author['last_name']);
                    }, $book['authors'])); ?>
                </div>
                <div class="book-genres"><?php echo htmlspecialchars(implode(', ', $book['genres'])); ?></div>
                <?php if (!empty($book['series'])): ?>
                <div class="book-series">
                    <a href="<?php echo buildUrl(['series' => $book['series'], 'page' => 1]); ?>" class="series-link">
                        <?php echo htmlspecialchars($book['series']); ?>
                        <?php echo !empty($book['series_position']) ? ' #' . htmlspecialchars($book['series_position']) : ''; ?>
                    </a>
                </div>
                <?php endif; ?>
                <div class="download-links">
                    <a href="https://<?php echo $_SERVER['HTTP_HOST']; ?>/_ksiazki/<?php echo htmlspecialchars($book['file_name']); ?>">HTTPS</a>
                    <a href="http://<?php echo $_SERVER['HTTP_HOST']; ?>/_ksiazki/<?php echo htmlspecialchars($book['file_name']); ?>">HTTP</a>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($booksForPage)): ?>
            <div class="book-row" style="text-align: center; color: #666666;">
                Brak książek spełniających kryteria wyszukiwania.
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="<?php echo buildUrl(['page' => 1]); ?>">Pierwsza</a>
                <a href="<?php echo buildUrl(['page' => $page - 1]); ?>">Poprzednia</a>
            <?php endif; ?>
            
            <?php
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            
            for ($i = $startPage; $i <= $endPage; $i++):
                if ($i == $page): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="<?php echo buildUrl(['page' => $i]); ?>"><?php echo $i; ?></a>
                <?php endif;
            endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="<?php echo buildUrl(['page' => $page + 1]); ?>">Następna</a>
                <a href="<?php echo buildUrl(['page' => $totalPages]); ?>">Ostatnia</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="footer">
            Strona wygenerowana: <?php echo htmlspecialchars($generationTime); ?>
            <br>Całkowita liczba książek: <?php echo count($processedBooks); ?>
        </div>

    </div>
</div>
</body>
</html>
PHP;


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

