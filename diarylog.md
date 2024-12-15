
# E-Book Library System Documentation

## System Overview
The system manages an e-book library with two main components:
- `aniol.php`: Book management interface
- `ksiazki.html`: Public-facing library catalog

## Project Structure
- `/data/`: Data storage
  - `books.json`: Stores book metadata
  - `lists.json`: Stores reference lists
- `/includes/`: PHP classes and functions
  - `BookManager.php`: Core library management logic
  - `functions.php`: Helper functions
- `/templates/`: UI components
  - `header.php`: Navigation and statistics bar
  - `alerts.php`: Success/error messages
  - `tables.php`: Book listing tables
  - `modals.php`: Edit/add book forms
- `/js/`: JavaScript files
  - `bookEditor.js`: Book editing functionality

## Data Structure
- `data/books.json`: Stores book metadata
- `data/lists.json`: Stores reference lists (authors, genres, series)
- `_ksiazki/`: Directory containing actual e-book files

## Core Features

### Book Manager (aniol.php)
1. Status Bar
   - Shows total books in folder
   - Shows books with metadata
   - Displays last HTML update time
   - HTML generation button

2. Tab Navigation
   - "Nieopisane": Shows unprocessed books
   - "Gotowe": Shows processed books
   - Both tabs implement pagination (10 items per page)

3. Book Addition/Edit Form
   - File name (hidden)
   - Title (required)
   - Authors management
     - Add/remove multiple authors
     - First name and last name fields
   - Genres (comma-separated, required)
   - Series (optional)
   - Series Position (optional)

### Public Catalog (ksiazki.html)
- Bootstrap-based responsive layout
- Card-based book display
- Shows:
  - Title
  - Authors
  - Genres
  - Series info (if available)
  - Upload date
  - Download link

## Technical Implementation
1. File Operations
   - JSON read/write for data storage
   - Directory creation if missing
   - File upload handling

2. UI Components
   - Bootstrap 5.3.2
   - Responsive design
   - Modal dialogs for editing
   - Tab-based navigation
   - Pagination system

3. Security
   - Input sanitization
   - HTML escaping
   - Session management
   - Relative paths for domain independence

## JavaScript Features
- Dynamic author fields management
- Modal form handling
- Book editing functionality
- Real-time form validation

## Update History
- Initial setup: Basic file structure and BookManager class
- Added: Bootstrap integration
- Added: Tab navigation system
- Added: Pagination for book lists
- Added: Status bar with library statistics
- Added: Template-based structure
- Added: Dynamic author management
- Added: Modal-based editing system
