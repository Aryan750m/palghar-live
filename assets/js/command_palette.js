// assets/js/command_palette.js
// Accessible Ctrl+K command search console

document.addEventListener('DOMContentLoaded', () => {
    // Generate overlay dynamically
    const overlay = document.createElement('div');
    overlay.className = 'command-palette-overlay';
    overlay.id = 'command-palette-overlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', 'Command Palette Search');

    overlay.innerHTML = `
        <div class="command-palette">
            <div class="command-palette-header">
                <i class="fas fa-search"></i>
                <input type="text" class="command-palette-input" id="command-palette-input" placeholder="Search news portal... (Arrow keys to navigate, Esc to close)" autocomplete="off">
            </div>
            <div class="command-palette-results" id="command-palette-results" role="listbox">
                <div class="command-item empty-state">Type to begin searching...</div>
            </div>
        </div>
    `;

    document.body.appendChild(overlay);

    const input = document.getElementById('command-palette-input');
    const resultsContainer = document.getElementById('command-palette-results');
    let selectedIndex = -1;
    let debounceTimer = null;

    // Listen for Ctrl+K trigger shortcut
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            togglePalette();
        }
    });

    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            closePalette();
        }
    });

    input.addEventListener('keydown', (e) => {
        const items = resultsContainer.querySelectorAll('.command-item:not(.empty-state)');
        
        if (e.key === 'Escape') {
            closePalette();
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (items.length > 0) {
                selectedIndex = (selectedIndex + 1) % items.length;
                updateSelection(items);
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (items.length > 0) {
                selectedIndex = (selectedIndex - 1 + items.length) % items.length;
                updateSelection(items);
            }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (selectedIndex >= 0 && items[selectedIndex]) {
                items[selectedIndex].click();
            }
        }
    });

    input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const query = input.value.trim();
        
        if (query === '') {
            resultsContainer.innerHTML = '<div class="command-item empty-state">Type to begin searching...</div>';
            return;
        }

        debounceTimer = setTimeout(() => {
            fetchResults(query);
        }, 200);
    });

    function togglePalette() {
        overlay.classList.toggle('open');
        if (overlay.classList.contains('open')) {
            input.focus();
            input.value = '';
            resultsContainer.innerHTML = '<div class="command-item empty-state">Type to begin searching...</div>';
            selectedIndex = -1;
        }
    }

    function closePalette() {
        overlay.classList.remove('open');
        input.blur();
    }

    function updateSelection(items) {
        items.forEach((item, index) => {
            if (index === selectedIndex) {
                item.classList.add('selected');
                item.setAttribute('aria-selected', 'true');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('selected');
                item.setAttribute('aria-selected', 'false');
            }
        });
    }

    function fetchResults(query) {
        // Fetch matching articles from our AJAX search endpoint
        fetch(`search.php?q=${encodeURIComponent(query)}&ajax=1`)
            .then(res => res.json())
            .then(data => {
                resultsContainer.innerHTML = '';
                selectedIndex = -1;
                
                if (data.length === 0) {
                    resultsContainer.innerHTML = '<div class="command-item empty-state">No matching articles found.</div>';
                    return;
                }
                
                data.forEach((article, idx) => {
                    const item = document.createElement('div');
                    item.className = 'command-item';
                    item.setAttribute('role', 'option');
                    item.setAttribute('id', `option-${idx}`);
                    item.innerHTML = `
                        <i class="far fa-file-alt"></i>
                        <span>${escapeHtml(article.title)}</span>
                    `;
                    
                    item.addEventListener('click', () => {
                        window.location.href = `news/${article.id}/${slugify(article.title)}`;
                    });
                    
                    resultsContainer.appendChild(item);
                });
            })
            .catch(() => {
                resultsContainer.innerHTML = '<div class="command-item empty-state error">Search error. Please try again.</div>';
            });
    }

    function escapeHtml(str) {
        return str.replace(/&/g, '&amp;')
                  .replace(/</g, '&lt;')
                  .replace(/>/g, '&gt;')
                  .replace(/"/g, '&quot;')
                  .replace(/'/g, '&#039;');
    }

    function slugify(text) {
        return text.toString().toLowerCase()
            .replace(/\s+/g, '-')
            .replace(/[^\w\-]+/g, '')
            .replace(/\-\-+/g, '-')
            .replace(/^-+/, '')
            .replace(/-+$/, '');
    }
});
