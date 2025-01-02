
<?php
require_once 'includes/BookManager.php';

function debug_log($message) {
    echo "DEBUG: " . $message . "\n";
}

try {
    $manager = new BookManager();
    debug_log("BookManager initialized");

    $processedBooks = $manager->getProcessedBooks();
    debug_log("Processed books loaded: " . count($processedBooks) . " books");
    
    $generationTime = date('Y-m-d H:i:s');
    
    usort($processedBooks, function($a, $b) {
        return strcasecmp($a['title'], $b['title']);
    });

    $html = <<<HTML
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="generation-time" content="{$generationTime}">
    <title>Moja Biblioteka</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .book-card {
            margin-bottom: 1rem;
            transition: transform 0.2s;
        }
        .book-card:hover {
            transform: translateY(-2px);
        }
        .book-title {
            color: #0d6efd;
            text-decoration: none;
            font-weight: bold;
        }
        .book-title:hover {
            text-decoration: underline;
        }
        .book-metadata {
            margin: 0.5rem 0;
            color: #666;
        }
        .book-series {
            font-style: italic;
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <header class="pb-3 mb-4 border-bottom">
            <h1 class="display-5 fw-bold">Moja Biblioteka</h1>
            <p class="text-muted">Ostatnia aktualizacja: {$generationTime}</p>
        </header>

        <div class="row mb-4">
            <div class="col-md-6">
                <input type="text" id="searchInput" class="form-control" placeholder="Wyszukaj książkę...">
            </div>
            <div class="col-md-6">
                <div class="btn-group w-100">
                    <button onclick="sortBooks('title')" class="btn btn-outline-primary">Tytuł</button>
                    <button onclick="sortBooks('author')" class="btn btn-outline-primary">Autor</button>
                    <button onclick="sortBooks('date')" class="btn btn-outline-primary">Data</button>
                </div>
            </div>
        </div>

        <div class="row" id="booksList">
HTML;

    foreach ($processedBooks as $book) {
        $authors = implode(', ', array_map(function($author) {
            return $author['first_name'] . ' ' . $author['last_name'];
        }, $book['authors']));
        
        $genres = implode(', ', $book['genres']);
        $seriesInfo = '';
        if (!empty($book['series'])) {
            $seriesInfo = "<div class='book-series'>{$book['series']} #{$book['series_position']}</div>";
        }
        
        $html .= <<<HTML
            <div class="col-12 book-card">
                <div class="card">
                    <div class="card-body">
                        <a href="_ksiazki/{$book['file_name']}" class="book-title">{$book['title']}</a>
                        <div class="book-metadata">
                            <strong>Autorzy:</strong> {$authors}<br>
                            <strong>Gatunki:</strong> {$genres}
                            {$seriesInfo}
                        </div>
                    </div>
                </div>
            </div>
HTML;
    }

    $booksJson = json_encode($processedBooks);
    
    $html .= <<<HTML
        </div>
    </div>
    
    <script>
    const books = {$booksJson};
    
    function sortBooks(by) {
        const sorted = [...books].sort((a, b) => {
            switch(by) {
                case 'author':
                    const authorA = a.authors[0]?.last_name || '';
                    const authorB = b.authors[0]?.last_name || '';
                    return authorA.localeCompare(authorB);
                case 'date':
                    return b.upload_date - a.upload_date;
                default:
                    return a.title.localeCompare(b.title);
            }
        });
        renderBooks(sorted);
    }
    
    function searchBooks(query) {
        query = query.toLowerCase();
        const filtered = books.filter(book => 
            book.title.toLowerCase().includes(query) ||
            book.authors.some(author => 
                (author.first_name + ' ' + author.last_name)
                .toLowerCase()
                .includes(query)
            ) ||
            book.genres.some(genre => 
                genre.toLowerCase().includes(query)
            ) ||
            (book.series && book.series.toLowerCase().includes(query))
        );
        renderBooks(filtered);
    }
    
    function renderBooks(booksToRender) {
        const container = document.getElementById('booksList');
        container.innerHTML = booksToRender.map(book => {
            const authors = book.authors.map(a => 
                `\${a.first_name} \${a.last_name}`).join(', ');
            const genres = book.genres.join(', ');
            const seriesInfo = book.series ? 
                `<div class="book-series">\${book.series} #\${book.series_position}</div>` : '';
            
            return `
                <div class="col-12 book-card">
                    <div class="card">
                        <div class="card-body">
                            <a href="_ksiazki/\${book.file_name}" class="book-title">\${book.title}</a>
                            <div class="book-metadata">
                                <strong>Autorzy:</strong> \${authors}<br>
                                <strong>Gatunki:</strong> \${genres}
                                \${seriesInfo}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }
    
    document.getElementById('searchInput').addEventListener('input', (e) => {
        searchBooks(e.target.value);
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
HTML;

    if(file_put_contents('index.html', $html)) {
        debug_log("Successfully generated index.html");
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Failed to write index.html");
    }
} catch (Exception $e) {
    debug_log("Error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
