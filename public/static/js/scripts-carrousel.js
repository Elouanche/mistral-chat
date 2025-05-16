class Carousel {
    constructor(element) {
        this.carousel = element;
        this.track = element.querySelector('.carousel-track');
        this.slides = Array.from(element.querySelectorAll('.carousel-slide'));
        this.currentIndex = 0;
        this.isAnimating = false;

        // Buttons
        element.querySelector('.carousel-button-left').addEventListener('click', () => this.prev());
        element.querySelector('.carousel-button-right').addEventListener('click', () => this.next());

        // Touch events
        this.touchStartX = 0;
        this.track.addEventListener('touchstart', e => this.handleTouchStart(e));
        this.track.addEventListener('touchmove', e => this.handleTouchMove(e));
        
        // Initial setup
        this.updateSlidePosition();
    }

    handleTouchStart(e) {
        this.touchStartX = e.touches[0].clientX;
    }

    handleTouchMove(e) {
        if (this.isAnimating) return;
        
        const touchEndX = e.touches[0].clientX;
        const diff = this.touchStartX - touchEndX;

        if (Math.abs(diff) > 50) { // Minimum swipe distance
            if (diff > 0) this.next();
            else this.prev();
            
            this.touchStartX = 0;
        }
    }

    prev() {
        if (this.isAnimating) return;
        this.currentIndex = (this.currentIndex > 0) ? this.currentIndex - 1 : this.slides.length - 1;
        this.updateSlidePosition();
    }

    next() {
        if (this.isAnimating) return;
        this.currentIndex = (this.currentIndex < this.slides.length - 1) ? this.currentIndex + 1 : 0;
        this.updateSlidePosition();
    }

    updateSlidePosition() {
        this.isAnimating = true;
        this.track.style.transform = `translateX(-${this.currentIndex * 100}%)`;
        setTimeout(() => {
            this.isAnimating = false;
        }, 300);
    }
}

// Initialize all carousels on the page
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.carousel').forEach(carousel => new Carousel(carousel));
});