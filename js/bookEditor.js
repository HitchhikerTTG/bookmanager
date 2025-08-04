function toggleEditForm(rowId) {
    const form = document.getElementById('edit-form-' + rowId);
    if (form) {
        form.classList.toggle('d-none');
        if (!form.classList.contains('d-none')) {
            initializeAuthors(rowId);
            initializeAutocomplete(rowId);
        }
    }
}

function initializeAuthors(rowId) {
    currentRowId = rowId;
    const container = document.getElementById('authors-container-' + rowId);
    if (container) {
        container.innerHTML = '';
        const form = document.getElementById('editBookForm-' + rowId);
        const authorData = form ? form.getAttribute('data-authors') : null;
        
        if (authorData) {
            try {
                const authors = JSON.parse(authorData);
                if (authors && authors.length > 0) {
                    authors.forEach(author => {
                        addAuthorEntry(author.first_name || '', author.last_name || '', rowId);
                    });
                } else {
                    addAuthorEntry('', '', rowId);
                }
            } catch (e) {
                console.error('Error parsing author data:', e);
                addAuthorEntry('', '', rowId);
            }
        } else {
            addAuthorEntry('', '', rowId);
        }
        
        // Initialize autocomplete for all existing author entries
        container.querySelectorAll('.author-entry').forEach(entry => {
            initializeSimpleAuthorAutocomplete(entry, rowId);
        });
    }
}

function initializeAutocomplete(rowId) {
    // Initialize series typeahead
    const seriesInput = document.getElementById('series-' + rowId);
    if (!seriesInput || $(seriesInput).data('ttTypeahead')) return; // Skip if already initialized
    
    const availableSeries = JSON.parse(document.getElementById('available-series-' + rowId).value || '[]');
    
    if (availableSeries.length > 0) {
        const seriesBloodhound = new Bloodhound({
            datumTokenizer: Bloodhound.tokenizers.whitespace,
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            local: availableSeries
        });

        $(seriesInput).typeahead({
            hint: true,
            highlight: true,
            minLength: 1
        },
        {
            name: 'series',
            source: seriesBloodhound,
            limit: 10
        });
    }
}

function editBook(fileName, title, genres, series, seriesPosition, comment) {
    const rowId = fileName.replace(/[^a-zA-Z0-9]/g, '');
    toggleEditForm(rowId);
    
    const form = document.getElementById('editBookForm-' + rowId);
    if (form) {
        const titleInput = form.querySelector('input[name="title"]');
        const genresInput = document.getElementById('genres-' + rowId);
        const seriesInput = form.querySelector('input[name="series"]');
        const seriesPosInput = form.querySelector('input[name="series_position"]');
        const commentInput = form.querySelector('textarea[name="comment"]');
        
        if (titleInput) titleInput.value = title || '';
        if (genresInput) $(genresInput).tagsinput('add', genres);
        if (seriesInput) seriesInput.value = series || '';
        if (seriesPosInput) seriesPosInput.value = seriesPosition || '';
        if (commentInput) commentInput.value = comment || '';
    }
}



let currentRowId = null;

function addAuthorEntry(firstName = '', lastName = '', rowId) {
    if (!rowId && currentRowId) {
        rowId = currentRowId;
    }
    
    const container = document.getElementById('authors-container-' + rowId);
    if (!container) {
        console.error('Authors container not found for rowId:', rowId);
        return;
    }
    
    const index = container.children.length;
    const authorEntry = document.createElement('div');
    authorEntry.className = 'author-entry mb-3 border p-3 rounded';
    authorEntry.setAttribute('data-author-index', index);
    
    authorEntry.innerHTML = `
        <div class="row g-2">
            <div class="col-md-5">
                <label class="form-label small">Imię</label>
                <input type="text" class="form-control author-first-name" 
                       name="authors[${index}][first_name]"
                       value="${firstName}" 
                       placeholder="Imię autora" 
                       required>
            </div>
            <div class="col-md-5">
                <label class="form-label small">Nazwisko</label>
                <input type="text" class="form-control author-last-name" 
                       name="authors[${index}][last_name]"
                       value="${lastName}" 
                       placeholder="Nazwisko autora" 
                       required>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                ${index > 0 ? `<button type="button" class="btn btn-danger btn-sm" onclick="removeAuthor(this, '${rowId}')">
                    <i class="bi bi-trash"></i>
                </button>` : ''}
            </div>
        </div>
    `;
    
    container.appendChild(authorEntry);
    initializeSimpleAuthorAutocomplete(authorEntry, rowId);
}

function addAuthor(rowId) {
    if (rowId) {
        currentRowId = rowId;
    }
    addAuthorEntry('', '', rowId || currentRowId);
}

function removeAuthor(button, rowId) {
    const authorEntry = button.closest('.author-entry');
    if (authorEntry) {
        authorEntry.remove();
        updateAuthorIndices(rowId);
    }
}

function updateAuthorIndices(rowId) {
    const container = document.getElementById('authors-container-' + rowId);
    if (!container) return;
    
    const entries = container.querySelectorAll('.author-entry');
    entries.forEach((entry, index) => {
        entry.setAttribute('data-author-index', index);
        
        const firstNameInput = entry.querySelector('input[name*="[first_name]"]');
        const lastNameInput = entry.querySelector('input[name*="[last_name]"]');
        
        if (firstNameInput) firstNameInput.name = `authors[${index}][first_name]`;
        if (lastNameInput) lastNameInput.name = `authors[${index}][last_name]`;
    });
}

function initializeSimpleAuthorAutocomplete(authorEntry, rowId) {
    const firstNameInput = authorEntry.querySelector('.author-first-name');
    const lastNameInput = authorEntry.querySelector('.author-last-name');
    
    const availableAuthorsElement = document.getElementById('available-authors-' + rowId);
    const authors = availableAuthorsElement ? JSON.parse(availableAuthorsElement.value || '[]') : [];
    
    if (authors.length > 0) {
        // Create datalists for first names and last names
        const firstNames = new Set();
        const lastNames = new Set();
        
        authors.forEach(author => {
            if (typeof author === 'string' && author.includes('|')) {
                const [firstName, lastName] = author.split('|');
                if (firstName.trim()) firstNames.add(firstName.trim());
                if (lastName.trim()) lastNames.add(lastName.trim());
            }
        });
        
        // Create and attach datalist for first names
        const firstNameDatalistId = `firstnames-${rowId}-${Date.now()}`;
        const firstNameDatalist = document.createElement('datalist');
        firstNameDatalist.id = firstNameDatalistId;
        firstNames.forEach(name => {
            const option = document.createElement('option');
            option.value = name;
            firstNameDatalist.appendChild(option);
        });
        document.body.appendChild(firstNameDatalist);
        firstNameInput.setAttribute('list', firstNameDatalistId);
        
        // Create and attach datalist for last names
        const lastNameDatalistId = `lastnames-${rowId}-${Date.now()}`;
        const lastNameDatalist = document.createElement('datalist');
        lastNameDatalist.id = lastNameDatalistId;
        lastNames.forEach(name => {
            const option = document.createElement('option');
            option.value = name;
            lastNameDatalist.appendChild(option);
        });
        document.body.appendChild(lastNameDatalist);
        lastNameInput.setAttribute('list', lastNameDatalistId);
    }
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
        alert('Przynajmniej jeden autor z imieniem i nazwiskiem jest wymagany');
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
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            window.location.href = 'aniol.php';
        } else if (data.error) {
            console.error('Server error:', data.error);
            alert(data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (error.name === 'SyntaxError') {
            alert('Invalid response from server');
        } else {
            alert(error.message || 'Error saving book');
        }
    });
    
    return false;
}

function addGenre(rowId, genre) {
    const input = document.getElementById(`genres-${rowId}`);
    const container = document.getElementById(`selected-genres-${rowId}`);
    const currentGenres = input.value ? input.value.split(',').map(g => g.trim()) : [];
    
    if (!currentGenres.includes(genre)) {
        currentGenres.push(genre);
        input.value = currentGenres.join(', ');
        
        const badge = document.createElement('span');
        badge.className = 'badge bg-primary genre-badge';
        badge.style.margin = '2px';
        badge.innerHTML = `${genre} <i class="bi bi-x" onclick="removeGenre('${rowId}', '${genre}')" style="cursor: pointer;"></i>`;
        container.appendChild(badge);
    }
}

function removeGenre(rowId, genre) {
    const input = document.getElementById(`genres-${rowId}`);
    const container = document.getElementById(`selected-genres-${rowId}`);
    let currentGenres = input.value.split(',').map(g => g.trim());
    
    currentGenres = currentGenres.filter(g => g !== genre);
    input.value = currentGenres.join(', ');
    
    const badges = container.getElementsByClassName('genre-badge');
    for (let badge of badges) {
        if (badge.textContent.includes(genre)) {
            badge.remove();
            break;
        }
    }
}

function addNewGenre(rowId) {
    const genre = prompt('Enter new genre:');
    if (genre && genre.trim()) {
        addGenre(rowId, genre.trim());
    }
}

function validateForm(form) {
    if (!form) return false;
    
    const title = form.querySelector('[name="title"]')?.value;
    const authors = form.querySelectorAll('.author-entry');
    const genres = form.querySelector('[name="genres"]')?.value;
    const seriesPosition = form.querySelector('[name="series_position"]')?.value;
    
    if (!title) {
        alert('Title is required');
        return false;
    }
    if (authors.length === 0) {
        alert('At least one author is required');
        return false;
    }
    if (!genres) {
        alert('At least one genre is required');
        return false;
    }
    if (seriesPosition && isNaN(seriesPosition)) {
        alert('Series position must be a number');
        return false;
    }
    
    return true;
}

document.addEventListener('DOMContentLoaded', () => {
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