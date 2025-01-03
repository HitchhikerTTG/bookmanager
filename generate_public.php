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

// Dynamiczne pobieranie domeny
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$domain = $protocol . "://" . $_SERVER['HTTP_HOST'];

// Data i godzina generowania strony
$generationTime = date('Y-m-d H:i:s');

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

    <!-- Informacja o filtrach -->
    <div class="alert alert-info">
        <strong>Wybrano:</strong> <?php echo isset(\$_GET['genre']) ? htmlspecialchars(\$_GET['genre']) : 'Wszystkie książki'; ?>
    </div>

    <!-- Lista książek -->
    <div class="row">
HTML;

// Generowanie kart książek
foreach ($books as $book) {
    $authors = implode(', ', array_map(function ($author) {
        return htmlspecialchars($author['first_name'] . ' ' . $author['last_name']);
    }, $book['authors']));

    $genres = implode(', ', array_map('htmlspecialchars', $book['genres']));
    $series = $book['series'] ? htmlspecialchars($book['series']) : 'Brak';
    $httpsLink = "$domain/_ksiazki/" . htmlspecialchars($book['file_name']);
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

<!-- Debugowanie parametrów URL -->
<footer class="text-center mt-5">
    <p>Strona wygenerowana: $generationTime</p>
    <pre><?php echo htmlspecialchars(json_encode(\$_GET, JSON_PRETTY_PRINT)); ?></pre>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
HTML;

// Zapisanie pliku index.php
if (file_put_contents('index.php', $indexContent) === false) {
    die("Błąd: Nie można zapisać pliku index.php. Sprawdź uprawnienia katalogu.");
}

echo json_encode(['status' => 'success', 'message' => 'Plik index.php został wygenerowany.']);