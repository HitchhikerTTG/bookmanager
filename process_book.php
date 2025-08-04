<?php
header('Content-Type: application/json; charset=utf-8');

$basePath = dirname(__FILE__);
$includesPath = $basePath . '/includes/';

if (file_exists($includesPath . 'BookManager.php')) {
    require_once $includesPath . 'BookManager.php';
} else {
    error_log("BookManager.php not found at: " . $includesPath . 'BookManager.php');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'BookManager not found']);
    exit();
}

try {
    error_log("Processing book submission");
    $manager = new BookManager();

    if (!isset($_POST['file_name']) || !isset($_POST['title']) || !isset($_POST['genres'])) {
        throw new Exception("Missing required fields");
    }

    $data = [
        'file_name' => trim($_POST['file_name']),
        'title' => trim($_POST['title']),
        'authors' => [],
        'genres' => trim($_POST['genres']),
        'series' => isset($_POST['series']) ? trim($_POST['series']) : null,
        'series_position' => isset($_POST['series_position']) ? trim($_POST['series_position']) : null,
        'comment' => isset($_POST['comment']) ? trim($_POST['comment']) : null
    ];

    // Process authors
    $authors = [];
    if (isset($_POST['authors']) && is_array($_POST['authors'])) {
        foreach ($_POST['authors'] as $author) {
            $fullName = trim($author['full_name'] ?? '');

            if (!empty($fullName) && strpos($fullName, ',') !== false) {
                $parts = explode(',', $fullName, 2);
                $firstName = trim($parts[0]);
                $lastName = trim($parts[1]);

                if (!empty($firstName) && !empty($lastName)) {
                    $authors[] = [
                        'first_name' => $firstName,
                        'last_name' => $lastName
                    ];
                }
            }
        }
    }
    $data['authors'] = $authors;


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
        error_log("Sending success response");
        echo json_encode(['success' => true]);
    } else {
        // Regular form submit - redirect
        header('Location: aniol.php');
        exit();
    }
} catch (Exception $e) {
    error_log("Error processing book: " . $e->getMessage());
    http_response_code(500);
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    } else {
        $_SESSION['error'] = $e->getMessage();
        header('Location: aniol.php');
        exit();
    }
}