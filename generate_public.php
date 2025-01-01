
<?php
require_once 'includes/BookManager.php';
$manager = new BookManager();

$generationTime = date('Y-m-d H:i:s');

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
        
        <footer class="mt-5 text-muted">
            <p>Strona wygenerowana: $generationTime</p>
        </footer>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
HTML;

file_put_contents('index.html', $html);

header('Content-Type: application/json');
echo json_encode(['success' => true]);
