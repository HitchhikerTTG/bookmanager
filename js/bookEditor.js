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
                addAuthorEntry('', '', rowId);
            }
        } else {
            addAuthorEntry('', '', rowId);
        }
        
        container.querySelectorAll('.author-entry').forEach(entry => {
            initializeAuthorAutocomplete(entry, rowId);
        });
    }
}

function initializeAutocomplete(rowId) {
    // Initialize series datalist (same pattern as authors)
    const seriesInput = document.getElementById('series-' + rowId);
    if (!seriesInput) return;
    
    // Clean up existing datalist if any
    const existingDatalistId = seriesInput.getAttribute('list');
    if (existingDatalistId) {
        const existingDatalist = document.getElementById(existingDatalistId);
        if (existingDatalist) {
            existingDatalist.remove();
        }
    }
    
    const availableSeriesElement = document.getElementById('available-series-' + rowId);
    if (!availableSeriesElement) {
        return;
    }
    
    const availableSeries = JSON.parse(availableSeriesElement.value || '[]');
    
    if (availableSeries.length > 0) {
        // Create datalist for series with consistent ID
        const datalistId = `series-datalist-${rowId}`;
        const datalist = document.createElement('datalist');
        datalist.id = datalistId;
        
        availableSeries.forEach(series => {
            const option = document.createElement('option');
            option.value = series;
            datalist.appendChild(option);
        });
        
        document.body.appendChild(datalist);
        seriesInput.setAttribute('list', datalistId);
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
        if (genresInput) {
            // Set genres value and update visual display
            genresInput.value = genres || '';
            // Update the visual genre badges
            updateGenreDisplay(rowId, genres);
        }
        if (seriesInput) seriesInput.value = series || '';
        if (seriesPosInput) seriesPosInput.value = seriesPosition || '';
        if (commentInput) commentInput.value = comment || '';
        
        // Initialize autocomplete for series after form is visible
        initializeAutocomplete(rowId);
    }
}

function updateGenreDisplay(rowId, genres) {
    const container = document.getElementById(`selected-genres-${rowId}`);
    if (!container || !genres) return;
    
    // Clear existing badges
    container.innerHTML = '';
    
    // Add badges for each genre
    const genreList = genres.split(',').map(g => g.trim()).filter(g => g);
    genreList.forEach(genre => {
        const badge = document.createElement('span');
        badge.className = 'badge bg-primary genre-badge';
        badge.style.margin = '2px';
        badge.innerHTML = `${genre} <i class="bi bi-x" onclick="removeGenre('${rowId}', '${genre}')" style="cursor: pointer;"></i>`;
        container.appendChild(badge);
    });
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
    
    // Format: "firstName, lastName" or just existing values
    const fullName = (firstName && lastName) ? `${firstName}, ${lastName}` : '';
    
    authorEntry.innerHTML = `
        <div class="row g-2">
            <div class="col-md-10">
                <label class="form-label small">Autor (Imię, Nazwisko)</label>
                <input type="text" class="form-control author-full-name" 
                       name="authors[${index}][full_name]"
                       value="${fullName}" 
                       placeholder="np. Jan, Kowalski" 
                       required>
                <small class="text-muted">Wpisz imię i nazwisko oddzielone przecinkiem</small>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                ${index > 0 ? `<button type="button" class="btn btn-danger btn-sm" onclick="removeAuthor(this, '${rowId}')">
                    <i class="bi bi-trash"></i>
                </button>` : ''}
            </div>
        </div>
    `;
    
    container.appendChild(authorEntry);
    initializeAuthorAutocomplete(authorEntry, rowId);
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
        
        const fullNameInput = entry.querySelector('input[name*="[full_name]"]');
        if (fullNameInput) fullNameInput.name = `authors[${index}][full_name]`;
    });
}

function initializeAuthorAutocomplete(authorEntry, rowId) {
    const authorInput = authorEntry.querySelector('.author-full-name');
    
    const availableAuthorsElement = document.getElementById('available-authors-' + rowId);
    const authors = availableAuthorsElement ? JSON.parse(availableAuthorsElement.value || '[]') : [];
    
    if (authors.length > 0) {
        const authorSuggestions = [];
        
        authors.forEach(author => {
            if (typeof author === 'string' && author.includes('|')) {
                const [firstName, lastName] = author.split('|');
                if (firstName.trim() && lastName.trim()) {
                    authorSuggestions.push(`${firstName.trim()}, ${lastName.trim()}`);
                }
            }
        });
        
        // Create datalist for full author names
        const datalistId = `authors-${rowId}-${Date.now()}`;
        const datalist = document.createElement('datalist');
        datalist.id = datalistId;
        
        authorSuggestions.forEach(authorName => {
            const option = document.createElement('option');
            option.value = authorName;
            datalist.appendChild(option);
        });
        
        document.body.appendChild(datalist);
        authorInput.setAttribute('list', datalistId);
    }
}

function submitBookForm(form) {
    const formData = new FormData(form);
    
    // Sanitize genres input
    const genres = formData.get('genres');
    if (genres) {
        formData.set('genres', genres.split(',').map(g => g.trim()).filter(g => g).join(','));
    }
    
    // Validate authors
    const authorEntries = form.querySelectorAll('.author-entry');
    let hasValidAuthor = false;
    authorEntries.forEach((entry, index) => {
        const fullName = entry.querySelector(`input[name="authors[${index}][full_name]"]`).value.trim();
        if (fullName && fullName.includes(',')) {
            const [firstName, lastName] = fullName.split(',').map(part => part.trim());
            if (firstName && lastName) {
                hasValidAuthor = true;
            }
        }
    });
    
    if (!hasValidAuthor) {
        alert('Przynajmniej jeden autor w formacie "Imię, Nazwisko" jest wymagany');
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
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            window.location.href = 'aniol.php';
        } else if (data.error) {
            alert(data.error);
        }
    })
    .catch(error => {
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