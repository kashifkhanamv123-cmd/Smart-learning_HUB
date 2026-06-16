// Option Selection Handler
function selectOption(labelElement, slideIdx) {
    // Select all options in the current question slide
    const slide = document.getElementById('qSlide-' + slideIdx);
    const options = slide.querySelectorAll('.option-item');
    
    // Clear selection classes
    options.forEach(opt => opt.classList.remove('selected'));
    
    // Add selected class to chosen element
    labelElement.classList.add('selected');
}

document.addEventListener('DOMContentLoaded', () => {
    const quizForm = document.getElementById('quizSubmitForm');
    if (!quizForm) return; // Quiz test view is not active
    
    let currentSlide = 0;
    const totalQuestions = window.quizTotalQuestions;
    
    const prevBtn = document.getElementById('quizPrevBtn');
    const nextBtn = document.getElementById('quizNextBtn');
    const submitBtn = document.getElementById('quizSubmitBtn');
    
    // 1. Question Slide Progression Controls
    function showSlide(index) {
        // Range gate check
        if (index < 0 || index >= totalQuestions) return;
        
        // Hide all slides
        const slides = document.querySelectorAll('.question-slide');
        slides.forEach(slide => slide.style.display = 'none');
        
        // Show current active slide
        document.getElementById('qSlide-' + index).style.display = 'block';
        currentSlide = index;
        
        // Toggle action buttons based on slide position
        if (currentSlide === 0) {
            prevBtn.style.display = 'none';
        } else {
            prevBtn.style.display = 'block';
        }
        
        if (currentSlide === totalQuestions - 1) {
            nextBtn.style.display = 'none';
            submitBtn.style.display = 'block';
        } else {
            nextBtn.style.display = 'block';
            submitBtn.style.display = 'none';
        }
    }
    
    nextBtn.addEventListener('click', () => {
        showSlide(currentSlide + 1);
    });
    
    prevBtn.addEventListener('click', () => {
        showSlide(currentSlide - 1);
    });
    
    // 2. Countdown Timer
    let timeRemaining = window.quizDurationSecs;
    const timerClock = document.getElementById('timerClock');
    
    function updateClock() {
        if (timeRemaining <= 0) {
            clearInterval(timerInterval);
            timerClock.textContent = "00:00";
            
            // Auto submit quiz on time expiration
            alert('Time is up! Your quiz will be submitted automatically.');
            quizForm.submit();
            return;
        }
        
        const mins = Math.floor(timeRemaining / 60);
        const secs = timeRemaining % 60;
        
        const minsStr = mins < 10 ? '0' + mins : mins;
        const secsStr = secs < 10 ? '0' + secs : secs;
        
        timerClock.textContent = `${minsStr}:${secsStr}`;
        timeRemaining--;
    }
    
    // Initial clock update and start interval loops
    updateClock();
    const timerInterval = setInterval(updateClock, 1000);
});
