<?php
session_start();

// Include central version definition
$basePath = dirname(__FILE__);
$includesPath = $basePath . '/includes/';

if (file_exists($includesPath . 'version.php')) {
    require_once $includesPath . 'version.php';
} else {
    define('SCRIPT_VERSION', '1.0.4-relocated');
}

function debug_log($message) {
    echo "<div style='background: #f8f9fa; border: 1px solid #ddd; margin: 2px; padding: 5px;'>DEBUG: " . htmlspecialchars($message) . "</div>";
}

debug_log("Script started from: " . __FILE__);
debug_log("Base path: " . $basePath);
debug_log("Includes path: " . $includesPath);

if (file_exists($includesPath . 'BookManager.php')) {
    require_once $includesPath . 'BookManager.php';
    debug_log("BookManager.php loaded successfully");
} else {
    debug_log("ERROR: BookManager.php not found at: " . $includesPath . 'BookManager.php');
    die("Critical error: BookManager.php not found");
}
$manager = new BookManager();
?>
<!DOCTYPE html>

    <style>
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
    <title>Book Manager v<?php echo SCRIPT_VERSION; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Cache busting meta tag -->
    <meta name="cache-version" content="<?php echo SCRIPT_VERSION; ?>-<?php echo filemtime(__FILE__); ?>">
</head>
<body>
    <div class="container mt-4">
        <h1>Book Manager</h1>
        <button onclick="generatePublicPage()" class="btn btn-primary mb-3">Generuj stronę publiczną</button>
        <?php 
        $templatesPath = $basePath . '/templates/library.php';
        debug_log("Loading template from: " . $templatesPath);
        if (file_exists($templatesPath)) {
            include $templatesPath;
        } else {
            debug_log("Template not found, trying relative path");
            include 'templates/library.php';
        }
        ?>

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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/typeahead.js/0.11.1/typeahead.bundle.min.js"></script>


    <script>
        // Debug - sprawdź czy jQuery jest załadowane
        console.log('jQuery loaded:', typeof $ !== 'undefined');
        console.log('Current path:', window.location.pathname);
        console.log('Script directory:', '<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>');
    </script>
    <script src="js/bookEditor.js"></script>
    <script>
        $(document).ready(function() {
            console.log('Document ready, initializing...');
            // Initialize autocomplete for existing forms when the document is ready
            $('.edit-form:not(.d-none)').each(function() {
                const rowId = this.id.replace('edit-form-', '');
                console.log('Initializing rowId:', rowId);
                initializeAuthors(rowId); // This initializes authors datalist autocomplete
                initializeAutocomplete(rowId); // This initializes series typeahead
            });

            // Function to initialize datalist autocomplete for authors
            function initializeAuthors(rowId) {
                const authorInput = $('#' + rowId + ' #author_name');
                if (authorInput.length && authorInput.data('datalist-initialized') !== true) {
                    const authorNames = $('#' + rowId).data('author-names') || [];
                    // Simple datalist-based autocomplete for authors
                    // Bootstrap Typeahead is not used here for authors based on the original logic
                    console.log('Initializing datalist for author_name in row:', rowId);
                    authorInput.data('datalist-initialized', true); // Mark as initialized
                }
            }

            // Function to initialize Bootstrap Typeahead for series
            function initializeAutocomplete(rowId) {
                const seriesInput = $('#' + rowId + ' #series_name');
                if (seriesInput.length && !seriesInput.hasClass('tt-input')) { // Check if Typeahead is already initialized
                    const seriesData = $('#' + seriesInput.closest('.edit-form')[0].id).data('series-data') || [];
                    const engine = new Bloodhound({
                        datumTokenizer: Bloodhound.tokenizers.whitespace,
                        queryTokenizer: Bloodhound.tokenizers.whitespace,
                        local: seriesData.map(function(item) { return { value: item }; })
                    });

                    seriesInput.typeahead({
                        hint: true,
                        highlight: true,
                        minLength: 1
                    }, {
                        name: 'series',
                        source: engine,
                        displayKey: 'value'
                    });
                    console.log('Initialized Typeahead for series_name in row:', rowId);
                } else if (seriesInput.hasClass('tt-input')) {
                    console.log('Typeahead already initialized for series_name in row:', rowId);
                }
            }
        });

        function generatePublicPage() {
            fetch('generate_public.php')
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        alert('Strona została wygenerowana pomyślnie');
                    }
                });
        }
    </script>
    <footer class="text-center mt-4">
        <p>&copy; Book Manager <?php echo date('Y'); ?> | Version: <?php echo SCRIPT_VERSION; ?></p>
    </footer>
</body>
</html>