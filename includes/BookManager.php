
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
            file_put_contents('data/books.json', json_encode([]));
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

    public function getStats() {
        return [
            'total' => count(glob('_ksiazki/*.*')),
            'withMetadata' => count($this->booksData),
            'lastUpdate' => date('Y-m-d H:i:s')
        ];
    }

    public function getUnprocessedBooks() {
        $allFiles = glob('_ksiazki/*.*');
        $unprocessed = [];
        foreach ($allFiles as $file) {
            $filename = basename($file);
            if (!isset($this->booksData[$filename])) {
                $unprocessed[] = $filename;
            }
        }
        return $unprocessed;
    }

    public function getProcessedBooks() {
        return $this->booksData;
    }
}
