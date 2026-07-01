// assets/js/index_page.js
// Handles Homepage specific features: Hero Carousel and AJAX Infinite Scroll

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialise Hero Carousel
    const carouselContainer = document.querySelector('.carousel-container');
    if (carouselContainer) {
        new HeroCarousel(carouselContainer);
    }

    // 2. Initialise Infinite Scroll
    const newsGrid = document.getElementById('news-articles-grid');
    const loadMoreTrigger = document.getElementById('infinite-scroll-trigger');
    
    if (newsGrid && loadMoreTrigger) {
        initInfiniteScroll(newsGrid, loadMoreTrigger);
    }

    // 3. Initialise Video Playlist Player
    const playlistContainer = document.getElementById('video-playlist-container');
    const mainIframe = document.getElementById('main-yt-iframe');
    if (playlistContainer && mainIframe) {
        initVideoPlaylist(playlistContainer, mainIframe);
    }
});

// Carousel Class
class HeroCarousel {
    constructor(container) {
        this.container = container;
        this.track = container.querySelector('.carousel-track');
        this.slides = Array.from(this.track.children);
        this.dots = Array.from(container.querySelectorAll('.carousel-dot'));
        this.currentIndex = 0;
        this.autoplayTimer = null;
        
        this.init();
    }
    
    init() {
        this.dots.forEach((dot, idx) => {
            dot.addEventListener('click', () => this.gotoSlide(idx));
        });
        
        // Touch events for mobile swiping
        this.track.addEventListener('touchstart', (e) => this.dragStart(e), { passive: true });
        this.track.addEventListener('touchmove', (e) => this.dragMove(e), { passive: true });
        this.track.addEventListener('touchend', () => this.dragEnd());
        
        this.startAutoplay();
    }
    
    gotoSlide(index) {
        this.currentIndex = index;
        const width = this.slides[0].offsetWidth;
        this.track.style.transform = `translateX(-${this.currentIndex * width}px)`;
        
        this.dots.forEach((dot, idx) => {
            dot.classList.toggle('active', idx === this.currentIndex);
        });
    }
    
    navigate(direction) {
        const nextIndex = (this.currentIndex + direction + this.slides.length) % this.slides.length;
        this.gotoSlide(nextIndex);
    }
    
    dragStart(e) {
        this.isDragging = true;
        this.stopAutoplay();
        this.startX = e.touches[0].clientX;
        this.track.style.transition = 'none';
    }
    
    dragMove(e) {
        if (!this.isDragging) return;
        const currentX = e.touches[0].clientX;
        const diff = currentX - this.startX;
        const width = this.slides[0].offsetWidth;
        const translate = -(this.currentIndex * width) + diff;
        this.track.style.transform = `translateX(${translate}px)`;
    }
    
    dragEnd() {
        if (!this.isDragging) return;
        this.isDragging = false;
        this.track.style.transition = '';
        this.startAutoplay();
    }
    
    startAutoplay() {
        this.autoplayTimer = setInterval(() => this.navigate(1), 5000);
    }
    
    stopAutoplay() {
        if (this.autoplayTimer) clearInterval(this.autoplayTimer);
    }
}

// Infinite Scroll function
function initInfiniteScroll(grid, trigger) {
    let page = 1;
    let loading = false;
    let finished = false;
    
    const activeCat = trigger.getAttribute('data-category') || '';
    const activeQuery = trigger.getAttribute('data-search') || '';

    // Create observer
    const observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting && !loading && !finished) {
            loadNextPage();
        }
    }, {
        rootMargin: '100px'
    });

    observer.observe(trigger);

    function loadNextPage() {
        loading = true;
        page++;
        
        // Show skeleton loading placeholders
        const skeletons = showSkeletons();
        
        const url = (window.APP_URL || '') + `/index.php?page=${page}&cat=${encodeURIComponent(activeCat)}&q=${encodeURIComponent(activeQuery)}&ajax=1`;
        
        fetch(url)
            .then(res => {
                if (!res.ok) throw new Error("Fetch failed");
                return res.text();
            })
            .then(html => {
                // Clear skeleton loaders
                skeletons.forEach(el => el.remove());
                
                if (html.trim() === '') {
                    finished = true;
                    trigger.innerHTML = '<div class="scroll-finished">You have reached the end of the news reel.</div>';
                    observer.unobserve(trigger);
                    return;
                }
                
                // Append cards
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                
                const cards = Array.from(tempDiv.children);
                cards.forEach(card => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    grid.appendChild(card);
                    
                    // Fade-in animation
                    setTimeout(() => {
                        card.style.transition = 'opacity 0.5s, transform 0.5s';
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 50);
                });
                
                loading = false;
            })
            .catch(() => {
                skeletons.forEach(el => el.remove());
                loading = false;
                trigger.innerHTML = '<button class="btn-retry" id="btn-scroll-retry">Failed to load. Click to retry</button>';
                
                const retryBtn = document.getElementById('btn-scroll-retry');
                if (retryBtn) {
                    retryBtn.addEventListener('click', () => {
                        trigger.innerHTML = '<div class="scroll-spinner"></div>';
                        loadNextPage();
                    });
                }
            });
    }

    function showSkeletons() {
        const list = [];
        for (let i = 0; i < 3; i++) {
            const card = document.createElement('div');
            card.className = 'skeleton-card';
            card.innerHTML = `
                <div class="skeleton-element skeleton-image"></div>
                <div class="skeleton-element skeleton-title"></div>
                <div class="skeleton-element skeleton-text"></div>
                <div class="skeleton-element skeleton-text-short"></div>
            `;
            grid.appendChild(card);
            list.push(card);
        }
        return list;
    }
}

// Video Playlist interaction logic
function initVideoPlaylist(playlist, iframe) {
    const items = playlist.querySelectorAll('.playlist-item');
    items.forEach(item => {
        const handler = () => {
            const ytId = item.getAttribute('data-youtube-id');
            if (ytId) {
                iframe.src = `https://www.youtube.com/embed/${ytId}?autoplay=1&rel=0`;
                
                // Toggle active state
                items.forEach(el => el.classList.remove('active'));
                item.classList.add('active');
            }
        };
        
        item.addEventListener('click', handler);
        item.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                handler();
            }
        });
    });
}
