
<?php
session_start();
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

    public function loadJson($path) {
        return json_decode(file_get_contents($path), true);
    }

    public function getUnprocessedBooks() {
        $allFiles = is_dir('_ksiazki') ? array_map('basename', glob('_ksiazki/*')) : [];
        $processedFiles = array_column($this->booksData['books'], 'file_name');
        return array_diff($allFiles, $processedFiles);
    }

    private function getValidMetadataStructure() {
        return [
            'file_name' => '',
            'title' => '',
            'authors' => [
                ['first_name' => '', 'last_name' => '']
            ],
            'genres' => [],
            'series' => null,
            'series_position' => null
        ];
    }

    private function validateBookMetadata($book) {
        $template = $this->getValidMetadataStructure();
        $required = ['file_name', 'title', 'authors'];
        
        foreach ($required as $field) {
            if (!isset($book[$field]) || empty($book[$field])) {
                return false;
            }
        }

        if (!is_array($book['authors'])) return false;
        
        foreach ($book['authors'] as $author) {
            if (!isset($author['first_name']) || !isset($author['last_name'])) {
                return false;
            }
        }

        if (!isset($book['genres']) || !is_array($book['genres'])) {
            return false;
        }

        return true;
    }

    public function repairBookMetadata($book) {
        $template = $this->getValidMetadataStructure();
        
        if (!isset($book['genres'])) $book['genres'] = [];
        if (!isset($book['series'])) $book['series'] = null;
        if (!isset($book['series_position'])) $book['series_position'] = null;

        if (isset($book['authors']) && is_array($book['authors'])) {
            foreach ($book['authors'] as &$author) {
                if (is_string($author)) {
                    $parts = explode(',', $author);
                    $author = [
                        'last_name' => trim($parts[0] ?? ''),
                        'first_name' => trim($parts[1] ?? '')
                    ];
                }
            }
        }

        return $book;
    }

    public function getProcessedBooks() {
        $books = $this->booksData['books'];
        foreach ($books as &$book) {
            if (!$this->validateBookMetadata($book)) {
                $book = $this->repairBookMetadata($book);
            }
        }
        return $books;
    }

    public function generateHtml() {
        $books = $this->getProcessedBooks();
        usort($books, function($a, $b) {
            $timeA = filemtime('_ksiazki/' . $a['file_name']);
            $timeB = filemtime('_ksiazki/' . $b['file_name']);
            return $timeB - $timeA;
        });

        $html = '<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Biblioteka E-booków</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2em; line-height: 1.6; }
        .book-list { list-style-type: none; padding: 0; }
        .book-item { margin: 0.5em 0; }
        a { text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>Biblioteka E-booków</h1>
    <ul class="book-list">';

        foreach ($books as $book) {
            $authors = array_map(function($author) {
                return $author['last_name'] . ', ' . $author['first_name'];
            }, $book['authors']);

            $html .= sprintf(
                '<li class="book-item"><a href="http://_ksiazki/%s">🔗</a> <a href="https://_ksiazki/%s">%s</a> [%s]</li>',
                urlencode($book['file_name']),
                urlencode($book['file_name']),
                htmlspecialchars($book['title']),
                htmlspecialchars(implode('; ', $authors))
            );
        }

        $html .= '</ul></body></html>';
        
        file_put_contents('ksiazki.html', $html);
    }

    public function getStats() {
        $filesInFolder = is_dir('_ksiazki') ? count(glob('_ksiazki/*')) : 0;
        $booksWithMetadata = count($this->booksData['books']);
        return [
            'files' => $filesInFolder,
            'metadata' => $booksWithMetadata
        ];
    }
}

$manager = new BookManager();
$stats = $manager->getStats();
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
        <div class="alert alert-info d-flex justify-content-between align-items-center">
            <div>Books in folder: <?= $stats['files'] ?>, Books with metadata: <?= $stats['metadata'] ?></div>
            <form method="post">
                <button type="submit" name="generate_html" class="btn btn-primary">Generate HTML</button>
            </form>
        </div>
        <?php 
        if (isset($_POST['generate_html'])) {
            $manager->generateHtml();
            echo '<div class="alert alert-success">HTML file generated successfully!</div>';
        }
        ?>

        <h2>Books Without Metadata</h2>
        <div class="list-group mb-4">
            <?php foreach ($manager->getUnprocessedBooks() as $file): ?>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><?= htmlspecialchars($file) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <h2>Books With Metadata</h2>
        <div class="list-group">
            <?php foreach ($manager->getProcessedBooks() as $book): ?>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><?= htmlspecialchars($book['title']) ?> by 
                        <?php foreach ($book['authors'] as $author): ?>
                            <?= htmlspecialchars($author['last_name'] . ', ' . $author['first_name']) ?>;
                        <?php endforeach; ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="mt-4">
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">books.json</h5>
                    </div>
                    <div class="card-body">
                        <pre class="mb-0"><?= json_encode($manager->loadJson('data/books.json'), JSON_PRETTY_PRINT) ?></pre>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">lists.json</h5>
                    </div>
                    <div class="card-body">
                        <pre class="mb-0"><?= json_encode($manager->loadJson('data/lists.json'), JSON_PRETTY_PRINT) ?></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
