
<?php
session_start();
$rootUrl = "http://" . $_SERVER['HTTP_HOST'];
$rootsUrl = "https://" . $_SERVER['HTTP_HOST'];

if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
class BookManager {
    private $booksData;
    private $listsData;
    private $token;
    
    const ITEMS_PER_PAGE = 10;
    
    private function validateToken() {
        return isset($_POST['token']) && $_POST['token'] === $_SESSION['csrf_token'];
    }
    
    private function generateToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $this->token = $_SESSION['csrf_token'];
    }
    
    private function sanitizeInput($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }
    
    private function validateFilePath($path) {
        return !preg_match('/\.\./', $path) && strpos($path, '_ksiazki/') === 0;
    }
    
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
        static $cache = [];
        
        if (!isset($cache[$path])) {
            if (!file_exists($path)) {
                throw new RuntimeException("JSON file not found: $path");
            }
            $content = file_get_contents($path);
            if ($content === false) {
                throw new RuntimeException("Failed to read JSON file: $path");
            }
            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException("Invalid JSON in file: $path");
            }
            $cache[$path] = $data;
        }
        return $cache[$path];
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
        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($successMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
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
                    <th>Author First Name</th>
                    <th>Author Last Name</th>
                    <th>Genres</th>
                    <th>Series</th>
                    <th>Upload Date</th>
                    <th>Actions</th>
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
                    if (isset($book['authors']) && is_array($book['authors']) && !empty($book['authors'])) {
                        $author = $book['authors'][0];
                        if (is_array($author)) {
                            echo htmlspecialchars($author['first_name'] ?? 'No first name');
                        }
                    } else {
                        echo 'No first name';
                    }
                    echo '</td>';
                    echo '<td>';
                    if (isset($book['authors']) && is_array($book['authors']) && !empty($book['authors'])) {
                        $author = $book['authors'][0];
                        if (is_array($author)) {
                            echo htmlspecialchars($author['last_name'] ?? 'No last name');
                        }
                    } else {
                        echo 'No last name';
                    }
                    echo '</td>';
                    echo '<td>' . (isset($book['genres']) ? htmlspecialchars(implode(', ', $book['genres'])) : 'No genres') . '</td>';
                    echo '<td>' . (isset($book['series']) ? htmlspecialchars($book['series']) . 
                         (isset($book['series_position']) ? ' #' . htmlspecialchars($book['series_position']) : '') : 'No series') . '</td>';
                    echo '<td>' . date('Y-m-d H:i:s', $book['upload_date']) . '</td>';
                    $firstAuthorFirstName = '';
                    $firstAuthorLastName = '';
                    if (isset($book['authors']) && is_array($book['authors']) && !empty($book['authors'])) {
                        $firstAuthor = $book['authors'][0];
                        if (is_array($firstAuthor)) {
                            $firstAuthorFirstName = $firstAuthor['first_name'] ?? '';
                            $firstAuthorLastName = $firstAuthor['last_name'] ?? '';
                        }
                    }
                    echo '<td><button type="button" class="btn btn-sm btn-primary" onclick="editBook(' . 
                        "'" . htmlspecialchars($book['file_name'], ENT_QUOTES) . "', " .
                        "'" . htmlspecialchars($book['title'] ?? '', ENT_QUOTES) . "', " .
                        "'" . htmlspecialchars($firstAuthorFirstName, ENT_QUOTES) . "', " .
                        "'" . htmlspecialchars($firstAuthorLastName, ENT_QUOTES) . "', " .
                        "'" . htmlspecialchars(isset($book['genres']) ? implode(', ', $book['genres']) : '', ENT_QUOTES) . "', " .
                        "'" . htmlspecialchars($book['series'] ?? '', ENT_QUOTES) . "', " .
                        "'" . htmlspecialchars($book['series_position'] ?? '', ENT_QUOTES) . "'" .
                        ')">Edit</button></td>';
                    echo '</tr>';

                    if (isset($_POST['save_book']) && $_POST['file_name'] === $book['file_name']) {
                        $bookData = $manager->loadJson('data/books.json');
                        $bookFound = false;
                        
                        foreach ($bookData['books'] as &$existingBook) {
                            if ($existingBook['file_name'] === $_POST['file_name']) {
                                $existingBook['title'] = $_POST['title'];
                                // Preserve existing authors if present
                                // Update authors
                                $existingBook['authors'] = [];
                                foreach ($_POST['authors'] as $author) {
                                    if (!empty($author['first_name']) && !empty($author['last_name'])) {
                                        $existingBook['authors'][] = [
                                            'first_name' => $author['first_name'],
                                            'last_name' => $author['last_name']
                                        ];
                                    }
                                }
                                
                                // Update comment
                                $existingBook['comment'] = !empty($_POST['comment']) ? $_POST['comment'] : null;
                                // Ensure upload_date exists
                                if (!isset($existingBook['upload_date'])) {
                                    $existingBook['upload_date'] = isset($existingBook['date_uploaded']) ? 
                                        strtotime($existingBook['date_uploaded']) : 
                                        filemtime('_ksiazki/' . $_POST['file_name']);
                                }
                                unset($existingBook['date_uploaded']); // Remove old date format if exists
                                
                                // Update authors list
                                $listsData = $manager->loadJson('data/lists.json');
                                $authorExists = false;
                                foreach ($listsData['authors'] as $existingAuthor) {
                                    if ($existingAuthor['first_name'] === $author['first_name'] && 
                                        $existingAuthor['last_name'] === $author['last_name']) {
                                        $authorExists = true;
                                        break;
                                    }
                                }
                                if (!$authorExists) {
                                    $listsData['authors'][] = $author;
                                    file_put_contents('data/lists.json', json_encode($listsData, JSON_PRETTY_PRINT));
                                }
                                $existingBook['genres'] = array_map('trim', explode(',', $_POST['genres']));
                                $existingBook['series'] = !empty($_POST['series']) ? $_POST['series'] : null;
                                $existingBook['series_position'] = !empty($_POST['series_position']) ? $_POST['series_position'] : null;
                                $bookFound = true;
                                break;
                            }
                        }

                        if (!$bookFound) {
                            $bookData['books'][] = [
                                'file_name' => $_POST['file_name'],
                                'title' => $_POST['title'],
                                'authors' => [[
                                    'first_name' => $_POST['author_first_name'],
                                    'last_name' => $_POST['author_last_name']
                                ]],
                                'genres' => array_map('trim', explode(',', $_POST['genres'])),
                                'series' => !empty($_POST['series']) ? $_POST['series'] : null,
                                'series_position' => !empty($_POST['series_position']) ? $_POST['series_position'] : null,
                                'upload_date' => filemtime('_ksiazki/' . $_POST['file_name'])
                            ];
                        }

                        if (file_put_contents('data/books.json', json_encode($bookData, JSON_PRETTY_PRINT))) {
                            $_SESSION['success_message'] = 'Book metadata saved successfully!';
                        } else {
                            $_SESSION['success_message'] = 'Error saving book metadata!';
                        }
                        header('Location: ' . $_SERVER['PHP_SELF']);
                        exit;
                    }
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="container mt-4">
        <h2>Authors</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>First Name</th>
                    <th>Last Name</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $authors = [];
                foreach ($books as $book) {
                    if (isset($book['authors']) && is_array($book['authors'])) {
                        foreach ($book['authors'] as $author) {
                            if (is_array($author)) {
                                $authorKey = $author['first_name'] . '|' . $author['last_name'];
                                $authors[$authorKey] = $author;
                            }
                        }
                    }
                }
                ksort($authors);
                foreach ($authors as $author) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($author['first_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($author['last_name']) . '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>

        <h2 class="mt-4">Genres</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Genre</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $genres = [];
                foreach ($books as $book) {
                    if (isset($book['genres']) && is_array($book['genres'])) {
                        foreach ($book['genres'] as $genre) {
                            $genres[$genre] = true;
                        }
                    }
                }
                ksort($genres);
                foreach (array_keys($genres) as $genre) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($genre) . '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>

        <h2 class="mt-4">Series</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Series Name</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $series = [];
                foreach ($books as $book) {
                    if (isset($book['series']) && !empty($book['series'])) {
                        $series[$book['series']] = true;
                    }
                }
                ksort($series);
                foreach (array_keys($series) as $seriesName) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($seriesName) . '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Edit Book Modal -->
    <div class="modal fade" id="editBookModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editBookForm" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="file_name" id="edit_file_name">
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" id="edit_title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Authors</label>
                            <div id="authors-container">
                                <div class="author-entry mb-2">
                                    <select class="form-select mb-2 author-select" onchange="handleAuthorSelect(this)">
                                        <option value="">Add new author</option>
                                        <?php
                                        $authorsList = $manager->loadJson('data/lists.json')['authors'];
                                        foreach ($authorsList as $author) {
                                            echo '<option value="' . htmlspecialchars($author['first_name']) . '|' . htmlspecialchars($author['last_name']) . '">' 
                                                . htmlspecialchars($author['first_name'] . ' ' . $author['last_name']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <div class="author-inputs">
                                        <input type="text" class="form-control mb-2" name="authors[0][first_name]" placeholder="First Name" required>
                                        <input type="text" class="form-control" name="authors[0][last_name]" placeholder="Last Name" required>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="addAuthor()">Add Another Author</button>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Comment</label>
                            <textarea class="form-control" name="comment" id="edit_comment" rows="3" placeholder="Optional"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Genres (comma-separated)</label>
                            <input type="text" class="form-control" name="genres" id="edit_genres">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Series</label>
                            <input type="text" class="form-control" name="series" id="edit_series" placeholder="Optional">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Series Position</label>
                            <input type="number" class="form-control" name="series_position" id="edit_series_position" placeholder="Optional">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="save_book" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
let editModal = null;

function initializeModal() {
    const modalElement = document.getElementById('editBookModal');
    if (modalElement) {
        editModal = new bootstrap.Modal(modalElement);
    }
}

function editBook(fileName, title, authorFirstName, authorLastName, genres, series, seriesPosition) {
    if (!editModal) {
        initializeModal();
    }
    
    document.getElementById('edit_file_name').value = fileName;
    document.getElementById('edit_title').value = title || '';
    
    // Clear existing authors
    const authorsContainer = document.getElementById('authors-container');
    authorsContainer.innerHTML = '';
    
    // Add first author
    const authorEntry = document.createElement('div');
    authorEntry.className = 'author-entry mb-2';
    authorEntry.innerHTML = `
        <select class="form-select mb-2 author-select" onchange="handleAuthorSelect(this)">
            <option value="">Add new author</option>
            ${document.querySelector('.author-select').innerHTML.split('\n').slice(1).join('\n')}
        </select>
        <div class="author-inputs">
            <input type="text" class="form-control mb-2" name="authors[0][first_name]" value="${authorFirstName || ''}" placeholder="First Name" required>
            <input type="text" class="form-control" name="authors[0][last_name]" value="${authorLastName || ''}" placeholder="Last Name" required>
        </div>
    `;
    authorsContainer.appendChild(authorEntry);
    
    document.getElementById('edit_genres').value = genres || '';
    document.getElementById('edit_series').value = series || '';
    document.getElementById('edit_series_position').value = seriesPosition || '';
    
    editModal.show();
}

function handleAuthorSelect(select) {
    const container = select.closest('.author-entry');
    const [firstName, lastName] = select.value ? select.value.split('|') : ['', ''];
    const inputs = container.querySelector('.author-inputs');
    inputs.querySelector('input[name$="[first_name]"]').value = firstName;
    inputs.querySelector('input[name$="[last_name]"]').value = lastName;
}

function addAuthor() {
    const container = document.getElementById('authors-container');
    const index = container.children.length;
    const authorEntry = document.createElement('div');
    authorEntry.className = 'author-entry mb-2';
    authorEntry.innerHTML = `
        <select class="form-select mb-2 author-select" onchange="handleAuthorSelect(this)">
            <option value="">Add new author</option>
            ${document.querySelector('.author-select').innerHTML.split('\n').slice(1).join('\n')}
        </select>
        <div class="author-inputs">
            <input type="text" class="form-control mb-2" name="authors[${index}][first_name]" placeholder="First Name" required>
            <input type="text" class="form-control" name="authors[${index}][last_name]" placeholder="Last Name" required>
        </div>
        <button type="button" class="btn btn-danger btn-sm mt-1" onclick="this.parentElement.remove()">Remove</button>
    `;
    container.appendChild(authorEntry);
}

document.addEventListener('DOMContentLoaded', function() {
    initializeModal();
});
</script>

    <div class="container mt-4">
        <h2>books.json content:</h2>
        <pre class="bg-light p-3 border rounded">
<?php
$booksJson = file_get_contents('data/books.json');
echo htmlspecialchars($booksJson);
?>
        </pre>
    </div>
</body>
</html>
