<input type="hidden" id="available-authors" value='<?php echo json_encode($manager->getLists()['authors']); ?>'>
<div class="modal fade" id="editBookModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Book</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="process_book.php" method="post">
                <div class="modal-body">
                    <input type="hidden" id="edit_file_name" name="file_name">
                    
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" id="edit_title" name="title" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Authors</label>
                        <div id="authors-container"></div>
                        <button type="button" class="btn btn-secondary btn-sm mt-2" onclick="addAuthor()">Add Author</button>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Genres (comma separated)</label>
                        <input type="text" class="form-control" id="edit_genres" name="genres" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Series (optional)</label>
                        <input type="text" class="form-control" id="edit_series" name="series">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Series Position (optional)</label>
                        <input type="number" class="form-control" id="edit_series_position" name="series_position">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Comment (optional)</label>
                        <textarea class="form-control" id="edit_comment" name="comment" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>