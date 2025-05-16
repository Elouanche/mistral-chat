class ImageGallery {
    constructor(images) {
        this.images = images;
        this.currentIndex = 0;
        
        // Main gallery elements
        this.mainImage = document.querySelector('.main-image');
        this.prevButton = document.getElementById('bouton-l');
        this.nextButton = document.getElementById('bouton-r');
        this.thumbnailsContainer = document.querySelector('.thumbnails-container');
        
        // Fullscreen elements
        this.fullscreenContainer = this.createFullscreenContainer();
        document.body.appendChild(this.fullscreenContainer);
        
        this.init();
    }

    createFullscreenContainer() {
        const container = document.createElement('div');
        container.className = 'fullscreen-container';
        container.style.cssText = `
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        `;

        const fullscreenImage = document.createElement('img');
        fullscreenImage.className = 'fullscreen-image';
        fullscreenImage.style.cssText = `
            max-width: 90%;
            max-height: 90vh;
            object-fit: contain;
        `;

        const closeButton = document.createElement('button');
        closeButton.innerHTML = '×';
        closeButton.className = 'fullscreen-close';
        closeButton.style.cssText = `
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 30px;
            color: white;
            background: none;
            border: none;
            cursor: pointer;
        `;

        const fullscreenPrev = document.createElement('button');
        fullscreenPrev.innerHTML = '‹';
        fullscreenPrev.className = 'fullscreen-nav fullscreen-prev';
        fullscreenPrev.style.cssText = `
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 40px;
            color: white;
            background: none;
            border: none;
            cursor: pointer;
        `;

        const fullscreenNext = document.createElement('button');
        fullscreenNext.innerHTML = '›';
        fullscreenNext.className = 'fullscreen-nav fullscreen-next';
        fullscreenNext.style.cssText = `
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 40px;
            color: white;
            background: none;
            border: none;
            cursor: pointer;
        `;

        container.appendChild(fullscreenImage);
        container.appendChild(closeButton);
        container.appendChild(fullscreenPrev);
        container.appendChild(fullscreenNext);

        return container;
    }

    init() {
        this.updateMainImage();
        this.createThumbnails();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Main gallery navigation
        this.prevButton.addEventListener('click', () => this.prevImage());
        this.nextButton.addEventListener('click', () => this.nextImage());
        
        // Fullscreen mode
        this.mainImage.addEventListener('click', () => this.openFullscreen());
        
        // Fullscreen navigation
        const closeButton = this.fullscreenContainer.querySelector('.fullscreen-close');
        const fullscreenPrev = this.fullscreenContainer.querySelector('.fullscreen-prev');
        const fullscreenNext = this.fullscreenContainer.querySelector('.fullscreen-next');
        
        closeButton.addEventListener('click', () => this.closeFullscreen());
        fullscreenPrev.addEventListener('click', () => this.prevImage());
        fullscreenNext.addEventListener('click', () => this.nextImage());
        
        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (this.fullscreenContainer.style.display === 'flex') {
                if (e.key === 'Escape') this.closeFullscreen();
                if (e.key === 'ArrowLeft') this.prevImage();
                if (e.key === 'ArrowRight') this.nextImage();
            }
        });
    }

    updateMainImage() {
        this.mainImage.src = this.images[this.currentIndex];
        const fullscreenImage = this.fullscreenContainer.querySelector('.fullscreen-image');
        fullscreenImage.src = this.images[this.currentIndex];
        this.updateThumbnailsState();
    }

    createThumbnails() {
        this.thumbnailsContainer.innerHTML = '';
        this.images.forEach((img, index) => {
            const thumbnail = document.createElement('img');
            thumbnail.src = img;
            thumbnail.alt = `Vignette ${index + 1}`;
            thumbnail.className = `thumbnail ${index === this.currentIndex ? 'active' : ''}`;

            thumbnail.addEventListener('click', () => {
                this.currentIndex = index;
                this.updateMainImage();
            });

            this.thumbnailsContainer.appendChild(thumbnail);
        });
    }

    updateThumbnailsState() {
        const thumbnails = this.thumbnailsContainer.querySelectorAll('.thumbnail');
        thumbnails.forEach((thumb, index) => {
            thumb.classList.toggle('active', index === this.currentIndex);
        });
    }

    prevImage() {
        this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length;
        this.updateMainImage();
    }

    nextImage() {
        this.currentIndex = (this.currentIndex + 1) % this.images.length;
        this.updateMainImage();
    }

    openFullscreen() {
        this.fullscreenContainer.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    closeFullscreen() {
        this.fullscreenContainer.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}