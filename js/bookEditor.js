
let editModal = null;

function initializeModal() {
    const modalElement = document.getElementById('editBookModal');
    if (modalElement) {
        editModal = new bootstrap.Modal(modalElement);
    }
}

function editBook(fileName, title, authorFirstName, authorLastName, genres, series, seriesPosition) {
    if (!editModal) {
        initializeModal();
    }
    
    document.getElementById('edit_file_name').value = fileName;
    document.getElementById('edit_title').value = title || '';
    
    // Clear existing authors
    const authorsContainer = document.getElementById('authors-container');
    authorsContainer.innerHTML = '';
    
    // Add first author
    addAuthorEntry(authorFirstName, authorLastName);
    
    document.getElementById('edit_genres').value = genres || '';
    document.getElementById('edit_series').value = series || '';
    document.getElementById('edit_series_position').value = seriesPosition || '';
    
    editModal.show();
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
    authorEntry.innerHTML = `
        <select class="form-select mb-2 author-select" onchange="handleAuthorSelect(this)">
            <option value="">Add new author</option>
            ${document.querySelector('.author-select').innerHTML.split('\n').slice(1).join('\n')}
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

document.addEventListener('DOMContentLoaded', initializeModal);
