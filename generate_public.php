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

// Generowanie pliku index.php
$indexContent = <<<PHP
<?php

// Dane książek
\$books = json_decode('
PHP;

$indexContent .= json_encode($books, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$indexContent .= <<<PHP
', true);

// Dynamiczne pobieranie domeny
\$protocol = (!empty(\$_SERVER['HTTPS']) && \$_SERVER['HTTPS'] !== 'off') ? "https" : "http";
\$domain = \$protocol . "://" . \$_SERVER['HTTP_HOST'];

// Parametry URL
\$sort = \$_GET['sort'] ?? null;
\$filterGenre = \$_GET['genre'] ?? null;
\$filterSeries = \$_GET['series'] ?? null;

// Filtry i sortowanie
\$filteredBooks = \$books;

if (\$filterGenre) {
    \$filteredBooks = array_filter(\$filteredBooks, function (\$book) use (\$filterGenre) {
        return in_array(\$filterGenre, \$book['genres']);
    });
}

if (\$filterSeries) {
    \$filteredBooks = array_filter(\$filteredBooks, function (\$book) use (\$filterSeries) {
        return \$book['series'] === \$filterSeries;
    });
    usort(\$filteredBooks, function (\$a, \$b) {
        return ((int)(\$a['series_position'] ?? 0)) <=> ((int)(\$b['series_position'] ?? 0));
    });
} elseif (\$sort === 'author') {
    usort(\$filteredBooks, function (\$a, \$b) {
        \$authorA = \$a['authors'][0]['last_name'] ?? '';
        \$authorB = \$b['authors'][0]['last_name'] ?? '';
        return strcmp(\$authorA, \$authorB);
    });
} elseif (\$sort === 'title') {
    usort(\$filteredBooks, function (\$a, \$b) {
        return strcmp(\$a['title'], \$b['title']);
    });
}

// Generowanie informacji o filtrze
\$filterDescription = "Wszystkie książki";
if (\$filterGenre) {
    \$filterDescription = "Książki z gatunku: " . htmlspecialchars(\$filterGenre);
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista książek</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
        /* Poprawiona czytelność dla Kindle */
        body {
            font-family: Georgia, serif;
            font-size: 1.1em; /* Około 24px */
            line-height: 1.1;
            margin: 0;
            padding: 0;
        }

        h1 {
            font-size: 2em; /* Około 32px */
            margin-bottom: 1rem;
        }

        .card {
            margin-bottom: 0.6rem;
            border: 1px solid #ddd;
        }

        .card-body {
            padding: 0.5rem;
        }

        .card-title {
            font-size: 1.3em; /* Około 24px */
        }

        .btn-genre {
            font-size: 1em; /* Około 16px */
            margin: 0.3rem;
        }

        .row {
            margin: 0; /* Usuń marginesy dla rzędu */
        }

        .col-12 {
            padding: 0.5rem;
        }

        /* Ustaw karty na pełną szerokość */
        .card {
            width: 100%;
        }
    </style>
</head>
<body>
<div class="container my-4">
    <h1 class="text-center mb-4">Lista książek</h1>

    <!-- Filtry gatunków -->
    <div class="mb-3">
        <div class="d-flex flex-wrap">
            <a href="?" class="btn btn-outline-secondary btn-genre">Wszystkie</a>
<?php
\$genres = array_unique(array_merge(...array_filter(array_column(\$books, 'genres'))));
sort(\$genres);

foreach (\$genres as \$genre) {
    \$active = (isset(\$_GET['genre']) && \$_GET['genre'] === \$genre) ? 'btn-primary' : 'btn-outline-primary';
    echo "<a href=\"?genre=" . htmlspecialchars(\$genre) . "\" class=\"btn \$active btn-genre\">" . htmlspecialchars(\$genre) . "</a>";
}
?>
        </div>
    </div>

    <!-- Sortowanie -->
    <div class="mb-3">
        <a href="?sort=title" class="btn btn-primary btn-sm me-2">Sortuj po tytule</a>
        <a href="?sort=author" class="btn btn-secondary btn-sm">Sortuj po autorze</a>
    </div>

    <!-- Informacja o filtrach -->
    <div class="alert alert-info">
        <strong>Wybrano:</strong> <?php echo \$filterDescription; ?>
    </div>

    <!-- Lista książek -->
    <div class="row">
<?php
foreach (\$filteredBooks as \$book) {
    \$authors = implode(', ', array_map(function (\$author) {
        return htmlspecialchars(\$author['first_name'] . ' ' . \$author['last_name']);
    }, \$book['authors']));

    \$genres = implode(', ', array_map('htmlspecialchars', \$book['genres']));
    \$series = \$book['series'] ? "<a href=\"?series=" . htmlspecialchars(\$book['series']) . "\">" . htmlspecialchars(\$book['series']) . "</a> (" . htmlspecialchars(\$book['series_position']) . ")" : '';

    \$httpsLink = "\$domain/_ksiazki/" . htmlspecialchars(\$book['file_name']);
    \$httpLink = str_replace('https://', 'http://', \$httpsLink);

    echo <<<HTML
        <div class="col-12">
            <div class="card">
                 <div class="card-header">{\$genres}</div>
                <div class="card-body">
                    <h5 class="card-title" style="display: flex; justify-content: space-between; align-items: center;">
                        <a href="\$httpsLink"style="text-decoration: none; flex-grow: 1;">{\$book['title']}</a>
                        <a href="\$httpLink" style="margin-left: auto; padding: 0.2em 0.5em; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;">http</a>
                    </h5>
                        if (!empty(\$series)) {
        echo <<<HTML
        <h6 class="card-subtitle mb-2 text-body-secondary">{\$series}</h6>
HTML;
                    <p class="card-text" style="display: flex; justify-content: space-between;">
                        <span>Autor: {\$authors}</span>
                    </p>
HTML;


    }

    echo <<<HTML
                </div>
            </div>
        </div>
HTML;
}
?>
    </div>
</div>
<footer class="text-center mt-5">
    <p>Strona wygenerowana: <?php echo "$generationTime"; ?></p>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
PHP;

// Zapisanie pliku index.php
if (file_put_contents('index.php', $indexContent) === false) {
    die("Błąd: Nie można zapisać pliku index.php. Sprawdź uprawnienia katalogu.");
}

echo json_encode(['status' => 'success', 'message' => 'Plik index.php został wygenerowany.']);