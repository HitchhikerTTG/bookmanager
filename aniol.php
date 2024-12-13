
<?php
class BookManager {
    private $booksData;
    private $listsData;
    
    public function __construct() {
        $this->ensureDataFilesExist();
        $this->booksData = $this->loadJson('data/books.json');
        $this->listsData = $this->loadJson('data/lists.json');
    }

    private function ensureDataFilesExist() {
        if (!is_dir('data')) {
            mkdir('data');
        }
        if (!file_exists('data/books.json')) {
            file_put_contents('data/books.json', json_encode(['books' => []]));
        }
        if (!file_exists('data/lists.json')) {
            file_put_contents('data/lists.json', json_encode([
                'authors' => [],
                'genres' => [],
                'series' => []
            ]));
        }
    }

    private function loadJson($path) {
        return json_decode(file_get_contents($path), true);
    }

    private function saveJson($path, $data) {
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function addBook($file, $title, $authors, $genres, $series = null, $seriesPosition = null) {
        $book = [
            'file_name' => basename($file),
            'date_uploaded' => date('Y-m-d H:i:s'),
            'title' => $title,
            'authors' => $authors,
            'genres' => $genres,
            'series' => $series,
            'series_position' => $seriesPosition
        ];
        
        $this->booksData['books'][] = $book;
        $this->saveJson('data/books.json', $this->booksData);
        $this->updateLists($authors, $genres, $series);
        $this->generateHtml();
    }

    private function updateLists($authors, $genres, $series) {
        foreach ($authors as $author) {
            if (!in_array($author, $this->listsData['authors'])) {
                $this->listsData['authors'][] = $author;
            }
        }
        foreach ($genres as $genre) {
            if (!in_array($genre, $this->listsData['genres'])) {
                $this->listsData['genres'][] = $genre;
            }
        }
        if ($series && !in_array($series, $this->listsData['series'])) {
            $this->listsData['series'][] = $series;
        }
        $this->saveJson('data/lists.json', $this->listsData);
    }

    public function generateHtml() {
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>E-Book Library</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2em; }
        .book { border: 1px solid #ddd; padding: 1em; margin: 1em 0; }
    </style>
</head>
<body>
    <h1>E-Book Library</h1>';

        foreach ($this->booksData['books'] as $book) {
            $html .= '<div class="book">';
            $html .= '<h2>' . htmlspecialchars($book['title']) . '</h2>';
            $html .= '<p>Authors: ' . htmlspecialchars(implode(', ', $book['authors'])) . '</p>';
            $html .= '<p>Genres: ' . htmlspecialchars(implode(', ', $book['genres'])) . '</p>';
            if ($book['series']) {
                $html .= '<p>Series: ' . htmlspecialchars($book['series']) . 
                        ' (#' . htmlspecialchars($book['series_position']) . ')</p>';
            }
            $html .= '<p>Uploaded: ' . htmlspecialchars($book['date_uploaded']) . '</p>';
            $html .= '<p><a href="_ksiazki/' . htmlspecialchars($book['file_name']) . '">Download</a></p>';
            $html .= '</div>';
        }

        $html .= '</body></html>';
        file_put_contents('ksiazki.html', $html);
    }
}

// Initialize the book manager
$manager = new BookManager();

// Handle file uploads and form submissions here
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add your form handling logic here
}
?>

<form method="POST" enctype="multipart/form-data">
    <h2>Add New Book</h2>
    <p><input type="file" name="book_file" required></p>
    <p><input type="text" name="title" placeholder="Title" required></p>
    <p><input type="text" name="authors" placeholder="Authors (comma separated)" required></p>
    <p><input type="text" name="genres" placeholder="Genres (comma separated)" required></p>
    <p><input type="text" name="series" placeholder="Series (optional)"></p>
    <p><input type="number" name="series_position" placeholder="Position in series"></p>
    <p><input type="submit" value="Add Book"></p>
</form>
