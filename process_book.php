
<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'includes/BookManager.php';

header('Content-Type: application/json');

try {
    $manager = new BookManager();
    
    $data = [
        'file_name' => $_POST['file_name'],
        'title' => $_POST['title'],
        'authors' => [],
        'genres' => $_POST['genres'],
        'series' => $_POST['series'] ?? null,
        'series_position' => $_POST['series_position'] ?? null,
        'comment' => $_POST['comment'] ?? null
    ];

    // Process authors
    if (isset($_POST['authors'])) {
        foreach ($_POST['authors'] as $author) {
            if (!empty($author['first_name']) && !empty($author['last_name'])) {
                $data['authors'][] = [
                    'first_name' => $author['first_name'],
                    'last_name' => $author['last_name']
                ];
            }
        }
    }

    // Check if book exists and update or add accordingly
    $existingBooks = $manager->getProcessedBooks();
    $exists = false;
    foreach ($existingBooks as $book) {
        if ($book['file_name'] === $data['file_name']) {
            $exists = true;
            break;
        }
    }

    if ($exists) {
        $manager->updateBook($data['file_name'], $data);
    } else {
        $manager->addBook($data);
    }

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        // Ajax request - return JSON
        echo json_encode(['success' => true]);
    } else {
        // Regular form submit - redirect
        header('Location: aniol.php');
        exit();
    }
} catch (Exception $e) {
    http_response_code(500);
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    } else {
        $_SESSION['error'] = $e->getMessage();
        header('Location: aniol.php');
        exit();
    }
}
