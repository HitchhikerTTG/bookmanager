
let editModal = null;

function initializeModal() {
    const modalElement = document.getElementById('editBookModal');
    if (modalElement) {
        editModal = new bootstrap.Modal(modalElement);
    }
}

function editBook(fileName, title, authorFirstName, authorLastName, genres, series, seriesPosition, comment) {
    if (!editModal) {
        initializeModal();
    }
    
    const fileNameInput = document.getElementById('edit_file_name');
    const titleInput = document.getElementById('edit_title');
    
    if (fileNameInput && titleInput) {
        fileNameInput.value = fileName;
        titleInput.value = title || '';
    
    // Clear existing authors
    const authorsContainer = document.getElementById('authors-container');
    authorsContainer.innerHTML = '';
    
    // Add first author
    addAuthorEntry(authorFirstName, authorLastName);
    
    const genresInput = document.getElementById('edit_genres');
    const seriesInput = document.getElementById('edit_series');
    const seriesPosInput = document.getElementById('edit_series_position');
    const commentInput = document.getElementById('edit_comment');
    
    if (genresInput) genresInput.value = genres || '';
    if (seriesInput) seriesInput.value = series || '';
    if (seriesPosInput) seriesPosInput.value = seriesPosition || '';
    if (commentInput) commentInput.value = comment || '';
    
    if (editModal) editModal.show();
}
}

function handleAuthorSelect(select) {
    const container = select.closest('.author-entry');
    const [firstName, lastName] = select.value ? select.value.split('|') : ['', ''];
    const inputs = container.querySelector('.author-inputs');
    inputs.querySelector('input[name$="[first_name]"]').value = firstName;
    inputs.querySelector('input[name$="[last_name]"]').value = lastName;
}

function addAuthorEntry(firstName = '', lastName = '') {
    const container = document.getElementById('authors-container');
    const index = container.children.length;
    const authorEntry = document.createElement('div');
    authorEntry.className = 'author-entry mb-2';
    const authors = JSON.parse(document.getElementById('available-authors').value || '[]');
    const authorOptions = authors.map(author => `<option value="${author}">${author.replace('|', ' ')}</option>`).join('\n');
    
    authorEntry.innerHTML = `
        <select class="form-select mb-2 author-select" onchange="handleAuthorSelect(this)">
            <option value="">Add new author</option>
            ${authorOptions}
        </select>
        <div class="author-inputs">
            <input type="text" class="form-control mb-2" name="authors[${index}][first_name]" value="${firstName}" placeholder="First Name" required>
            <input type="text" class="form-control" name="authors[${index}][last_name]" value="${lastName}" placeholder="Last Name" required>
        </div>
        ${index > 0 ? '<button type="button" class="btn btn-danger btn-sm mt-1" onclick="this.parentElement.remove()">Remove</button>' : ''}
    `;
    container.appendChild(authorEntry);
}

function addAuthor() {
    addAuthorEntry();
}

function submitBookForm(form) {
    console.log('Starting form submission');
    const formData = new FormData(form);
    console.log('Form data created:', Object.fromEntries(formData));
    
    // Sanitize genres input
    const genres = formData.get('genres');
    if (genres) {
        formData.set('genres', genres.split(',').map(g => g.trim()).filter(g => g).join(','));
    }
    
    // Validate authors
    const authorEntries = form.querySelectorAll('.author-entry');
    let hasValidAuthor = false;
    authorEntries.forEach((entry, index) => {
        const firstName = entry.querySelector(`input[name="authors[${index}][first_name]"]`).value.trim();
        const lastName = entry.querySelector(`input[name="authors[${index}][last_name]"]`).value.trim();
        if (firstName && lastName) {
            hasValidAuthor = true;
        }
    });
    
    if (!hasValidAuthor) {
        alert('At least one author is required');
        return false;
    }
    
    fetch('process_book.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        console.log('Response received:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            window.location.href = 'aniol.php';
        } else {
            alert(data.error || 'Error saving book');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving book: ' + error.message);
    });
    
    return false;
}

function validateForm(form) {
    const title = form.querySelector('[name="title"]').value;
    const authors = form.querySelectorAll('.author-entry');
    const genres = form.querySelector('[name="genres"]').value;
    
    if (!title) return false;
    if (authors.length === 0) return false;
    if (!genres) return false;
    
    return true;
}

document.addEventListener('DOMContentLoaded', () => {
    initializeModal();
    
    const form = document.getElementById('editBookForm');
    if (form) {
        form.onsubmit = (e) => {
            e.preventDefault();
            if (validateForm(form)) {
                submitBookForm(form);
            }
        };
    }
});
