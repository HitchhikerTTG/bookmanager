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

// Wczytanie danych
$booksFile = 'data/books.json';
$listsFile = 'data/lists.json';

$booksData = loadJsonFile($booksFile);
$lists = loadJsonFile($listsFile);

// Pobranie książek
$books = $booksData['books'] ?? [];

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Podgląd danych książek</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container my-4">
    <h1 class="mb-4 text-center">Podgląd danych książek</h1>

    <!-- Wyświetlenie książek -->
    <h2>Książki</h2>
    <table class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Tytuł</th>
            <th>Autorzy</th>
            <th>Gatunki</th>
            <th>Seria</th>
            <th>Data przesłania</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($books as $book): ?>
            <tr>
                <td><?= htmlspecialchars($book['title']) ?></td>
                <td>
                    <?php 
                    $authors = array_map(function($author) {
                        return $author['first_name'] . ' ' . $author['last_name'];
                    }, $book['authors']);
                    echo htmlspecialchars(implode(', ', $authors));
                    ?>
                </td>
                <td><?= htmlspecialchars(implode(', ', $book['genres'])) ?></td>
                <td><?= htmlspecialchars($book['series'] ?? 'Brak') ?></td>
                <td><?= htmlspecialchars(date('Y-m-d', $book['upload_date'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Wyświetlenie list -->
    <h2>Listy</h2>
    <table class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Typ</th>
            <th>Dane</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($lists as $key => $list): ?>
            <tr>
                <td><?= htmlspecialchars($key) ?></td>
                <td><?= htmlspecialchars(implode(', ', $list)) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>