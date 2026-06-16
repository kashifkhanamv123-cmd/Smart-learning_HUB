document.addEventListener('DOMContentLoaded', () => {
    // 1. Validate Course Adding Inputs
    const addCourseForm = document.querySelector('#addCourseModal form');
    if (addCourseForm) {
        addCourseForm.addEventListener('submit', (e) => {
            const titleInput = document.getElementById('course_title');
            if (titleInput && titleInput.value.trim() === '') {
                e.preventDefault();
                alert('Course title is required and cannot be empty.');
            }
        });
    }
    
    // 2. Validate Lesson Adding Inputs
    const addLessonForm = document.querySelector('#manageLessonsModal form');
    if (addLessonForm) {
        addLessonForm.addEventListener('submit', (e) => {
            const titleInput = document.getElementById('lesson_title');
            const contentInput = document.getElementById('lesson_content');
            
            if (titleInput && titleInput.value.trim() === '') {
                e.preventDefault();
                alert('Lesson title is required.');
                return;
            }
            
            if (contentInput && contentInput.value.trim() === '') {
                e.preventDefault();
                alert('Lesson markdown content is required.');
            }
        });
    }
    
    // 3. Validate Quiz Adding Inputs
    const addQuizForm = document.querySelector('#addQuizModal form');
    if (addQuizForm) {
        addQuizForm.addEventListener('submit', (e) => {
            const titleInput = document.getElementById('quiz_title');
            const durationInput = document.getElementById('quiz_dur');
            
            if (titleInput && titleInput.value.trim() === '') {
                e.preventDefault();
                alert('Quiz title is required.');
                return;
            }
            
            if (durationInput && parseInt(durationInput.value) <= 0) {
                e.preventDefault();
                alert('Quiz duration must be at least 1 minute.');
            }
        });
    }
    
    // 4. Validate Question Adding Inputs
    const addQuestionForm = document.querySelector('#manageQuestionsModal form');
    if (addQuestionForm) {
        addQuestionForm.addEventListener('submit', (e) => {
            const textInput = document.getElementById('question_text');
            const optA = document.getElementById('opt_a');
            const optB = document.getElementById('opt_b');
            const optC = document.getElementById('opt_c');
            const optD = document.getElementById('opt_d');
            
            if (textInput && textInput.value.trim() === '') {
                e.preventDefault();
                alert('Question text is required.');
                return;
            }
            
            if (!optA || !optB || !optC || !optD || 
                optA.value.trim() === '' || optB.value.trim() === '' || 
                optC.value.trim() === '' || optD.value.trim() === '') {
                e.preventDefault();
                alert('All four choices (A, B, C, D) are required for multiple-choice questions.');
            }
        });
    }
});
