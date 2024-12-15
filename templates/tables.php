
<ul class="nav nav-tabs" id="myTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="unprocessed-tab" data-bs-toggle="tab" data-bs-target="#unprocessed" type="button" role="tab">Unprocessed</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="processed-tab" data-bs-toggle="tab" data-bs-target="#processed" type="button" role="tab">Processed</button>
    </li>
</ul>

<div class="tab-content" id="myTabContent">
    <div class="tab-pane fade show active" id="unprocessed" role="tabpanel">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>File Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($manager->getUnprocessedBooks() as $book): ?>
                <tr>
                    <td><?php echo htmlspecialchars($book); ?></td>
                    <td>
                        <button class="btn btn-primary btn-sm" onclick="editBook('<?php echo htmlspecialchars($book); ?>')">Add Metadata</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="tab-pane fade" id="processed" role="tabpanel">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Authors</th>
                    <th>Genres</th>
                    <th>Series</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($manager->getProcessedBooks() as $book): ?>
                <tr>
                    <td><?php echo htmlspecialchars($book['title']); ?></td>
                    <td><?php echo implode(', ', array_map(function($author) {
                        return htmlspecialchars($author['first_name'] . ' ' . $author['last_name']);
                    }, $book['authors'])); ?></td>
                    <td><?php echo htmlspecialchars(implode(', ', $book['genres'])); ?></td>
                    <td><?php echo $book['series'] ? htmlspecialchars($book['series'] . ' #' . $book['series_position']) : ''; ?></td>
                    <td>
                        <button class="btn btn-warning btn-sm" onclick="editBook('<?php echo htmlspecialchars($book['file_name']); ?>', 
                            '<?php echo htmlspecialchars($book['title']); ?>', 
                            '<?php echo htmlspecialchars($book['authors'][0]['first_name']); ?>', 
                            '<?php echo htmlspecialchars($book['authors'][0]['last_name']); ?>', 
                            '<?php echo htmlspecialchars(implode(', ', $book['genres'])); ?>', 
                            '<?php echo htmlspecialchars($book['series'] ?? ''); ?>', 
                            '<?php echo htmlspecialchars($book['series_position'] ?? ''); ?>')">Edit</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
