
<?php
/**
 * Helper functions for the book management system
 */

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateBookData($data) {
    $errors = [];
    
    if (empty($data['title'])) {
        $errors[] = "Title is required";
    }
    
    if (empty($data['authors'])) {
        $errors[] = "At least one author is required";
    }
    
    if (empty($data['genres'])) {
        $errors[] = "At least one genre is required";
    }
    
    return $errors;
}

function formatDate($timestamp) {
    return date('Y-m-d H:i:s', $timestamp);
}

function generateSlug($text) {
    $text = transliterator_transliterate('Any-Latin; Latin-ASCII;', $text);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}
?>
