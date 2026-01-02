
let carouselDom = document.querySelector('.carousel');
let SliderDom = carouselDom.querySelector('.carousel .list');
let thumbnailBorderDom = document.querySelector('.carousel .thumbnail');
let thumbnailItemsDom = thumbnailBorderDom.querySelectorAll('.item');

// Remove initial thumbnail reordering (delete this line)
// thumbnailBorderDom.appendChild(thumbnailItemsDom[0]); 

document.querySelectorAll('.slide-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        const targetIndex = parseInt(btn.dataset.index);
        const currentIndex = getCurrentSlideIndex();
        
        if(targetIndex === currentIndex) return;
        
        const totalSlides = 4;
        let direction;
        let steps;
        
        // Calculate shortest path
        const forwardSteps = (targetIndex - currentIndex + totalSlides) % totalSlides;
        const backwardSteps = (currentIndex - targetIndex + totalSlides) % totalSlides;
        
        if(forwardSteps <= backwardSteps) {
            direction = 'next';
            steps = forwardSteps;
        } else {
            direction = 'prev';
            steps = backwardSteps;
        }
        
        // Animate each step with proper delays
        for(let i = 0; i < steps; i++) {
            await new Promise(resolve => {
                showSlider(direction);
                setTimeout(resolve, 300); // Wait for animation
            });
        }

        // Update active class after animation completes
        document.querySelectorAll('.slide-btn').forEach(button => {
            button.classList.remove('active');
        });
        btn.classList.add('active');        
    });
});

function getCurrentSlideIndex() {
    const activeSlideImg = SliderDom.querySelector('.item:first-child img').src;
    return Array.from(thumbnailItemsDom).findIndex(
        thumb => thumb.querySelector('img').src === activeSlideImg
    );
}

function showSlider(type) {
    let SliderItemsDom = SliderDom.querySelectorAll('.item');
    let thumbnailItemsDom = document.querySelectorAll('.thumbnail .item');
    
    if(type === 'next') {
        SliderDom.appendChild(SliderItemsDom[0]);
        thumbnailBorderDom.appendChild(thumbnailItemsDom[0]);
        carouselDom.classList.add('next');
    } else {
        SliderDom.prepend(SliderItemsDom[SliderItemsDom.length - 1]);
        thumbnailBorderDom.prepend(thumbnailItemsDom[thumbnailItemsDom.length - 1]);
        carouselDom.classList.add('prev');
    }

    setTimeout(() => {
        carouselDom.classList.remove('next', 'prev');
    }, 300);
}