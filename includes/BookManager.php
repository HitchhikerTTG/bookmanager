
<?php
class BookManager {
    private $booksData;
    private $listsData;
    private $token;
    
    const ITEMS_PER_PAGE = 10;
    
    public function __construct() {
        $this->ensureDataFilesExist();
        $this->booksData = $this->loadJson('data/books.json');
        $this->listsData = $this->loadJson('data/lists.json');
    }

    private function ensureDataFilesExist() {
        if (!file_exists('data')) {
            mkdir('data', 0777, true);
        }
        
        if (!file_exists('data/books.json')) {
            file_put_contents('data/books.json', json_encode(['books' => []]));
        }
        
        if (!file_exists('data/lists.json')) {
            $defaultLists = [
                'authors' => [],
                'genres' => [],
                'series' => []
            ];
            file_put_contents('data/lists.json', json_encode($defaultLists));
        }
    }

    private function loadJson($file) {
        if (!file_exists($file)) {
            throw new Exception("File not found: $file");
        }
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON in file: $file");
        }
        return $data;
    }

    private function saveJson($file, $data) {
        $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (file_put_contents($file, $jsonData) === false) {
            throw new Exception("Failed to save data to file: $file");
        }
    }

    public function getStats() {
        return [
            'total' => count(glob('_ksiazki/*.*')),
            'withMetadata' => count($this->booksData['books']),
            'lastUpdate' => date('Y-m-d H:i:s')
        ];
    }

    public function getUnprocessedBooks() {
        $allFiles = glob('_ksiazki/*.*');
        $processedBooks = $this->getProcessedBooks();
        $processedFiles = array_column($processedBooks, 'file_name');
        return array_map('basename', array_diff($allFiles, $processedFiles));
    }

    public function getProcessedBooks() {
        return array_filter($this->booksData['books'], function($book) {
            return !empty($book['title']) && 
                   !empty($book['authors']) && 
                   count($book['authors']) > 0 &&
                   !empty($book['authors'][0]['first_name']) && 
                   !empty($book['authors'][0]['last_name']);
        });
    }

    public function addBook($data) {
        $errors = $this->validateBookData($data);
        if (!empty($errors)) {
            throw new Exception(implode(", ", $errors));
        }

        $newBook = [
            'file_name' => $data['file_name'],
            'upload_date' => time(),
            'title' => $data['title'],
            'authors' => $data['authors'],
            'genres' => explode(',', $data['genres']),
            'series' => $data['series'] ?? null,
            'series_position' => $data['series_position'] ?? null,
            'comment' => $data['comment'] ?? null
        ];

        $this->booksData['books'][] = $newBook;
        $this->saveJson('data/books.json', $this->booksData);
        $this->updateLists($newBook);
    }

    public function updateBook($fileName, $data) {
        $index = array_search($fileName, array_column($this->booksData['books'], 'file_name'));
        if ($index === false) {
            throw new Exception("Book not found");
        }

        $errors = $this->validateBookData($data);
        if (!empty($errors)) {
            throw new Exception(implode(", ", $errors));
        }

        $this->booksData['books'][$index] = array_merge(
            $this->booksData['books'][$index],
            [
                'title' => $data['title'],
                'authors' => $data['authors'],
                'genres' => explode(',', $data['genres']),
                'series' => $data['series'] ?? null,
                'series_position' => $data['series_position'] ?? null,
                'comment' => $data['comment'] ?? null
            ]
        );

        $this->saveJson('data/books.json', $this->booksData);
        $this->updateLists($this->booksData['books'][$index]);
    }

    private function validateBookData($data) {
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

    private function updateLists($book) {
        // Update authors list
        foreach ($book['authors'] as $author) {
            $authorKey = $author['first_name'] . '|' . $author['last_name'];
            if (!in_array($authorKey, $this->listsData['authors'])) {
                $this->listsData['authors'][] = $authorKey;
            }
        }

        // Update genres list
        foreach ($book['genres'] as $genre) {
            if (!in_array($genre, $this->listsData['genres'])) {
                $this->listsData['genres'][] = $genre;
            }
        }

        // Update series list
        if (!empty($book['series']) && !in_array($book['series'], $this->listsData['series'])) {
            $this->listsData['series'][] = $book['series'];
        }

        $this->saveJson('data/lists.json', $this->listsData);
    }

    public function getLists() {
        return $this->listsData;
    }

    public function validateAndUpdateLists() {
        $allAuthors = [];
        $allGenres = [];
        $allSeries = [];

        foreach ($this->booksData['books'] as $book) {
            foreach ($book['authors'] as $author) {
                $authorKey = $author['first_name'] . '|' . $author['last_name'];
                if (!in_array($authorKey, $allAuthors)) {
                    $allAuthors[] = $authorKey;
                }
            }

            foreach ($book['genres'] as $genre) {
                if (!in_array($genre, $allGenres)) {
                    $allGenres[] = $genre;
                }
            }

            if (!empty($book['series']) && !in_array($book['series'], $allSeries)) {
                $allSeries[] = $book['series'];
            }
        }

        $this->listsData['authors'] = array_values(array_unique(array_merge($this->listsData['authors'], $allAuthors)));
        $this->listsData['genres'] = array_values(array_unique(array_merge($this->listsData['genres'], $allGenres)));
        $this->listsData['series'] = array_values(array_unique(array_merge($this->listsData['series'], $allSeries)));

        $this->saveJson('data/lists.json', $this->listsData);
    }

    public function checkUnprocessedBooks() {
        $allFiles = array_map('basename', glob('_ksiazki/*.*'));
        $processedFiles = array_column($this->booksData['books'], 'file_name');
        
        $stats = [
            'total_files' => count($allFiles),
            'processed_files' => count($processedFiles),
            'unprocessed_files' => array_diff($allFiles, $processedFiles)
        ];
        
        return $stats;
    }
}
