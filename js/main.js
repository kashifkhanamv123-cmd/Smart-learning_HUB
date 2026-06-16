document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Sidebar Navigation Drawer Controls
    const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
    const sidebarContainer = document.getElementById('sidebarContainer');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    if (mobileSidebarToggle && sidebarContainer && sidebarOverlay) {
        mobileSidebarToggle.addEventListener('click', () => {
            sidebarContainer.classList.add('expanded');
        });
        
        sidebarOverlay.addEventListener('click', () => {
            sidebarContainer.classList.remove('expanded');
        });
        
        // Auto-close sidebar on window resize if screen goes larger
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                sidebarContainer.classList.remove('expanded');
            }
        });
    }
    
    // 2. Global Search Interface Filter (simulates search filter highlights)
    const globalSearchInput = document.getElementById('globalSearchInput');
    if (globalSearchInput) {
        globalSearchInput.addEventListener('keyup', (e) => {
            const query = e.target.value.toLowerCase().trim();
            
            // Search courses, cards, etc. depending on current active view
            const courseCards = document.querySelectorAll('.courses-grid .glass-card');
            const deckCards = document.querySelectorAll('.deck-grid .glass-card');
            const noteItems = document.querySelectorAll('.notes-list .note-item');
            
            if (courseCards.length > 0) {
                courseCards.forEach(card => {
                    const title = card.querySelector('h3').textContent.toLowerCase();
                    const desc = card.querySelector('p').textContent.toLowerCase();
                    if (title.includes(query) || desc.includes(query)) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }
            
            if (deckCards.length > 0) {
                deckCards.forEach(card => {
                    const title = card.querySelector('h3').textContent.toLowerCase();
                    const desc = card.querySelector('p').textContent.toLowerCase();
                    if (title.includes(query) || desc.includes(query)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }
            
            if (noteItems.length > 0) {
                noteItems.forEach(item => {
                    const title = item.querySelector('h4').textContent.toLowerCase();
                    if (title.includes(query)) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            }
        });
    }
    
    // 3. Auto dismiss flash-alert notifications after 5 seconds
    const flashAlert = document.querySelector('.flash-alert');
    if (flashAlert) {
        setTimeout(() => {
            flashAlert.style.opacity = '0';
            flashAlert.style.transition = 'opacity 0.5s ease';
            setTimeout(() => {
                flashAlert.remove();
            }, 500);
        }, 5000);
    }
});
