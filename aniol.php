
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

    public function getUnprocessedBooks() {
        $allFiles = is_dir('_ksiazki') ? array_map('basename', glob('_ksiazki/*')) : [];
        $processedFiles = array_column($this->booksData['books'], 'file_name');
        return array_diff($allFiles, $processedFiles);
    }

    public function getProcessedBooks() {
        return $this->booksData['books'];
    }

    public function saveMetadata($fileName, $title, $authors) {
        $bookData = [
            'file_name' => $fileName,
            'title' => $title,
            'authors' => array_map('trim', explode(',', $authors)),
            'date_uploaded' => date('Y-m-d H:i:s')
        ];

        $this->booksData['books'][] = $bookData;
        $this->saveJson('data/books.json', $this->booksData);
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
        <div class="row">
            <div class="col">
                <p>Total Books: ' . $this->getStats()['files'] . '</p>
                <p>Books with Metadata: ' . $this->getStats()['metadata'] . '</p>
            </div>
        </div>
    </div>
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

if (isset($_POST['save_metadata'])) {
    $manager->saveMetadata(
        $_POST['file_name'],
        $_POST['title'],
        $_POST['authors']
    );
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
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

        <h2>Books Without Metadata</h2>
        <div class="list-group mb-4">
            <?php foreach ($manager->getUnprocessedBooks() as $file): ?>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><?= htmlspecialchars($file) ?></span>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addMetadataModal" data-file="<?= htmlspecialchars($file) ?>">
                            Add Metadata
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <h2>Books With Metadata</h2>
        <div class="list-group">
            <?php foreach ($manager->getProcessedBooks() as $book): ?>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><?= htmlspecialchars($book['title']) ?> by <?= htmlspecialchars(implode(', ', $book['authors'])) ?></span>
                        <button type="button" class="btn btn-secondary btn-sm" disabled>Edit</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="addMetadataModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Metadata</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="file_name" id="modalFileName">
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Authors (comma-separated)</label>
                            <input type="text" class="form-control" name="authors" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="save_metadata" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('addMetadataModal').addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var fileName = button.getAttribute('data-file');
            document.getElementById('modalFileName').value = fileName;
        });
    </script>
</body>
</html>
