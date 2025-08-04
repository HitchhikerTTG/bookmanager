
<?php

function debug_log($message) {
    error_log("[GENERATE_PUBLIC] " . $message);
}

try {
    debug_log("Starting HTML generation");
    
    // Load books data
    if (!file_exists('data/books.json')) {
        throw new Exception("books.json not found");
    }
    
    $jsonContent = file_get_contents('data/books.json');
    $booksData = json_decode($jsonContent, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON in books.json: " . json_last_error_msg());
    }
    
    if (!isset($booksData['books']) || !is_array($booksData['books'])) {
        throw new Exception("Invalid structure in books.json");
    }
    
    $rawBooks = $booksData['books'];
    debug_log("Loaded " . count($rawBooks) . " books from JSON");
    
    // Process books - filter only complete ones
    $processedBooks = array_filter($rawBooks, function($book) {
        return !empty($book['title']) && 
               !empty($book['authors']) && 
               count($book['authors']) > 0 &&
               !empty($book['authors'][0]['first_name']) && 
               !empty($book['authors'][0]['last_name']);
    });
    
    debug_log("Filtered to " . count($processedBooks) . " complete books");
    
    // Gather all unique genres and series
    $allGenres = [];
    $allSeries = [];
    
    foreach ($processedBooks as $book) {
        if (!empty($book['genres'])) {
            foreach ($book['genres'] as $genre) {
                if (!in_array($genre, $allGenres)) {
                    $allGenres[] = $genre;
                }
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
$perPage = 25;
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
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="generation-time" content="' . date('Y-m-d H:i:s') . '">
    <title><?php 
        if ($series) {
            echo "Seria: " . htmlspecialchars($series) . " (" . $totalBooks . " książek)";
        } elseif ($genre && $genre !== "all") {
            echo "Gatunek: " . htmlspecialchars($genre) . " (" . $totalBooks . " książek)";
        } elseif ($search) {
            echo "Wyniki wyszukiwania: \"" . htmlspecialchars($search) . "\" (" . $totalBooks . " książek)";
        } else {
            echo "Wszystkie książki (" . $totalBooks . " książek)";
        }
    ?> - E-Biblioteczka</title>
    <style>
        /* Reset podstawowy */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        /* Ukrywanie/pokazywanie filtrów */
        .filters-toggle {
            margin-bottom: 16px;
        }
        
        .filters-toggle summary {
            display: block;
            padding: 12px 24px;
            border: 2px solid #000000;
            background: #ffffff;
            color: #000000;
            font-size: 1em;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
            list-style: none;
            user-select: none;
        }
        
        .filters-toggle summary:hover {
            background: #000000;
            color: #ffffff;
        }
        
        .filters-toggle summary::-webkit-details-marker {
            display: none;
        }
        
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #ffffff;
            color: #000000;
            line-height: 1.5;
            padding: 16px;
            font-size: 16px;
            min-font-size: 16px;
        }
        
        /* Zapewniamy minimalną czcionkę 16px dla wszystkich elementów */
        * {
            font-size: max(1em, 16px);
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        /* Nagłówek główny */
        .main-title {
            font-size: 1.8em;
            font-weight: bold;
            margin-bottom: 16px;
            text-align: left;
            border-bottom: 2px solid #000000;
            padding-bottom: 8px;
        }
        
        /* Sekcje formularzy - kompaktowe */
        .form-section {
            margin-bottom: 16px;
            padding: 12px;
            border: 1px solid #333333;
            background: #f8f8f8;
        }
        
        .form-section h3 {
            font-size: 1.1em;
            font-weight: bold;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        
        .form-group {
            margin-bottom: 8px;
        }
        
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 3px;
            font-size: 1em;
        }
        
        input[type="text"], select {
            width: 100%;
            max-width: 300px;
            padding: 8px 12px;
            border: 2px solid #000000;
            font-size: 1em;
            background: #ffffff;
            color: #000000;
            margin-bottom: 6px;
        }
        
        .btn {
            padding: 10px 16px;
            border: 2px solid #000000;
            background: #ffffff;
            color: #000000;
            font-size: 1em;
            font-weight: bold;
            cursor: pointer;
            margin-top: 4px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            background: #000000;
            color: #ffffff;
        }
        
        /* Informacja o wynikach */
        .results-info {
            font-size: 1em;
            font-weight: bold;
            margin: 16px 0 12px 0;
            padding: 12px;
            background: #e8e8e8;
            border: 1px solid #333333;
        }
        
        .results-info::before {
            content: "📚 ";
            margin-right: 4px;
        }
        
        .active-filters {
            font-size: max(0.9em, 16px);
            margin-top: 8px;
            color: #333333;
        }
        
        /* Lista książek */
        .books-list {
            margin: 16px 0;
        }
        
        .book-item {
            margin-bottom: 24px;
            padding: 16px;
            border: 1px solid #cccccc;
            background: #ffffff;
            line-height: 1.4;
        }
        
        .book-item:nth-child(even) {
            background: #f9f9f9;
        }
        
        /* Linia 1: Tytuł ↔ Autor */
        .book-line-1 {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 6px;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .book-title {
            font-size: 1.4em;
            font-weight: bold;
            color: #000000;
            line-height: 1.3;
            flex: 1;
            min-width: 0;
        }
        
        .book-title::before {
            content: "📖 ";
            margin-right: 4px;
        }
        
        .book-authors {
            font-size: 1em;
            color: #333333;
            text-align: right;
            white-space: nowrap;
            flex-shrink: 0;
        }
        
        /* Linia 2: Seria ↔ Gatunek */
        .book-line-2 {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 12px;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .book-series {
            font-size: 1em;
            color: #555555;
            flex: 1;
            min-width: 0;
        }
        
        .book-series a {
            color: #006600;
            text-decoration: underline;
            font-weight: bold;
        }
        
        .book-series a:hover {
            background: #006600;
            color: #ffffff;
            padding: 2px 4px;
        }
        
        .book-genres {
            font-size: 1em;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #222222;
            text-align: right;
            white-space: nowrap;
            flex-shrink: 0;
        }
        
        /* Fallback dla starszych urządzeń bez flex */
        .no-flex .book-line-1,
        .no-flex .book-line-2 {
            display: block;
        }
        
        .no-flex .book-authors,
        .no-flex .book-genres {
            text-align: left;
            margin-top: 4px;
        }
        
        .download-links {
            margin-top: 16px;
            padding-top: 12px;
            border-top: 1px solid #cccccc;
        }
        
        .download-link {
            display: inline-block;
            margin-right: 16px;
            margin-bottom: 8px;
            padding: 12px 20px;
            border: 2px solid #000000;
            background: #ffffff;
            color: #000000;
            text-decoration: none;
            font-weight: bold;
            font-size: 1em;
        }
        
        .download-link::before {
            content: "📥 ";
            margin-right: 4px;
        }
        
        .download-link:hover {
            background: #000000;
            color: #ffffff;
        }
        
        .download-link.https {
            border-color: #006600;
            color: #006600;
        }
        
        .download-link.https:hover {
            background: #006600;
            color: #ffffff;
        }
        
        .download-link.http {
            border-color: #cc6600;
            color: #cc6600;
        }
        
        .download-link.http:hover {
            background: #cc6600;
            color: #ffffff;
        }
        
        /* Stronicowanie */
        .pagination {
            text-align: center;
            margin: 32px 0;
            padding: 16px;
            border: 1px solid #000000;
            background: #f8f8f8;
        }
        
        .pagination-info {
            font-weight: bold;
            margin-bottom: 12px;
        }
        
        .pagination a, .pagination span {
            display: inline-block;
            padding: 8px 12px;
            margin: 2px;
            border: 1px solid #000000;
            text-decoration: none;
            color: #000000;
            font-weight: bold;
        }
        
        .pagination a:hover {
            background: #000000;
            color: #ffffff;
        }
        
        .pagination .current {
            background: #000000;
            color: #ffffff;
        }
        
        /* Komunikaty specjalne */
        .no-results {
            text-align: center;
            padding: 32px;
            color: #666666;
            font-size: 1.1em;
            border: 1px solid #cccccc;
            margin: 24px 0;
        }
        
        .clear-filters {
            margin: 16px 0;
            text-align: center;
        }
        
        .clear-filters a {
            color: #cc0000;
            font-weight: bold;
            text-decoration: underline;
            font-size: 1em;
        }
        
        .clear-filters a:hover {
            background: #cc0000;
            color: #ffffff;
            padding: 4px 8px;
        }
        
        /* Stopka */
        .footer {
            text-align: center;
            margin-top: 32px;
            padding: 16px;
            color: #666666;
            font-size: max(0.9em, 16px);
            border-top: 1px solid #cccccc;
        }
        
        /* Serie specjalne */
        .series-view {
            text-align: center;
            margin: 16px 0;
            padding: 16px;
            border: 2px solid #006600;
            background: #f0f8f0;
        }
        
        .series-view h2 {
            font-size: 1.2em;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        /* Responsywność dla małych ekranów */
        @media (max-width: 600px) {
            body {
                padding: 8px;
            }
            
            .download-link {
                display: block;
                margin-right: 0;
                margin-bottom: 8px;
                text-align: center;
            }
            
            input[type="text"], select {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="main-title"><?php 
            if ($series) {
                echo "Seria: " . htmlspecialchars($series) . " (" . $totalBooks . " książek)";
            } elseif ($genre && $genre !== "all") {
                echo "Gatunek: " . htmlspecialchars($genre) . " (" . $totalBooks . " książek)";
            } elseif ($search) {
                echo "Wyniki wyszukiwania: \"" . htmlspecialchars($search) . "\" (" . $totalBooks . " książek)";
            } else {
                echo "Wszystkie książki (" . $totalBooks . " książek)";
            }
        ?></h1>
        
        <!-- Przycisk do pokazywania/ukrywania filtrów -->
        <details class="filters-toggle">
            <summary>📋 Pokaż/Ukryj filtry</summary>
            <!-- Sekcja wyszukiwania -->
            <div class="form-section">
                <h3>Wyszukiwanie</h3>
            <form method="GET">
                <div class="form-group">
                    <label for="search">Szukaj według tytułu lub autora:</label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Wpisz tytuł lub nazwisko autora">
                </div>
                <input type="hidden" name="genre" value="<?php echo htmlspecialchars($genre); ?>">
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                <input type="hidden" name="series" value="<?php echo htmlspecialchars($series); ?>">
                <button type="submit" class="btn">Szukaj</button>
            </form>
        </div>
        
        <!-- Sekcja filtrowania -->
        <div class="form-section">
            <h3>Filtrowanie według gatunku</h3>
            <form method="GET">
                <div class="form-group">
                    <label for="genre">Wybierz gatunek:</label>
                    <select id="genre" name="genre">
                        <option value="">Wszystkie gatunki</option>';

    foreach ($allGenres as $g) {
        $html .= '<option value="' . htmlspecialchars($g) . '"<?php echo $genre === "' . htmlspecialchars($g) . '" ? " selected" : ""; ?>>' . htmlspecialchars($g) . '</option>' . "\n";
    }

    $html .= '                    </select>
                </div>
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                <input type="hidden" name="series" value="<?php echo htmlspecialchars($series); ?>">
                <button type="submit" class="btn">Filtruj</button>
            </form>
        </div>
        
        <!-- Sekcja sortowania -->
        <div class="form-section">
            <h3>Sortowanie</h3>
            <form method="GET">
                <div class="form-group">
                    <label for="sort">Sortuj według:</label>
                    <select id="sort" name="sort">
                        <option value="title"<?php echo $sort === "title" ? " selected" : ""; ?>>Alfabetycznie według tytułu</option>
                        <option value="author"<?php echo $sort === "author" ? " selected" : ""; ?>>Alfabetycznie według autora</option>
                        <option value="date"<?php echo $sort === "date" ? " selected" : ""; ?>>Najnowsze pierwsze</option>
                    </select>
                </div>
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                <input type="hidden" name="genre" value="<?php echo htmlspecialchars($genre); ?>">
                <input type="hidden" name="series" value="<?php echo htmlspecialchars($series); ?>">
                <button type="submit" class="btn">Sortuj</button>
            </form>
        </div>
        </details>
        <!-- Koniec kontenera z filtrami -->
        
        <?php if (!empty($search) || !empty($genre) || !empty($series)): ?>
        <div class="clear-filters">
            <a href="?">Wyczyść wszystkie filtry i wróć do pełnej listy</a>
        </div>
        <?php endif; ?>
        
        <?php if ($series): ?>
        <div class="series-view">
            <h2>Seria: <?php echo htmlspecialchars($series); ?></h2>
            <a href="<?php echo buildUrl([\'series\' => \'\', \'page\' => 1]); ?>" class="btn">← Powrót do wszystkich książek</a>
        </div>
        <?php endif; ?>
        
        <!-- Lista książek -->
        <div class="books-list">
            <?php if (empty($booksForPage)): ?>
            <div class="no-results">
                <strong>Brak książek spełniających kryteria wyszukiwania.</strong>
                <br>Spróbuj zmienić parametry wyszukiwania lub wyczyść filtry.
            </div>
            <?php else: ?>
                <?php foreach ($booksForPage as $book): ?>
                <div class="book-item">
                    <!-- Linia 1: Tytuł ↔ Autor -->
                    <div class="book-line-1">
                        <div class="book-title"><?php echo htmlspecialchars($book[\'title\']); ?></div>
                        <div class="book-authors">
                            <?php echo implode(\', \', array_map(function($author) {
                                return htmlspecialchars($author[\'first_name\'] . \' \' . $author[\'last_name\']);
                            }, $book[\'authors\'])); ?>
                        </div>
                    </div>
                    
                    <!-- Linia 2: Seria ↔ Gatunek -->
                    <div class="book-line-2">
                        <div class="book-series">
                            <?php if (!empty($book[\'series\'])): ?>
                                Seria: <a href="<?php echo buildUrl([\'series\' => $book[\'series\'], \'page\' => 1]); ?>">
                                    <?php echo htmlspecialchars($book[\'series\']); ?>
                                    <?php echo !empty($book[\'series_position\']) ? \' (część \' . htmlspecialchars($book[\'series_position\']) . \')\' : \'\'; ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="book-genres">
                            <?php if (!empty($book[\'genres\'])): ?>
                                <?php echo htmlspecialchars(implode(\' | \', $book[\'genres\'])); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="download-links">
                        <a href="https://<?php echo $_SERVER[\'HTTP_HOST\']; ?>/_ksiazki/<?php echo htmlspecialchars($book[\'file_name\']); ?>" 
                           class="download-link https">Pobierz HTTPS</a>
                        <a href="http://<?php echo $_SERVER[\'HTTP_HOST\']; ?>/_ksiazki/<?php echo htmlspecialchars($book[\'file_name\']); ?>" 
                           class="download-link http">Pobierz HTTP – dla starszych Kindle</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <div class="pagination-info">Strona <?php echo $page; ?> z <?php echo $totalPages; ?></div>
            
            <?php if ($page > 1): ?>
                <a href="<?php echo buildUrl([\'page\' => 1]); ?>">1</a>
                <?php if ($page > 2): ?>
                    <span>...</span>
                <?php endif; ?>
                <a href="<?php echo buildUrl([\'page\' => $page - 1]); ?>">Poprzednia</a>
            <?php endif; ?>
            
            <span class="current"><?php echo $page; ?></span>
            
            <?php if ($page < $totalPages): ?>
                <a href="<?php echo buildUrl([\'page\' => $page + 1]); ?>">Następna</a>
                <?php if ($page < $totalPages - 1): ?>
                    <span>...</span>
                <?php endif; ?>
                <a href="<?php echo buildUrl([\'page\' => $totalPages]); ?>"><?php echo $totalPages; ?></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="footer">
            <strong>E-Biblioteczka</strong><br>
            Strona wygenerowana: ' . date('Y-m-d H:i:s') . '<br>
            Całkowita liczba książek w bibliotece: <?php echo count($processedBooks); ?>
        </div>
    </div>
</body>
</html>';

    debug_log("HTML generation completed");
    
    if(file_put_contents('index.php', $html)) {
        debug_log("Successfully wrote index.php");
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Strona została wygenerowana zgodnie z wytycznymi dla e-czytników']);
    } else {
        throw new Exception("Failed to write index.php");
    }
} catch (Exception $e) {
    debug_log("Error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
