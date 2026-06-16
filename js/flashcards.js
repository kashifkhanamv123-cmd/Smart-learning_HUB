document.addEventListener('DOMContentLoaded', () => {
    const cardsDataEl = document.getElementById('cardsData');
    if (!cardsDataEl) return; // Study view is not active
    
    // 1. Initialize data
    const cards = JSON.parse(cardsDataEl.textContent);
    let currentIndex = 0;
    let flippedOnce = false;
    let easyCount = 0;
    
    // UI elements
    const cardView = document.getElementById('flashCardView');
    const frontText = document.getElementById('cardFrontText');
    const backText = document.getElementById('cardBackText');
    const controls = document.getElementById('cardControls');
    
    const progressText = document.getElementById('studyProgressText');
    const progressBar = document.getElementById('studyProgressBar');
    const scoreText = document.getElementById('sessionScoreText');
    const finishCard = document.getElementById('studyFinishCard');
    
    // Button bindings
    const hardBtn = document.getElementById('difficultyHardBtn');
    const mediumBtn = document.getElementById('difficultyMediumBtn');
    const easyBtn = document.getElementById('difficultyEasyBtn');
    
    // 2. Load active card
    function loadCard(index) {
        if (index >= cards.length) {
            // Study session finished
            cardView.style.display = 'none';
            controls.style.display = 'none';
            progressBar.style.width = '100%';
            progressText.textContent = `Completed ${cards.length} of ${cards.length}`;
            finishCard.style.display = 'block';
            return;
        }
        
        // Reset card flip state
        cardView.classList.remove('flipped');
        flippedOnce = false;
        controls.style.opacity = '0.4';
        controls.style.pointerEvents = 'none';
        
        // Wait for flip back animation before changing text
        setTimeout(() => {
            frontText.textContent = cards[index].front;
            backText.textContent = cards[index].back;
        }, 150);
        
        // Update statistics indicators
        progressText.textContent = `Card ${index + 1} of ${cards.length}`;
        const progressPct = (index / cards.length) * 100;
        progressBar.style.width = `${progressPct}%`;
        
        const masteryPct = index > 0 ? Math.round((easyCount / index) * 100) : 0;
        scoreText.textContent = `Mastery: ${masteryPct}%`;
    }
    
    // 3. Card flip toggle listener
    cardView.addEventListener('click', () => {
        cardView.classList.toggle('flipped');
        if (!flippedOnce) {
            flippedOnce = true;
            controls.style.opacity = '1';
            controls.style.pointerEvents = 'auto';
        }
    });
    
    // 4. Rating buttons listeners
    function rateCard(rating) {
        if (rating === 'easy') {
            easyCount++;
        }
        
        currentIndex++;
        loadCard(currentIndex);
    }
    
    hardBtn.addEventListener('click', () => rateCard('hard'));
    mediumBtn.addEventListener('click', () => rateCard('medium'));
    easyBtn.addEventListener('click', () => rateCard('easy'));
    
    // Load first card initially
    loadCard(currentIndex);
});
