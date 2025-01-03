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
$books = $booksData['books'] ?? [];

// Generowanie pliku index.php
$indexContent = <<<HTML
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista książek</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container my-4">
    <h1 class="text-center mb-4">Lista książek</h1>

    <!-- Filtry i sortowanie -->
    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <!-- Sortowanie -->
            <div>
                <a href="?sort=title" class="btn btn-primary btn-sm">Sortuj po tytule</a>
                <a href="?sort=author" class="btn btn-secondary btn-sm">Sortuj po autorze</a>
            </div>

            <!-- Filtry gatunków -->
            <form method="get" class="d-inline">
                <select name="genre" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Wszystkie gatunki</option>
HTML;

// Dodanie gatunków do selektora
$genres = array_unique(array_merge(...array_column($books, 'genres')));
sort($genres);

foreach ($genres as $genre) {
    $selected = (isset($_GET['genre']) && $_GET['genre'] === $genre) ? 'selected' : '';
    $indexContent .= "<option value=\"$genre\" $selected>$genre</option>";
}

$indexContent .= <<<HTML
                </select>
                <a href="?" class="btn btn-link btn-sm">Resetuj filtr</a>
            </form>
        </div>
    </div>

    <!-- Lista książek -->
    <div class="row">
HTML;

// Sortowanie i filtrowanie
$sort = $_GET['sort'] ?? null;
$filterGenre = $_GET['genre'] ?? null;
$filterSeries = $_GET['series'] ?? null;

if ($filterGenre) {
    $books = array_filter($books, function ($book) use ($filterGenre) {
        return in_array($filterGenre, $book['genres']);
    });
}

if ($filterSeries) {
    $books = array_filter($books, function ($book) use ($filterSeries) {
        return $book['series'] === $filterSeries;
    });
    usort($books, function ($a, $b) {
        return ($a['series_position'] ?? 0) <=> ($b['series_position'] ?? 0);
    });
} elseif ($sort === 'author') {
    usort($books, function ($a, $b) {
        $authorA = $a['authors'][0]['last_name'] ?? '';
        $authorB = $b['authors'][0]['last_name'] ?? '';
        return strcmp($authorA, $authorB);
    });
} elseif ($sort === 'title') {
    usort($books, function ($a, $b) {
        return strcmp($a['title'], $b['title']);
    });
}

// Generowanie kart książek
foreach ($books as $book) {
    $authors = implode(', ', array_map(function ($author) {
        return $author['first_name'] . ' ' . $author['last_name'];
    }, $book['authors']));

    $genres = implode(', ', $book['genres']);
    $series = $book['series'] ? "<a href=\"?series={$book['series']}\" class=\"text-decoration-none\">{$book['series']} ({$book['series_position']})</a>" : 'Brak';

    $indexContent .= <<<HTML
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">
                        <a href="https://example.com/{$book['file_name']}" class="text-decoration-none">{$book['title']}</a>
                        <a href="http://example.com/{$book['file_name']}" class="btn btn-sm btn-outline-primary">[http]</a>
                    </h5>
                    <h6 class="card-subtitle text-muted">Autor: {$authors}</h6>
                    <p class="card-text">Gatunek: {$genres}</p>
                    <p class="card-text">Seria: {$series}</p>
                </div>
            </div>
        </div>
HTML;
}

$indexContent .= <<<HTML
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
HTML;

// Zapisanie pliku index.php
file_put_contents('index.php', $indexContent);
echo json_encode(['status' => 'success', 'message' => 'Plik index.php został wygenerowany.']);