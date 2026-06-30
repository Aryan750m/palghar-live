// assets/js/news_detail_page.js
// Handles article details interactive options: Reading progress, Bookmarks, Reading History, and Share API

document.addEventListener('DOMContentLoaded', () => {
    const articleId = document.body.getAttribute('data-article-id');
    const articleTitle = document.body.getAttribute('data-article-title');
    
    // Initialize reading progress bar
    initReadingProgress();
    
    // Register reading history
    if (articleId && articleTitle) {
        logReadingHistory(articleId, articleTitle);
    }
    
    // Bookmarking logic
    const bookmarkBtn = document.getElementById('bookmark-btn');
    if (bookmarkBtn && articleId) {
        initBookmark(bookmarkBtn, articleId, articleTitle);
    }
    
    // Social Web Share API logic
    const shareBtn = document.getElementById('share-btn');
    if (shareBtn && articleTitle) {
        initShareButton(shareBtn, articleTitle);
    }
    
    // Copy link helper
    const copyLinkBtn = document.getElementById('copy-link-btn');
    if (copyLinkBtn) {
        initCopyLink(copyLinkBtn);
    }
});

// 1. Reading Progress indicator
function initReadingProgress() {
    const progressBar = document.getElementById('reading-progress-bar');
    if (!progressBar) return;
    
    window.addEventListener('scroll', () => {
        const scrollTop = window.scrollY;
        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
        if (docHeight > 0) {
            const pct = (scrollTop / docHeight) * 100;
            progressBar.style.width = pct + '%';
        }
    });
}

// 2. Local Bookmarking logic
function initBookmark(btn, id, title) {
    let bookmarks = JSON.parse(localStorage.getItem('bookmarks') || '[]');
    
    // Update button visual state
    updateBookmarkButtonState(btn, bookmarks.includes(id));
    
    btn.addEventListener('click', () => {
        bookmarks = JSON.parse(localStorage.getItem('bookmarks') || '[]');
        const idx = bookmarks.indexOf(id);
        
        if (idx === -1) {
            bookmarks.push(id);
            localStorage.setItem('bookmarks', JSON.stringify(bookmarks));
            updateBookmarkButtonState(btn, true);
            if (typeof showToast === 'function') {
                showToast('Article added to bookmarks!', 'success');
            }
        } else {
            bookmarks.splice(idx, 1);
            localStorage.setItem('bookmarks', JSON.stringify(bookmarks));
            updateBookmarkButtonState(btn, false);
            if (typeof showToast === 'function') {
                showToast('Article removed from bookmarks.', 'info');
            }
        }
    });
}

function updateBookmarkButtonState(btn, isBookmarked) {
    const icon = btn.querySelector('i');
    if (!icon) return;
    
    if (isBookmarked) {
        icon.className = 'fas fa-bookmark';
        btn.classList.add('bookmarked');
        btn.setAttribute('aria-label', 'Remove bookmark');
    } else {
        icon.className = 'far fa-bookmark';
        btn.classList.remove('bookmarked');
        btn.setAttribute('aria-label', 'Bookmark article');
    }
}

// 3. Log user reading history
function logReadingHistory(id, title) {
    let history = JSON.parse(localStorage.getItem('reading_history') || '[]');
    
    // Remove existing if present to shift to top
    history = history.filter(item => item.id !== id);
    
    // Limit history to 10 articles
    history.unshift({ id, title, time: Date.now() });
    if (history.length > 10) {
        history.pop();
    }
    
    localStorage.setItem('reading_history', JSON.stringify(history));
}

// 4. Web Share API helper
function initShareButton(btn, title) {
    btn.addEventListener('click', () => {
        if (navigator.share) {
            navigator.share({
                title: title,
                text: `Read this news on Palghar LIVE: ${title}`,
                url: window.location.href
            })
            .then(() => {
                if (typeof showToast === 'function') showToast('Shared successfully!', 'success');
            })
            .catch((err) => {
                // Ignore abortions
                if (err.name !== 'AbortError') {
                    fallbackShare();
                }
            });
        } else {
            fallbackShare();
        }
    });
    
    function fallbackShare() {
        // Fallback to clipboard copy
        navigator.clipboard.writeText(window.location.href)
            .then(() => {
                if (typeof showToast === 'function') showToast('Link copied to clipboard! (Fallback Share)', 'success');
            })
            .catch(() => {
                if (typeof showToast === 'function') showToast('Failed to copy share link.', 'error');
            });
    }
}

// 5. Copy Link directly helper
function initCopyLink(btn) {
    btn.addEventListener('click', () => {
        navigator.clipboard.writeText(window.location.href)
            .then(() => {
                if (typeof showToast === 'function') showToast('Link copied to clipboard!', 'success');
            })
            .catch(() => {
                if (typeof showToast === 'function') showToast('Failed to copy link.', 'error');
            });
    });
}
