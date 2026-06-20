document.addEventListener('DOMContentLoaded', () => {
    // 1. Core Timer State
    let timerDuration = 25 * 60; // 25 minutes by default
    let timeRemaining = timerDuration;
    let timerInterval = null;
    let isRunning = false;
    let currentMode = 'focus'; // 'focus', 'short', 'long'
    
    // SVG variables
    const circumference = 753.98; // 2 * pi * 120
    const progressRing = document.getElementById('timerProgressRing');
    
    // UI Elements
    const timeDisplay = document.getElementById('timerTimeDisplay');
    const modeLabel = document.getElementById('timerActiveModeLabel');
    const startBtn = document.getElementById('timerStartBtn');
    const resetBtn = document.getElementById('timerResetBtn');
    
    const modeFocusBtn = document.getElementById('modeFocus');
    const modeShortBtn = document.getElementById('modeShort');
    const modeLongBtn = document.getElementById('modeLong');
    
    // Ambient Sound Elements
    const soundBtns = document.querySelectorAll('.sound-btn');
    const alarmSound = document.getElementById('soundAlarm');
    
    // 2. Display render function
    function updateTimerDisplay() {
        const mins = Math.floor(timeRemaining / 60);
        const secs = timeRemaining % 60;
        
        const minsStr = mins < 10 ? '0' + mins : mins;
        const secsStr = secs < 10 ? '0' + secs : secs;
        timeDisplay.textContent = `${minsStr}:${secsStr}`;
        
        // Calculate SVG circular stroke dash offset
        const offset = circumference * (1 - timeRemaining / timerDuration);
        progressRing.style.strokeDashoffset = offset;
    }
    
    // 3. Save study session time back to SQL via background fetch (AJAX)
    function saveStudySession(seconds) {
        const formData = new FormData();
        formData.append('action', 'log_session');
        formData.append('seconds', seconds);
        
        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfTokenMeta) {
            formData.append('csrf_token', csrfTokenMeta.getAttribute('content'));
        }
        
        fetch('timer.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            console.log('Session saved successfully:', data);
        })
        .catch(err => {
            console.error('Error saving study session:', err);
        });
    }
    
    // 4. Tick function
    function tick() {
        if (timeRemaining <= 0) {
            clearInterval(timerInterval);
            timerInterval = null;
            isRunning = false;
            startBtn.innerHTML = '<i class="fa-solid fa-play"></i> Start';
            
            // Ring alarm
            alarmSound.play();
            
            // Log study session to SQL database if it was a focus block
            if (currentMode === 'focus') {
                saveStudySession(timerDuration);
                alert('Focus session completed! Logged 25 minutes of study time. Take a break!');
                setMode('short');
            } else {
                alert('Break is over! Time to get back to work.');
                setMode('focus');
            }
            return;
        }
        
        timeRemaining--;
        updateTimerDisplay();
    }
    
    // 5. Timer state actions
    function startTimer() {
        if (isRunning) {
            // Pause
            clearInterval(timerInterval);
            timerInterval = null;
            isRunning = false;
            startBtn.innerHTML = '<i class="fa-solid fa-play"></i> Resume';
        } else {
            // Start
            isRunning = true;
            startBtn.innerHTML = '<i class="fa-solid fa-pause"></i> Pause';
            timerInterval = setInterval(tick, 1000);
        }
    }
    
    function resetTimer() {
        clearInterval(timerInterval);
        timerInterval = null;
        isRunning = false;
        startBtn.innerHTML = '<i class="fa-solid fa-play"></i> Start';
        timeRemaining = timerDuration;
        updateTimerDisplay();
    }
    
    function setMode(mode) {
        currentMode = mode;
        
        // Remove active class from buttons
        [modeFocusBtn, modeShortBtn, modeLongBtn].forEach(btn => btn.classList.remove('active'));
        
        if (mode === 'focus') {
            timerDuration = 25 * 60;
            modeFocusBtn.classList.add('active');
            modeLabel.textContent = 'Focusing';
            progressRing.style.stroke = '#6366f1';
        } else if (mode === 'short') {
            timerDuration = 5 * 60;
            modeShortBtn.classList.add('active');
            modeLabel.textContent = 'Short Break';
            progressRing.style.stroke = '#10b981';
        } else if (mode === 'long') {
            timerDuration = 15 * 60;
            modeLongBtn.classList.add('active');
            modeLabel.textContent = 'Long Break';
            progressRing.style.stroke = '#3b82f6';
        }
        
        resetTimer();
    }
    
    // 6. Action button binds
    startBtn.addEventListener('click', startTimer);
    resetBtn.addEventListener('click', resetTimer);
    
    modeFocusBtn.addEventListener('click', () => setMode('focus'));
    modeShortBtn.addEventListener('click', () => setMode('short'));
    modeLongBtn.addEventListener('click', () => setMode('long'));
    
    // 7. Ambient sound managers
    soundBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const soundId = btn.getAttribute('data-sound');
            const audioEl = document.getElementById('sound' + soundId.charAt(0).toUpperCase() + soundId.slice(1));
            
            if (btn.classList.contains('active')) {
                // Pause sound
                btn.classList.remove('active');
                audioEl.pause();
            } else {
                // Stop all ambient audios first
                soundBtns.forEach(b => {
                    b.classList.remove('active');
                    const sId = b.getAttribute('data-sound');
                    const sEl = document.getElementById('sound' + sId.charAt(0).toUpperCase() + sId.slice(1));
                    sEl.pause();
                });
                
                // Play selected sound
                btn.classList.add('active');
                audioEl.play();
            }
        });
    });
    
    // Render display initially
    updateTimerDisplay();
});
