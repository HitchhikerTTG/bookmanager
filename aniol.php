<?php
session_start();

function debug_log($message) {
    echo "<div style='background: #f8f9fa; border: 1px solid #ddd; margin: 2px; padding: 5px;'>DEBUG: " . htmlspecialchars($message) . "</div>";
}

debug_log("Script started");

require_once 'includes/BookManager.php';
$manager = new BookManager();

$stats = $manager->getStats(); // Assuming getStats() returns an array with 'lastUpdate'

?>
<!DOCTYPE html>

    <style>
        .bootstrap-tagsinput {
            width: 100%;
            padding: 8px;
            border-radius: 4px;
        }
        .bootstrap-tagsinput .tag {
            margin-right: 4px;
            padding: 4px 8px;
            color: white !important;
            background-color: #0d6efd;
            border-radius: 3px;
            display: inline-block;
        }
        .tt-menu {
            width: 100%;
            padding: 8px;
            background: white;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .tt-suggestion {
            padding: 4px 8px;
            cursor: pointer;
        }
        .tt-suggestion:hover {
            background-color: #f8f9fa;
        }
    </style>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Book Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/typeahead.js-bootstrap-css/1.2.1/typeaheadjs.min.css" rel="stylesheet">
    
</head>
<body>
    <div class="container mt-4">
        <h1>Book Manager</h1>

        <nav class="navbar navbar-light bg-light mt-3">
            <div class="container-fluid">
                <div>Last Update: <?php echo $stats['lastUpdate']; ?></div>
                <div class="ms-3">Last HTML Update: <?php echo date('Y-m-d H:i:s', file_exists('index.html') ? filemtime('index.html') : 0); ?></div>
                <button onclick="generatePublicPage()" class="btn btn-primary ms-3">Generuj stronę publiczną</button>
            </div>
        </nav>


        <div class="row mt-4">
            <div class="col-12">
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="my-library-tab" data-bs-toggle="tab" data-bs-target="#my-library" type="button" role="tab" aria-controls="my-library" aria-selected="true">Moja biblioteka</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="preparing-books-tab" data-bs-toggle="tab" data-bs-target="#preparing-books" type="button" role="tab" aria-controls="preparing-books" aria-selected="false">Książki w przygotowaniu</button>
                    </li>
                </ul>
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active" id="my-library" role="tabpanel" aria-labelledby="my-library-tab">
                        <?php
                            $booksWithTitleAndAuthor = $manager->countBooksWithTitleAndAuthor();
                            echo "<p>Liczba książek z tytułem i autorem: " . $booksWithTitleAndAuthor . "</p>";
                        ?>
                    </div>
                    <div class="tab-pane fade" id="preparing-books" role="tabpanel" aria-labelledby="preparing-books-tab">
                        <?php
                            $booksWithoutTitleAndAuthor = $manager->countBooksWithoutTitleAndAuthor();
                            echo "<p>Liczba książek bez tytułu i autora: " . $booksWithoutTitleAndAuthor . "</p>";
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'templates/library.php'; ?>

        <div class="row mt-4">
            <div class="col-md-6">
                <h3>Authors Statistics</h3>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Author</th>
                            <th>Books Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $authorStats = [];
                        foreach ($manager->getProcessedBooks() as $book) {
                            foreach ($book['authors'] as $author) {
                                $authorKey = $author['first_name'] . ' ' . $author['last_name'];
                                $authorStats[$authorKey] = ($authorStats[$authorKey] ?? 0) + 1;
                            }
                        }
                        arsort($authorStats);
                        foreach ($authorStats as $author => $count): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($author); ?></td>
                                <td><?php echo $count; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="col-md-6">
                <h3>Genres Statistics</h3>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Genre</th>
                            <th>Books Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $genreStats = [];
                        foreach ($manager->getProcessedBooks() as $book) {
                            foreach ($book['genres'] as $genre) {
                                $genreStats[$genre] = ($genreStats[$genre] ?? 0) + 1;
                            }
                        }
                        arsort($genreStats);
                        foreach ($genreStats as $genre => $count): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($genre); ?></td>
                                <td><?php echo $count; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <h3>Available Genre Predictions</h3>
                <div class="card">
                    <div class="card-body">
                        <?php
                        $availableGenres = $manager->getLists()['genres'];
                        if (empty($availableGenres)): ?>
                            <p class="text-muted">No genres available for prediction yet.</p>
                        <?php else: ?>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($availableGenres as $genre): ?>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($genre); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'templates/modals.php'; ?>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/corejs-typeahead/1.3.1/typeahead.bundle.min.js"></script>
    
    <script src="js/bookEditor.js"></script>
    <script>
        $(document).ready(function() {
            $('.edit-form:not(.d-none)').each(function() {
                const rowId = this.id.replace('edit-form-', '');
                initializeAuthors(rowId);
                initializeAutocomplete(rowId);
            });
        });

        function generatePublicPage() {
            fetch('generate_public.php')
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        location.reload();
                    }
                });
        }
    </script>
</body>
</html>