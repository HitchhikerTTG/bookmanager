
<?php
class SystemCheck {
    private $errors = [];
    private $warnings = [];
    
    public function runChecks() {
        $this->checkRequiredFiles();
        $this->checkDirectories();
        $this->checkJsonFiles();
        $this->checkTemplates();
        $this->checkBooksDirectory();
        $this->displayResults();
    }
    
    private function checkRequiredFiles() {
        $requiredFiles = [
            'includes/BookManager.php',
            'includes/functions.php',
            'js/bookEditor.js',
            'aniol.php',
            'ksiazki.html'
        ];
        
        foreach ($requiredFiles as $file) {
            if (!file_exists($file)) {
                $this->errors[] = "Missing required file: $file";
            }
        }
    }
    
    private function checkDirectories() {
        $requiredDirs = ['data', 'includes', 'js', 'templates', '_ksiazki'];
        foreach ($requiredDirs as $dir) {
            if (!is_dir($dir)) {
                $this->errors[] = "Missing required directory: $dir";
            }
        }
    }
    
    private function checkJsonFiles() {
        // Check books.json
        if (file_exists('data/books.json')) {
            $books = json_decode(file_get_contents('data/books.json'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->errors[] = "books.json is not valid JSON";
            } else {
                echo "<h3>books.json content:</h3>";
                echo "<pre>" . htmlspecialchars(json_encode($books, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
            }
        }
        
        // Check lists.json
        if (file_exists('data/lists.json')) {
            $lists = json_decode(file_get_contents('data/lists.json'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->errors[] = "lists.json is not valid JSON";
            } else {
                if (!isset($lists['authors']) || !isset($lists['genres']) || !isset($lists['series'])) {
                    $this->errors[] = "lists.json missing required keys (authors, genres, series)";
                }
                echo "<h3>lists.json content:</h3>";
                echo "<pre>" . htmlspecialchars(json_encode($lists, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
            }
        }
    }
    
    private function checkBooksDirectory() {
        if (is_dir('_ksiazki')) {
            $books = scandir('_ksiazki');
            $books = array_diff($books, array('.', '..'));
            
            echo "<h3>Books directory content:</h3>";
            echo "<ul>";
            foreach ($books as $book) {
                echo "<li>" . htmlspecialchars($book) . "</li>";
            }
            echo "</ul>";
            
            if (empty($books)) {
                $this->warnings[] = "Books directory is empty";
            }
        }
    }
    
    private function checkTemplates() {
        $templates = [
            'templates/header.php',
            'templates/alerts.php',
            'templates/tables.php',
            'templates/modals.php'
        ];
        
        foreach ($templates as $template) {
            if (!file_exists($template)) {
                $this->errors[] = "Missing template file: $template";
            } else {
                $content = file_get_contents($template);
                if (empty($content)) {
                    $this->warnings[] = "Template file is empty: $template";
                }
            }
        }
    }
    
    private function displayResults() {
        echo "<html><head><title>System Check Results</title>";
        echo "<style>
            .error { color: red; font-weight: bold; }
            .warning { color: orange; }
            .success { color: green; }
            pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; }
            ul { list-style-type: none; padding-left: 20px; }
            li { padding: 2px 0; }
        </style></head><body>";
        
        echo "<h1>System Check Results</h1>";
        
        if (empty($this->errors) && empty($this->warnings)) {
            echo "<p class='success'>All checks passed successfully!</p>";
        } else {
            if (!empty($this->errors)) {
                echo "<h2>Errors:</h2><ul>";
                foreach ($this->errors as $error) {
                    echo "<li class='error'>$error</li>";
                }
                echo "</ul>";
            }
            
            if (!empty($this->warnings)) {
                echo "<h2>Warnings:</h2><ul>";
                foreach ($this->warnings as $warning) {
                    echo "<li class='warning'>$warning</li>";
                }
                echo "</ul>";
            }
        }
        
        echo "</body></html>";
    }
}

$checker = new SystemCheck();
$checker->runChecks();
?>
