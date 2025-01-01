
<?php
require_once 'includes/BookManager.php';
$manager = new BookManager();

$processedBooks = $manager->getProcessedBooks();
$unprocessedBooks = $manager->getUnprocessedBooks();
$generationTime = date('Y-m-d H:i:s');

// Get sorting parameter
$sort = $_GET['sort'] ?? 'title';
$search = $_GET['search'] ?? '';

// Filter books based on search
if ($search !== '') {
    $processedBooks = array_filter($processedBooks, function($book) use ($search) {
        return stripos($book['title'], $search) !== false;
    });
}

// Sort books
usort($processedBooks, function($a, $b) use ($sort) {
    switch ($sort) {
        case 'author':
            $authorA = $a['authors'][0]['last_name'] . ' ' . $a['authors'][0]['first_name'];
            $authorB = $b['authors'][0]['last_name'] . ' ' . $b['authors'][0]['first_name'];
            return strcasecmp($authorA, $authorB);
        case 'date':
            return $b['upload_date'] - $a['upload_date'];
        case 'genre':
            return strcasecmp($a['genres'][0], $b['genres'][0]);
        default: // title
            return strcasecmp($a['title'], $b['title']);
    }
});

$html = <<<HTML
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="generation-time" content="$generationTime">
    <title>Moja Biblioteka</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Moja Biblioteka</h1>
        
        <div class="row mt-4">
            <div class="col-12 mb-3">
                <div class="row g-3">
                    <div class="col-md-6" id="searchContainer">
                        <input type="text" class="form-control" id="searchInput" placeholder="Wyszukaj..." value="{$search}">
                        <div id="searchSuggestions" class="list-group mt-1 position-absolute w-100" style="z-index: 1000; display: none;"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="btn-group w-100" role="group">
                            <button type="button" class="btn btn-outline-primary <?php echo $sort === 'title' ? 'active' : ''; ?>" onclick="updateSort('title')">Po tytule</button>
                            <button type="button" class="btn btn-outline-primary <?php echo $sort === 'author' ? 'active' : ''; ?>" onclick="updateSort('author')">Po autorze</button>
                            <button type="button" class="btn btn-outline-primary <?php echo $sort === 'date' ? 'active' : ''; ?>" onclick="updateSort('date')">Po dacie</button>
                            <button type="button" class="btn btn-outline-primary <?php echo $sort === 'genre' ? 'active' : ''; ?>" onclick="updateSort('genre')">Po gatunku</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-12">
                <h2>Książki z metadanymi</h2>
                <div class="list-group">
HTML;

foreach ($processedBooks as $book) {
    $authors = implode(', ', array_map(function($author) {
        return $author['first_name'] . ' ' . $author['last_name'];
    }, $book['authors']));
    
    $genres = implode(', ', $book['genres']);
    $date = date('Y-m-d', $book['upload_date']);
    
    $html .= <<<HTML
    <div class="list-group-item">
        <h5 class="mb-1"><a href="_ksiazki/{$book['file_name']}">{$book['title']}</a></h5>
        <p class="mb-1">Autorzy: {$authors}</p>
        <p class="mb-1">Gatunki: {$genres}</p>
        <small>Data dodania: {$date}</small><br>
        <small><a href="http://{$_SERVER['HTTP_HOST']}/_ksiazki/{$book['file_name']}">alternatywnie pobierz po http</a></small>
    </div>
HTML;
}

$html .= <<<HTML
                </div>
            </div>
            
            <div class="col-12 mt-4">
                <h2>Książki bez metadanych</h2>
                <div class="list-group">
HTML;

foreach ($unprocessedBooks as $fileName) {
    $html .= <<<HTML
    <div class="list-group-item">
        <p class="mb-1">Plik: {$fileName}</p>
    </div>
HTML;
}

$html .= <<<HTML
                </div>
            </div>
        </div>
        
        <footer class="mt-5 text-muted">
            <p>Strona wygenerowana: $generationTime</p>
        </footer>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let currentSort = '<?php echo $sort; ?>';
    const books = <?php echo json_encode($processedBooks); ?>;
    
    function updateSort(sortType) {
        window.location.href = '?' + new URLSearchParams({
            sort: sortType,
            search: document.getElementById('searchInput').value
        }).toString();
    }

    function updateSearchVisibility() {
        const searchContainer = document.getElementById('searchContainer');
        if (currentSort === 'date' || currentSort === 'genre') {
            searchContainer.style.display = 'none';
        } else {
            searchContainer.style.display = 'block';
        }
    }

    function getSuggestions(input) {
        if (!input) return [];
        input = input.toLowerCase();
        const suggestions = new Set();
        
        books.forEach(book => {
            if (currentSort === 'title' && book.title.toLowerCase().includes(input)) {
                suggestions.add(book.title);
            } else if (currentSort === 'author') {
                book.authors.forEach(author => {
                    const fullName = `${author.first_name} ${author.last_name}`;
                    if (fullName.toLowerCase().includes(input)) {
                        suggestions.add(fullName);
                    }
                });
            }
        });
        
        return Array.from(suggestions).slice(0, 5);
    }

    document.getElementById('searchInput').addEventListener('input', function(e) {
        const suggestions = getSuggestions(e.target.value);
        const suggestionsList = document.getElementById('searchSuggestions');
        
        if (suggestions.length > 0) {
            suggestionsList.innerHTML = suggestions.map(s => 
                `<a href="#" class="list-group-item list-group-item-action">${s}</a>`
            ).join('');
            suggestionsList.style.display = 'block';
        } else {
            suggestionsList.style.display = 'none';
        }
    });

    document.getElementById('searchSuggestions').addEventListener('click', function(e) {
        if (e.target.tagName === 'A') {
            e.preventDefault();
            document.getElementById('searchInput').value = e.target.textContent;
            this.style.display = 'none';
            updateSort(currentSort);
        }
    });

    document.addEventListener('DOMContentLoaded', updateSearchVisibility);
    </script>
</body>
</html>
HTML;

file_put_contents('index.html', $html);

header('Content-Type: application/json');
echo json_encode(['success' => true]);
