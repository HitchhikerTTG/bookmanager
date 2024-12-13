
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

    public function getUnprocessedBooks() {
        $allFiles = is_dir('_ksiazki') ? array_map('basename', glob('_ksiazki/*')) : [];
        $processedFiles = array_column($this->booksData['books'], 'file_name');
        $unprocessedFiles = array_diff($allFiles, $processedFiles);
        
        // Add unprocessed files to books.json with upload date
        foreach ($unprocessedFiles as $file) {
            $uploadTime = filemtime('_ksiazki/' . $file);
            $this->booksData['books'][] = [
                'file_name' => $file,
                'upload_date' => $uploadTime,
                'title' => '',
                'authors' => [],
                'genres' => [],
                'series' => null,
                'series_position' => null
            ];
        }
        
        // Save updated data
        file_put_contents('data/books.json', json_encode($this->booksData, JSON_PRETTY_PRINT));
        return $unprocessedFiles;
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
        // Check required fields exist and are not empty
        $required = ['file_name', 'title', 'upload_date'];
        foreach ($required as $field) {
            if (!isset($book[$field]) || empty($book[$field])) {
                return false;
            }
        }

        // Verify authors array exists and has at least one valid author
        if (!isset($book['authors']) || !is_array($book['authors']) || empty($book['authors'])) {
            return false;
        }

        // Check if at least one author has both first and last name
        $hasValidAuthor = false;
        foreach ($book['authors'] as $author) {
            if (isset($author['first_name']) && isset($author['last_name']) && 
                !empty($author['first_name']) && !empty($author['last_name'])) {
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
                '<li class="book-item"><a href="%s/_ksiazki/%s">[🔗http]</a> <a href="%s/_ksiazki/%s">%s</a> [%s]</li>',
                $rootUrl,
                urlencode($book['file_name']),
                $rootsUrl,
                urlencode($book['file_name']),
                htmlspecialchars($book['title']),
                htmlspecialchars(implode('; ', $authors))
            );
        }

        $html .= '</ul></body></html>';
        
        file_put_contents('ksiazki.html', $html);
    }

    public function saveMetadata($data) {
        $book = [
            'file_name' => $data['file_name'],
            'title' => $data['title'],
            'authors' => [],
            'genres' => $data['genres'],
            'series' => $data['series'],
            'series_position' => $data['series_position']
        ];

        foreach ($data['authors'] as $author) {
            if (!empty($author['first_name']) && !empty($author['last_name'])) {
                $book['authors'][] = [
                    'first_name' => $author['first_name'],
                    'last_name' => $author['last_name']
                ];
                
                // Update lists.json with new author
                if (!in_array($author, $this->listsData['authors'])) {
                    $this->listsData['authors'][] = $author;
                }
            }
        }

        // Update genres and series in lists.json
        if (!empty($data['genres'])) {
            foreach ($data['genres'] as $genre) {
                if (!in_array($genre, $this->listsData['genres'])) {
                    $this->listsData['genres'][] = $genre;
                }
            }
        }

        if (!empty($data['series'])) {
            if (!in_array($data['series'], $this->listsData['series'])) {
                $this->listsData['series'][] = $data['series'];
            }
        }

        // Save to books.json
        $bookIndex = array_search($data['file_name'], array_column($this->booksData['books'], 'file_name'));
        if ($bookIndex !== false) {
            $this->booksData['books'][$bookIndex] = $book;
        } else {
            $this->booksData['books'][] = $book;
        }

        file_put_contents('data/books.json', json_encode($this->booksData, JSON_PRETTY_PRINT));
        file_put_contents('data/lists.json', json_encode($this->listsData, JSON_PRETTY_PRINT));
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $manager->saveMetadata($data);
    exit;
}

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
                        <button class="btn btn-primary btn-sm" onclick="editMetadata('<?= htmlspecialchars($file) ?>', null)">Add Metadata</button>
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
                        <button class="btn btn-primary btn-sm" onclick='editMetadata("<?= htmlspecialchars($book['file_name']) ?>", <?= json_encode($book) ?>)'>Edit</button>
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

    <!-- Metadata Edit Modal -->
    <div class="modal fade" id="editMetadataModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Metadata</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="metadataForm">
                        <input type="hidden" id="file_name" name="file_name">
                        <div class="mb-3">
                            <label>File Upload Date:</label>
                            <input type="text" class="form-control" id="upload_date" readonly>
                        </div>
                        <div class="mb-3">
                            <label>Title:</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label>Authors:</label>
                            <div id="authors_container">
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" name="author_first_name[]" placeholder="First Name" list="existing_first_names">
                                    <input type="text" class="form-control" name="author_last_name[]" placeholder="Last Name" list="existing_last_names">
                                    <button type="button" class="btn btn-danger" onclick="removeAuthor(this)">Remove</button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="addAuthor()">Add Author</button>
                        </div>
                        <div class="mb-3">
                            <label>Genres:</label>
                            <select class="form-select" name="genres[]" multiple>
                                <?php foreach ($manager->loadJson('data/lists.json')['genres'] as $genre): ?>
                                    <option value="<?= htmlspecialchars($genre) ?>"><?= htmlspecialchars($genre) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" class="form-control mt-2" id="new_genre" placeholder="Add new genre">
                            <button type="button" class="btn btn-secondary btn-sm mt-1" onclick="addGenre()">Add Genre</button>
                        </div>
                        <div class="mb-3">
                            <label>Series:</label>
                            <select class="form-select" name="series">
                                <option value="">None</option>
                                <?php foreach ($manager->loadJson('data/lists.json')['series'] as $series): ?>
                                    <option value="<?= htmlspecialchars($series) ?>"><?= htmlspecialchars($series) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" class="form-control mt-2" id="new_series" placeholder="Add new series">
                            <button type="button" class="btn btn-secondary btn-sm mt-1" onclick="addSeries()">Add Series</button>
                        </div>
                        <div class="mb-3">
                            <label>Series Position:</label>
                            <input type="number" class="form-control" name="series_position">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="saveMetadata()">Save</button>
                </div>
            </div>
        </div>
    </div>

    <datalist id="existing_first_names">
        <?php foreach ($manager->loadJson('data/lists.json')['authors'] as $author): ?>
            <option value="<?= htmlspecialchars($author['first_name']) ?>">
        <?php endforeach; ?>
    </datalist>

    <datalist id="existing_last_names">
        <?php foreach ($manager->loadJson('data/lists.json')['authors'] as $author): ?>
            <option value="<?= htmlspecialchars($author['last_name']) ?>">
        <?php endforeach; ?>
    </datalist>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const modal = new bootstrap.Modal(document.getElementById('editMetadataModal'));
        
        function editMetadata(fileName, existingData) {
            const form = document.getElementById('metadataForm');
            form.reset();
            document.getElementById('file_name').value = fileName;
            document.getElementById('upload_date').value = new Date(filemtime('_ksiazki/' + fileName) * 1000).toLocaleString();
            
            if (existingData) {
                form.querySelector('[name="title"]').value = existingData.title;
                form.querySelector('[name="series"]').value = existingData.series || '';
                form.querySelector('[name="series_position"]').value = existingData.series_position || '';
                
                // Set authors
                document.getElementById('authors_container').innerHTML = '';
                existingData.authors.forEach(author => {
                    addAuthor(author.first_name, author.last_name);
                });
                
                // Set genres
                const genreSelect = form.querySelector('[name="genres[]"]');
                existingData.genres.forEach(genre => {
                    Array.from(genreSelect.options).forEach(option => {
                        if (option.value === genre) option.selected = true;
                    });
                });
            }
            
            modal.show();
        }

        function addAuthor(firstName = '', lastName = '') {
            const container = document.getElementById('authors_container');
            const div = document.createElement('div');
            div.className = 'input-group mb-2';
            div.innerHTML = `
                <input type="text" class="form-control" name="author_first_name[]" placeholder="First Name" value="${firstName}" list="existing_first_names">
                <input type="text" class="form-control" name="author_last_name[]" placeholder="Last Name" value="${lastName}" list="existing_last_names">
                <button type="button" class="btn btn-danger" onclick="removeAuthor(this)">Remove</button>
            `;
            container.appendChild(div);
        }

        function removeAuthor(button) {
            button.closest('.input-group').remove();
        }

        function addGenre() {
            const newGenre = document.getElementById('new_genre').value.trim();
            if (!newGenre) return;
            
            const select = document.querySelector('[name="genres[]"]');
            const option = new Option(newGenre, newGenre, true, true);
            select.add(option);
            document.getElementById('new_genre').value = '';
        }

        function addSeries() {
            const newSeries = document.getElementById('new_series').value.trim();
            if (!newSeries) return;
            
            const select = document.querySelector('[name="series"]');
            const option = new Option(newSeries, newSeries);
            select.add(option);
            select.value = newSeries;
            document.getElementById('new_series').value = '';
        }

        function saveMetadata() {
            const form = document.getElementById('metadataForm');
            const formData = new FormData(form);
            
            const data = {
                file_name: formData.get('file_name'),
                title: formData.get('title'),
                authors: [],
                genres: Array.from(form.querySelector('[name="genres[]"]').selectedOptions).map(opt => opt.value),
                series: formData.get('series'),
                series_position: formData.get('series_position')
            };
            
            const firstNames = formData.getAll('author_first_name[]');
            const lastNames = formData.getAll('author_last_name[]');
            for (let i = 0; i < firstNames.length; i++) {
                if (firstNames[i] && lastNames[i]) {
                    data.authors.push({
                        first_name: firstNames[i],
                        last_name: lastNames[i]
                    });
                }
            }
            
            fetch('aniol.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            }).then(() => {
                modal.hide();
                location.reload();
            });
        }
    </script>
</body>
</html>
