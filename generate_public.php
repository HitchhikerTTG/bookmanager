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

// Domenę można zmienić na rzeczywistą domenę witryny
$domain = "https://twojadomena";

// Data i godzina generowania strony
$generationTime = date('Y-m-d H:i:s');

// Parametry URL
$sort = $_GET['sort'] ?? null;
$filterGenre = $_GET['genre'] ?? null;
$filterSeries = $_GET['series'] ?? null;

// Filtry i sortowanie
$filteredBooks = $books;

if ($filterGenre) {
    $filteredBooks = array_filter($filteredBooks, function ($book) use ($filterGenre) {
        return in_array($filterGenre, $book['genres']);
    });
}

if ($filterSeries) {
    $filteredBooks = array_filter($filteredBooks, function ($book) use ($filterSeries) {
        return $book['series'] === $filterSeries;
    });
    usort($filteredBooks, function ($a, $b) {
        return ($a['series_position'] ?? 0) <=> ($b['series_position'] ?? 0);
    });
} elseif ($sort === 'author') {
    usort($filteredBooks, function ($a, $b) {
        $authorA = $a['authors'][0]['last_name'] ?? '';
        $authorB = $b['authors'][0]['last_name'] ?? '';
        return strcmp($authorA, $authorB);
    });
} elseif ($sort === 'title') {
    usort($filteredBooks, function ($a, $b) {
        return strcmp($a['title'], $b['title']);
    });
}

// Generowanie informacji o filtrze
$filterDescription = "Wszystkie książki";
if ($filterGenre) {
    $filterDescription = "Książki z gatunku: $filterGenre";
}
if ($filterSeries) {
    $filterDescription = "Książki z serii: $filterSeries";
}

if (empty($filteredBooks)) {
    $filterDescription .= " (Brak wyników)";
}

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

    <!-- Filtry gatunków -->
    <div class="mb-3">
        <div class="d-flex flex-wrap">
            <a href="?" class="btn btn-outline-secondary btn-sm me-2 mb-2">Wszystkie</a>
HTML;

// Dodanie przycisków gatunków
$genres = array_unique(array_merge(...array_column($books, 'genres')));
sort($genres);

foreach ($genres as $genre) {
    $active = (isset($_GET['genre']) && $_GET['genre'] === $genre) ? 'btn-primary' : 'btn-outline-primary';
    $indexContent .= "<a href=\"?genre=$genre\" class=\"btn $active btn-sm me-2 mb-2\">$genre</a>";
}

$indexContent .= <<<HTML
        </div>
    </div>

    <!-- Sortowanie -->
    <div class="mb-3">
        <a href="?sort=title" class="btn btn-primary btn-sm me-2">Sortuj po tytule</a>
        <a href="?sort=author" class="btn btn-secondary btn-sm">Sortuj po autorze</a>
    </div>

    <!-- Informacja o filtrach -->
    <div class="alert alert-info">
        <strong>Wybrano:</strong> $filterDescription
    </div>

    <!-- Lista książek -->
    <div class="row">
HTML;

// Generowanie kart książek
foreach ($filteredBooks as $book) {
    $authors = implode(', ', array_map(function ($author) {
        return $author['first_name'] . ' ' . $author['last_name'];
    }, $book['authors']));

    $genres = implode(', ', $book['genres']);
    $series = $book['series'] ? "<a href=\"?series={$book['series']}\" class=\"text-decoration-none\">{$book['series']} ({$book['series_position']})</a>" : 'Brak';
    $httpsLink = "$domain/_ksiazki/{$book['file_name']}";
    $httpLink = str_replace('https://', 'http://', $httpsLink);

    $indexContent .= <<<HTML
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">
                        <a href="$httpsLink" class="text-decoration-none">{$book['title']}</a>
                        <a href="$httpLink" class="btn btn-sm btn-outline-primary">[http]</a>
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

<!-- Stopka -->
<footer class="text-center mt-5">
    <p>Strona wygenerowana: $generationTime</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
HTML;

// Zapisanie pliku index.php
file_put_contents('index.php', $indexContent);
echo json_encode(['status' => 'success', 'message' => 'Plik index.php został wygenerowany.']);