
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

    // [Tutaj cała zawartość klasy BookManager z oryginalnego pliku]
}
