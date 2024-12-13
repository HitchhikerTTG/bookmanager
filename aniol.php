
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
        <h1>E-Book Library</h1>';
        
        if (empty($this->booksData['books'])) {
            $html .= '<div class="alert alert-info">No books available yet.</div>';
        } else {
            $html .= '<div class="row">';

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
            $html .= '</div>';
        }
        
        $html .= '</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';
        file_put_contents('ksiazki.html', $html);
    }
}

$manager = new BookManager();
$stats = $manager->getStats();

if (isset($_POST['generate_html'])) {
    $manager->generateHtml();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Pagination setup
$unprocessedBooks = $manager->getUnprocessedBooks();
$processedBooks = $manager->booksData['books'];

$unprocessedPage = max(1, intval($_GET['unprocessed_page'] ?? 1));
$processedPage = max(1, intval($_GET['processed_page'] ?? 1));

$unprocessedTotalPages = ceil(count($unprocessedBooks) / 10);
$processedTotalPages = ceil(count($processedBooks) / 10);

$unprocessedStartIndex = ($unprocessedPage - 1) * 10;
$processedStartIndex = ($processedPage - 1) * 10;

$currentUnprocessed = array_slice($unprocessedBooks, $unprocessedStartIndex, 10);
$currentProcessed = array_slice($processedBooks, $processedStartIndex, 10);
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

        <?php if (!empty($currentUnprocessed)): ?>
            <h2>Unprocessed Books</h2>
            <div class="list-group mb-4">
                <?php foreach ($currentUnprocessed as $file): ?>
                    <div class="list-group-item"><?= htmlspecialchars($file) ?></div>
                <?php endforeach; ?>
            </div>
            <?php if ($unprocessedTotalPages > 1): ?>
                <nav class="mb-4">
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $unprocessedTotalPages; $i++): ?>
                            <li class="page-item <?= $i === $unprocessedPage ? 'active' : '' ?>">
                                <a class="page-link" href="?unprocessed_page=<?= $i ?>&processed_page=<?= $processedPage ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($currentProcessed)): ?>
            <h2>Processed Books</h2>
            <div class="list-group mb-4">
                <?php foreach ($currentProcessed as $book): ?>
                    <div class="list-group-item">
                        <h5><?= htmlspecialchars($book['title']) ?></h5>
                        <p class="mb-1">Authors: <?= htmlspecialchars(implode(', ', $book['authors'])) ?></p>
                        <small>File: <?= htmlspecialchars($book['file_name']) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($processedTotalPages > 1): ?>
                <nav>
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $processedTotalPages; $i++): ?>
                            <li class="page-item <?= $i === $processedPage ? 'active' : '' ?>">
                                <a class="page-link" href="?processed_page=<?= $i ?>&unprocessed_page=<?= $unprocessedPage ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
