
# E-Book Library System Documentation

## System Overview
The system manages an e-book library with two main components:
- `aniol.php`: Book management interface
- `ksiazki.html`: Public-facing library catalog

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

3. Book Addition Form
   - File upload
   - Metadata input fields:
     - Title (required)
     - Authors (comma-separated, required)
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

## Data Management
1. Book Processing
   - Automatic metadata storage
   - JSON data structure
   - File management in _ksiazki directory

2. Reference Lists
   - Authors list auto-updates
   - Genres list auto-updates
   - Series list auto-updates

## Technical Implementation
1. File Operations
   - JSON read/write for data storage
   - Directory creation if missing
   - File upload handling

2. UI Components
   - Bootstrap 5.3.2
   - Responsive design
   - Tab-based navigation
   - Pagination system

3. Security
   - Input sanitization
   - HTML escaping
   - Relative paths for domain independence

## Update History
- Initial setup: Basic file structure and BookManager class
- Added: Bootstrap integration
- Added: Tab navigation system
- Added: Pagination for book lists
- Added: Status bar with library statistics
- Added: HTML generation system
