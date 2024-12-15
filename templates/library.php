
<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Title/File</th>
                <th>Authors</th>
                <th>Genres</th>
                <th>Series</th>
                <th>Comment</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $allFiles = array_map('basename', glob('_ksiazki/*.*'));
            $processedBooks = array_column($manager->getProcessedBooks(), 'file_name');
            $booksData = $manager->getProcessedBooks();
            
            foreach ($allFiles as $file):
                $isProcessed = in_array($file, $processedBooks);
                $bookData = null;
                if ($isProcessed) {
                    $bookData = array_values(array_filter($booksData, function($book) use ($file) {
                        return $book['file_name'] === $file;
                    }))[0];
                }
            ?>
            <tr>
                <td>
                    <?php if ($isProcessed): ?>
                        <?php echo htmlspecialchars($bookData['title']); ?>
                    <?php else: ?>
                        <?php echo htmlspecialchars($file); ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($isProcessed): ?>
                        <?php echo implode(', ', array_map(function($author) {
                            return htmlspecialchars($author['first_name'] . ' ' . $author['last_name']);
                        }, $bookData['authors'])); ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($isProcessed): ?>
                        <?php echo htmlspecialchars(implode(', ', $bookData['genres'])); ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($isProcessed && $bookData['series']): ?>
                        <?php echo htmlspecialchars($bookData['series'] . ' #' . $bookData['series_position']); ?>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <?php if ($isProcessed && !empty($bookData['comment'])): ?>
                        <i class="bi bi-chat-right-text" title="<?php echo htmlspecialchars($bookData['comment']); ?>"></i>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($isProcessed): ?>
                        <button class="btn btn-warning btn-sm" onclick="editBook('<?php echo htmlspecialchars($file); ?>', 
                            '<?php echo htmlspecialchars($bookData['title']); ?>', 
                            '<?php echo htmlspecialchars($bookData['authors'][0]['first_name']); ?>', 
                            '<?php echo htmlspecialchars($bookData['authors'][0]['last_name']); ?>', 
                            '<?php echo htmlspecialchars(implode(', ', $bookData['genres'])); ?>', 
                            '<?php echo htmlspecialchars($bookData['series'] ?? ''); ?>', 
                            '<?php echo htmlspecialchars($bookData['series_position'] ?? ''); ?>', 
                            '<?php echo htmlspecialchars($bookData['comment'] ?? ''); ?>')">Edit</button>
                    <?php else: ?>
                        <button class="btn btn-primary btn-sm" onclick="editBook('<?php echo htmlspecialchars($file); ?>', '', '', '', '', '', '')">Add Metadata</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
