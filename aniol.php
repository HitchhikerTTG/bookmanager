
<?php
class BookManager {
    private $booksData;
    private $listsData;
    private $itemsPerPage = 10;
    
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

    public function getStats() {
        $filesInFolder = is_dir('_ksiazki') ? count(glob('_ksiazki/*')) : 0;
        $booksWithMetadata = count($this->booksData['books']);
        $lastUpdate = file_exists('ksiazki.html') ? date('Y-m-d H:i:s', filemtime('ksiazki.html')) : 'Never';
        return [
            'files' => $filesInFolder,
            'metadata' => $booksWithMetadata,
            'lastUpdate' => $lastUpdate
        ];
    }

    public function getUnprocessedBooks() {
        $allFiles = is_dir('_ksiazki') ? array_map('basename', glob('_ksiazki/*')) : [];
        $processedFiles = array_column($this->booksData['books'], 'file_name');
        return array_diff($allFiles, $processedFiles);
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>E-Book Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>E-Book Library</h1>
        <div class="row">';

        foreach ($this->booksData['books'] as $book) {
            $html .= '<div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title">' . htmlspecialchars($book['title']) . '</h2>
                        <p class="card-text">Authors: ' . htmlspecialchars(implode(', ', $book['authors'])) . '</p>
                        <p class="card-text">Genres: ' . htmlspecialchars(implode(', ', $book['genres'])) . '</p>';
            if ($book['series']) {
                $html .= '<p class="card-text">Series: ' . htmlspecialchars($book['series']) . 
                        ' (#' . htmlspecialchars($book['series_position']) . ')</p>';
            }
            $html .= '<p class="card-text"><small class="text-muted">Uploaded: ' . htmlspecialchars($book['date_uploaded']) . '</small></p>
                        <a href="_ksiazki/' . htmlspecialchars($book['file_name']) . '" class="btn btn-primary">Download</a>
                    </div>
                </div>
            </div>';
        }

        $html .= '</div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';
        file_put_contents('ksiazki.html', $html);
    }
}

$manager = new BookManager();
$stats = $manager->getStats();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['book_file']) && isset($_POST['title'])) {
        $file = $_FILES['book_file'];
        $title = $_POST['title'];
        $authors = array_map('trim', explode(',', $_POST['authors']));
        $genres = array_map('trim', explode(',', $_POST['genres']));
        $series = !empty($_POST['series']) ? $_POST['series'] : null;
        $seriesPosition = !empty($_POST['series_position']) ? $_POST['series_position'] : null;
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            $uploadPath = '_ksiazki/' . basename($file['name']);
            if (!is_dir('_ksiazki')) {
                mkdir('_ksiazki');
            }
            move_uploaded_file($file['tmp_name'], $uploadPath);
            $manager->addBook($file['name'], $title, $authors, $genres, $series, $seriesPosition);
        }
    } elseif (isset($_POST['generate_html'])) {
    $manager->generateHtml();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
    }
}

$currentTab = $_GET['tab'] ?? 'unprocessed';
$page = max(1, intval($_GET['page'] ?? 1));
$unprocessedBooks = $manager->getUnprocessedBooks();
$processedBooks = $manager->booksData['books'];

$totalPages = [
    'unprocessed' => ceil(count($unprocessedBooks) / 10),
    'processed' => ceil(count($processedBooks) / 10)
];

$startIndex = ($page - 1) * 10;
$currentBooks = $currentTab === 'unprocessed' 
    ? array_slice($unprocessedBooks, $startIndex, 10)
    : array_slice($processedBooks, $startIndex, 10);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Book Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="alert alert-info">
            Books in folder: <?= $stats['files'] ?>, Books with metadata: <?= $stats['metadata'] ?><br>
            Last HTML update: <?= $stats['lastUpdate'] ?>
            <form method="POST" class="d-inline ms-3">
                <button type="submit" name="generate_html" class="btn btn-primary btn-sm">Generate HTML</button>
            </form>
        </div>

        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <a class="nav-link <?= $currentTab === 'unprocessed' ? 'active' : '' ?>" 
                   href="?tab=unprocessed">Nieopisane</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentTab === 'processed' ? 'active' : '' ?>" 
                   href="?tab=processed">Gotowe</a>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active">
                <?php if ($currentTab === 'unprocessed'): ?>
                    <div class="list-group">
                        <?php foreach ($currentBooks as $file): ?>
                            <div class="list-group-item"><?= htmlspecialchars($file) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($currentBooks as $book): ?>
                            <div class="list-group-item">
                                <h5><?= htmlspecialchars($book['title']) ?></h5>
                                <p class="mb-1">Authors: <?= htmlspecialchars(implode(', ', $book['authors'])) ?></p>
                                <small>File: <?= htmlspecialchars($book['file_name']) ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($totalPages[$currentTab] > 1): ?>
                    <nav class="mt-3">
                        <ul class="pagination">
                            <?php for ($i = 1; $i <= $totalPages[$currentTab]; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?tab=<?= $currentTab ?>&page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-body">
                <h2 class="card-title">Add New Book</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Book File</label>
                        <input type="file" class="form-control" name="book_file" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Authors (comma separated)</label>
                        <input type="text" class="form-control" name="authors" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Genres (comma separated)</label>
                        <input type="text" class="form-control" name="genres" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Series (optional)</label>
                        <input type="text" class="form-control" name="series">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Position in series</label>
                        <input type="number" class="form-control" name="series_position">
                    </div>
                    <button type="submit" class="btn btn-primary">Add Book</button>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
