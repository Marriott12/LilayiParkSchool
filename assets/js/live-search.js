/**
 * Live Search Functionality
 * Provides real-time search across different entity types
 */

class LiveSearch {
    constructor(config) {
        this.searchInput = document.querySelector(config.searchInput);
        this.resultsContainer = document.querySelector(config.resultsContainer);
        this.apiEndpoint = config.apiEndpoint;
        this.renderRow = config.renderRow;
        this.emptyMessage = config.emptyMessage || 'No results found';
        this.debounceDelay = config.debounceDelay || 300;
        this.minChars = config.minChars || 0;
        this.loadingIndicator = config.loadingIndicator;
        
        this.debounceTimer = null;
        this.currentRequest = null;
        
        this.init();
    }
    
    init() {
        if (!this.searchInput || !this.resultsContainer) {
            console.error('LiveSearch: Required elements not found');
            return;
        }
        
        // Listen for input events
        this.searchInput.addEventListener('input', (e) => {
            this.handleInput(e.target.value);
        });
        
        // Prevent form submission on enter
        this.searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
            }
        });
    }
    
    handleInput(searchTerm) {
        // Clear previous debounce timer
        clearTimeout(this.debounceTimer);
        
        // Debounce the search
        this.debounceTimer = setTimeout(() => {
            if (searchTerm.length >= this.minChars) {
                this.performSearch(searchTerm);
            } else if (searchTerm.length === 0) {
                this.performSearch(''); // Load all records
            }
        }, this.debounceDelay);
    }
    
    performSearch(searchTerm) {
        // Cancel previous request if exists
        if (this.currentRequest) {
            this.currentRequest.abort();
        }
        
        // Show loading state
        this.showLoading();
        
        // Create abort controller for this request
        const controller = new AbortController();
        this.currentRequest = controller;
        
        // Build URL with search term
        const url = new URL(this.apiEndpoint, window.location.origin);
        if (searchTerm) {
            url.searchParams.set('term', searchTerm);
        }
        
        // Perform fetch
        fetch(url, { signal: controller.signal, credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    this.renderResults(data.data);
                } else {
                    this.showError(data.error || 'An error occurred');
                }
            })
            .catch(error => {
                if (error.name !== 'AbortError') {
                    console.error('Search error:', error);
                    this.showError('Failed to load results');
                }
            })
            .finally(() => {
                this.currentRequest = null;
                this.hideLoading();
            });
    }
    
    renderResults(results) {
        if (!results || results.length === 0) {
            this.showEmpty();
            return;
        }
        
        const tbody = this.resultsContainer.querySelector('tbody');
        if (!tbody) {
            console.error('LiveSearch: tbody not found in results container');
            return;
        }
        
        tbody.innerHTML = results.map(item => this.renderRow(item)).join('');
    }
    
    showEmpty() {
        const tbody = this.resultsContainer.querySelector('tbody');
        if (!tbody) return;
        
        const colCount = this.resultsContainer.querySelector('thead tr')?.children.length || 5;
        tbody.innerHTML = `
            <tr>
                <td colspan="${colCount}" class="text-center py-4">
                    <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                    <p class="text-muted mt-2">${this.emptyMessage}</p>
                </td>
            </tr>
        `;
    }
    
    showLoading() {
        if (this.loadingIndicator) {
            const indicator = document.querySelector(this.loadingIndicator);
            if (indicator) {
                indicator.style.display = 'block';
            }
        }
        
        // Add loading class to search input
        this.searchInput.classList.add('is-loading');
    }
    
    hideLoading() {
        if (this.loadingIndicator) {
            const indicator = document.querySelector(this.loadingIndicator);
            if (indicator) {
                indicator.style.display = 'none';
            }
        }
        
        // Remove loading class from search input
        this.searchInput.classList.remove('is-loading');
    }
    
    showError(message) {
        const tbody = this.resultsContainer.querySelector('tbody');
        if (!tbody) return;
        
        const colCount = this.resultsContainer.querySelector('thead tr')?.children.length || 5;
        tbody.innerHTML = `
            <tr>
                <td colspan="${colCount}" class="text-center py-4 text-danger">
                    <i class="bi bi-exclamation-triangle" style="font-size: 3rem;"></i>
                    <p class="mt-2">${message}</p>
                </td>
            </tr>
        `;
    }
}

// Make it globally available
window.LiveSearch = LiveSearch;
