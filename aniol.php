
<?php
session_start();
$rootUrl = "http://" . $_SERVER['HTTP_HOST'];
$rootsUrl = "https://" . $_SERVER['HTTP_HOST'];
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

    private function getValidMetadataStructure() {
        return [
            'file_name' => '',
            'title' => '',
            'authors' => [
                ['first_name' => '', 'last_name' => '']
            ],
            'genres' => [],
            'series' => null,
            'series_position' => null,
            'upload_date' => 0
        ];
    }

    private function validateBookMetadata($book) {
        // Check if required fields exist and contain non-empty strings
        $required = ['file_name', 'title', 'upload_date'];
        foreach ($required as $field) {
            if (!isset($book[$field])) return false;
            if ($field !== 'upload_date' && (!is_string($book[$field]) || trim($book[$field]) === '')) return false;
        }

        // Verify authors array exists and is not empty
        if (!isset($book['authors']) || !is_array($book['authors']) || count($book['authors']) === 0) {
            return false;
        }

        // Check if at least one author has both first and last name as non-empty strings
        $hasValidAuthor = false;
        foreach ($book['authors'] as $author) {
            if (isset($author['first_name']) && isset($author['last_name']) &&
                is_string($author['first_name']) && is_string($author['last_name']) &&
                trim($author['first_name']) !== '' && trim($author['last_name']) !== '') {
                $hasValidAuthor = true;
                break;
            }
        }
        if (!$hasValidAuthor) {
            return false;
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

    public function generateHtml() {
        $books = [];
        if (is_dir('_ksiazki')) {
            $files = array_map('basename', glob('_ksiazki/*'));
            foreach ($files as $file) {
                $books[] = [
                    'file_name' => $file,
                    'upload_date' => filemtime('_ksiazki/' . $file)
                ];
            }
        }

        usort($books, function($a, $b) {
            return $b['upload_date'] - $a['upload_date'];
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
            $html .= sprintf(
                '<li class="book-item"><a href="%s/_ksiazki/%s">[🔗http]</a> <a href="%s/_ksiazki/%s">%s</a></li>',
                $rootUrl,
                urlencode($book['file_name']),
                $rootsUrl,
                urlencode($book['file_name']),
                htmlspecialchars($book['file_name'])
            );
        }

        $html .= '</ul></body></html>';
        
        file_put_contents('ksiazki.html', $html);
    }

    public function getStats() {
        $filesInFolder = is_dir('_ksiazki') ? count(glob('_ksiazki/*')) : 0;
        return [
            'files' => $filesInFolder,
            'metadata' => 0
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
    <style>
        .modal-dialog { max-width: 700px; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="alert alert-info d-flex justify-content-between align-items-center">
            <div>Books in folder: <?= $stats['files'] ?></div>
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

        <h2 class="mt-4">Books Metadata</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>File Name</th>
                    <th>Title</th>
                    <th>Authors</th>
                    <th>Genres</th>
                    <th>Series</th>
                    <th>Upload Date</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $books = [];
                if (is_dir('_ksiazki')) {
                    $files = array_map('basename', glob('_ksiazki/*'));
                    foreach ($files as $file) {
                        $bookData = [
                            'file_name' => $file,
                            'upload_date' => filemtime('_ksiazki/' . $file)
                        ];
                        
                        // Look for metadata in books.json
                        foreach ($manager->loadJson('data/books.json')['books'] as $book) {
                            if ($book['file_name'] === $file) {
                                $bookData = array_merge($bookData, $book);
                                break;
                            }
                        }
                        $books[] = $bookData;
                    }
                }

                foreach ($books as $book) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($book['file_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($book['title'] ?? 'No title') . '</td>';
                    echo '<td>';
                    if (isset($book['authors']) && is_array($book['authors'])) {
                        foreach ($book['authors'] as $author) {
                            if (is_array($author)) {
                                echo htmlspecialchars($author['first_name'] . ' ' . $author['last_name']) . '<br>';
                            } else {
                                echo htmlspecialchars($author) . '<br>';
                            }
                        }
                    } else {
                        echo 'No authors';
                    }
                    echo '</td>';
                    echo '<td>' . (isset($book['genres']) ? htmlspecialchars(implode(', ', $book['genres'])) : 'No genres') . '</td>';
                    echo '<td>' . (isset($book['series']) ? htmlspecialchars($book['series']) . 
                         (isset($book['series_position']) ? ' #' . htmlspecialchars($book['series_position']) : '') : 'No series') . '</td>';
                    echo '<td>' . date('Y-m-d H:i:s', $book['upload_date']) . '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
