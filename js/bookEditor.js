
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
    document.getElementById('edit_comment').value = comment || '';
    
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
    const formData = new FormData(form);
    
    fetch('process_book.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            editModal.hide();
            location.reload();
        } else {
            alert(data.error || 'Wystąpił błąd podczas zapisywania książki');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert(`Błąd: ${error.message || 'Wystąpił nieznany błąd podczas zapisywania książki'}`);
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
