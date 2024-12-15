
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
                    <?php 
                    $rowId = preg_replace('/[^a-zA-Z0-9]/', '', $file);
                    if ($isProcessed): ?>
                        <button class="btn btn-warning btn-sm" onclick="editBook('<?php echo htmlspecialchars($file); ?>', 
                            '<?php echo htmlspecialchars($bookData['title']); ?>', 
                            '<?php echo htmlspecialchars(implode(', ', $bookData['genres'])); ?>', 
                            '<?php echo htmlspecialchars($bookData['series'] ?? ''); ?>', 
                            '<?php echo htmlspecialchars($bookData['series_position'] ?? ''); ?>', 
                            '<?php echo htmlspecialchars($bookData['comment'] ?? ''); ?>')">Edit</button>
                    <?php else: ?>
                        <button class="btn btn-primary btn-sm" onclick="editBook('<?php echo htmlspecialchars($file); ?>', '', '', '', '', '', '')">Add Metadata</button>
                    <?php endif; ?>
                </td>
            </tr>
            <tr id="edit-form-<?php echo $rowId; ?>" class="d-none">
                <td colspan="6">
                    <form id="editBookForm-<?php echo $rowId; ?>" 
                          onsubmit="return submitBookForm(this)" 
                          class="p-3 bg-light"
                          <?php if ($isProcessed): ?>
                          data-authors='<?php echo json_encode($bookData['authors']); ?>'
                          <?php endif; ?>>
                        <input type="hidden" name="file_name" value="<?php echo htmlspecialchars($file); ?>">
                        <input type="hidden" id="available-genres-<?php echo $rowId; ?>" value='<?php echo json_encode($manager->getLists()['genres']); ?>'>
                        <input type="hidden" id="available-authors-<?php echo $rowId; ?>" value='<?php echo json_encode($manager->getLists()['authors']); ?>'>
                        <input type="hidden" id="available-series-<?php echo $rowId; ?>" value='<?php echo json_encode($manager->getLists()['series']); ?>'>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" class="form-control" name="title" value="<?php echo $isProcessed ? htmlspecialchars($bookData['title']) : ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Genres</label>
                                <input type="text" class="form-control" name="genres" id="genres-<?php echo $rowId; ?>" 
                                       value="<?php echo $isProcessed ? htmlspecialchars(implode(', ', $bookData['genres'])) : ''; ?>" 
                                       data-role="tagsinput" required>
                            </div>
                        </div>
                        <div id="authors-container-<?php echo $rowId; ?>"></div>
                        <button type="button" class="btn btn-secondary btn-sm mt-2" onclick="addAuthor()">Add Author</button>
                        <div class="row mt-3">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Series</label>
                                <input type="text" class="form-control" name="series" id="series-<?php echo $rowId; ?>" 
                                       value="<?php echo $isProcessed ? htmlspecialchars($bookData['series'] ?? '') : ''; ?>" 
                                       list="series-list-<?php echo $rowId; ?>">
                                <datalist id="series-list-<?php echo $rowId; ?>">
                                    <?php foreach ($manager->getLists()['series'] as $series): ?>
                                        <option value="<?php echo htmlspecialchars($series); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Series Position</label>
                                <input type="number" class="form-control" name="series_position" 
                                       value="<?php echo $isProcessed ? htmlspecialchars($bookData['series_position'] ?? '') : ''; ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Comment</label>
                            <textarea class="form-control" name="comment" rows="3"><?php echo $isProcessed ? htmlspecialchars($bookData['comment'] ?? '') : ''; ?></textarea>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-primary">Save</button>
                                <button type="button" class="btn btn-secondary" onclick="toggleEditForm('<?php echo $rowId; ?>')">Cancel</button>
                            </div>
                        </div>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
