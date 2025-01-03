<?php

require_once 'includes/BookManager.php';
require_once 'includes/functions.php';

// Tryb debugowania
$debug = true;
$logFile = 'logs/generate.log';

// Funkcja logowania z automatycznym tworzeniem katalogu
function logMessage($message) {
    global $debug, $logFile;
    if ($debug) {
        $logDir = dirname($logFile);

        // Sprawdzenie, czy katalog istnieje, jeśli nie - utwórz
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // Zapis wiadomości do pliku logów
        file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
    }
}

try {
    // Inicjalizacja BookManager
    $bookManager = new BookManager('data/books.json', 'data/lists.json');

    // Pobranie danych
    $books = $bookManager->getBooks();
    $genres = $bookManager->getGenres();
    $series = $bookManager->getSeries();

    // Obsługa parametrów URL
    $sort = $_GET['sort'] ?? 'title'; // Sortowanie (domyślnie po tytule)
    $filterGenre = $_GET['genre'] ?? null; // Filtr gatunków

    // Sortowanie książek
    $books = $bookManager->sortBooks($books, $sort);

    // Filtrowanie książek
    if ($filterGenre) {
        $books = $bookManager->filterBooksByGenre($books, $filterGenre);
    }

    // Generowanie HTML
    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista książek</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container my-4">
    <h1 class="text-center mb-4">Lista książek</h1>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <!-- Sortowanie -->
        <div>
            <a href="?sort=title" class="btn btn-primary btn-sm">Sortuj po tytule</a>
            <a href="?sort=author" class="btn btn-secondary btn-sm">Sortuj po autorze</a>
        </div>
        <!-- Filtry gatunków -->
        <div>
            <form method="get" class="d-inline">
                <select name="genre" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Wszystkie gatunki</option>
HTML;

    foreach ($genres as $genre) {
        $selected = $filterGenre === $genre ? 'selected' : '';
        $html .= "<option value=\"$genre\" $selected>$genre</option>";
    }

    $html .= <<<HTML
                </select>
            </form>
        </div>
    </div>
    <div class="row">
HTML;

    // Wyświetlanie książek
    foreach ($books as $book) {
        $html .= <<<HTML
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">{$book['title']}</h5>
                    <h6 class="card-subtitle text-muted">{$book['author']}</h6>
                    <p class="card-text">Gatunek: {$book['genre']}</p>
                    <p class="card-text">Seria: {$book['series']}</p>
                    <a href="https://example.com/{$book['id']}" class="btn btn-link">Zobacz więcej</a>
                    <a href="http://example.com/download/{$book['id']}" class="btn btn-outline-primary btn-sm">Pobierz</a>
                </div>
            </div>
        </div>
HTML;
    }

    $html .= <<<HTML
    </div>
    <footer class="text-center mt-4">
        <p>Strona wygenerowana: <?php echo date('Y-m-d H:i:s'); ?></p>
    </footer>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
HTML;

    // Zapis pliku index.php
    file_put_contents('index.php', $html);

    logMessage('Plik index.php został wygenerowany pomyślnie.');
    echo json_encode(['status' => 'success', 'message' => 'Plik index.php został wygenerowany.']);
} catch (Exception $e) {
    logMessage('Błąd: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}