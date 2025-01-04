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
        return false;
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
} else {
    // Domyślne sortowanie według daty dodania
    usort(\$filteredBooks, function (\$a, \$b) {
        return (\$b['upload_date'] ?? 0) <=> (\$a['upload_date'] ?? 0);
    });
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista książek</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Georgia, serif;
            font-size: 1.3em;
        }
        .btn-genre, .btn-author {
            font-size: 1.2em;
            margin: 0.5rem;
        }
    </style>
</head>
<body>
<div class="container my-4">
    <h3 class="text-center mb-4">e-biblioteczka</h3>

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

    <!-- Filtry autorów -->
    <?php
    if (\$filterGenre || \$sort === 'author') {
        \$filteredAuthors = [];
        foreach (\$filteredBooks as \$book) {
            foreach (\$book['authors'] as \$author) {
                \$authorFullName = \$author['first_name'] . ' ' . \$author['last_name'];
                if (!in_array(\$authorFullName, \$filteredAuthors)) {
                    \$filteredAuthors[] = \$authorFullName;
                }
            }
        }

        sort(\$filteredAuthors);

        echo '<div class="mb-3">';
        echo '<strong>Filtruj wg autora:</strong><br>';
        foreach (\$filteredAuthors as \$author) {
            \$active = (isset(\$_GET['author']) && \$_GET['author'] === \$author) ? 'btn-primary' : 'btn-outline-primary';
            echo "<a href=\"?genre=\$filterGenre&author=" . urlencode(\$author) . "\" class=\"btn \$active btn-author\">" . htmlspecialchars(\$author) . "</a>";
        }
        echo '</div>';
    }
    ?>

    <!-- Lista książek -->
    <div class="row">
        <?php
        foreach (\$filteredBooks as \$book) {
            \$authors = implode(', ', array_map(function (\$author) {
                \$authorFullName = \$author['first_name'] . ' ' . \$author['last_name'];
                return "<a href=\"?author=" . urlencode(\$authorFullName) . "\">$authorFullName</a>";
            }, \$book['authors']));

            \$genres = implode(', ', array_map('htmlspecialchars', \$book['genres']));
            \$series = \$book['series'] ? "<a href=\"?series=" . htmlspecialchars(\$book['series']) . "\">" . htmlspecialchars(\$book['series']) . "</a>" : '';

            echo <<<HTML
            <div class="col-12">
                <div class="card mb-3">
                <div class="card-header" style="text-aling:right">{\$genres}</div>
                    <div class="card-body">
                        <h5 class="card-title"><a href="{\$httpsLink}" class="text-decoration-none">{$book['title']}</a></h5>
                        <p class="card-text">Autor: {\$[authors]}</p>
                        
                        <p class="card-text">Seria: {\$series}</p>
                    </div>
                    <div class="card-footer" style="text-aling:right"><small><a href="{\$httpLink}" class="card-link">(HTTP)</a></small></div>
                </div>
            </div>
            HTML;
        }
        ?>
    </div>
</div>
</body>
</html>
PHP;

// Zapisanie pliku index.php
if (file_put_contents('index.php', $indexContent) === false) {
    die("Błąd: Nie można zapisać pliku index.php. Sprawdź uprawnienia katalogu.");
}

echo json_encode(['status' => 'success', 'message' => 'Plik index.php został wygenerowany.']);