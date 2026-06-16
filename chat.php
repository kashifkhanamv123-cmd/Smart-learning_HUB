<?php
$pageTitle = 'AI Study Assistant';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/header.php';

$userId = $_SESSION['user_id'];
?>

<div class="page-header">
    <h1>AI Study Companion</h1>
    <p>Ask questions, generate study schedules, or summarize learning logs.</p>
</div>

<div class="chat-layout">
    <!-- Prompt templates sidebar -->
    <div class="chat-presets">
        <h4 style="color:#fff; font-weight:600; padding:10px 0; border-bottom:1px solid var(--border-color); margin-bottom:15px;">Suggested Prompts</h4>
        <button class="preset-btn" data-prompt="Explain JavaScript Closures simply with code.">
            <strong>💡 Explain Closures</strong><br>
            <span style="font-size:0.75rem; color:var(--text-dark);">Learn closures in JS</span>
        </button>
        <button class="preset-btn" data-prompt="Summarize my study sheets and recommend a study schedule.">
            <strong>📅 Make Study Schedule</strong><br>
            <span style="font-size:0.75rem; color:var(--text-dark);">Build custom calendars</span>
        </button>
        <button class="preset-btn" data-prompt="How do I load a CSV file using Pandas in Python?">
            <strong>🐼 Pandas CSV Load</strong><br>
            <span style="font-size:0.75rem; color:var(--text-dark);">Python data manipulation</span>
        </button>
        <button class="preset-btn" data-prompt="Explain the difference between Section and Article HTML5 tags.">
            <strong>🎨 HTML5 Semantics</strong><br>
            <span style="font-size:0.75rem; color:var(--text-dark);">Web structural layouts</span>
        </button>
    </div>
    
    <!-- Chat log window -->
    <div class="chat-workspace">
        <div class="chat-history" id="chatHistory">
            <!-- Bot greeting message -->
            <div class="chat-bubble bot">
                Hello <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>! I am your AI Study Companion. 
                I can explain complex engineering subjects, review your notes, or generate quizzes. 
                <br><br>
                Try selecting one of the suggested prompts on the left or write a custom query below!
            </div>
        </div>
        
        <!-- Typing indicator (hidden by default) -->
        <div style="padding: 10px 24px; display: none;" id="chatTypingRow">
            <div class="chat-bubble bot" style="width: fit-content; padding: 10px 20px;">
                <div class="typing-indicator">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            </div>
        </div>
        
        <!-- Input row -->
        <div class="chat-input-area">
            <input type="text" id="chatInputField" placeholder="Ask a question or input topic details..." autocomplete="off">
            <button id="chatSendBtn"><i class="fa-solid fa-paper-plane"></i></button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const chatHistory = document.getElementById('chatHistory');
    const inputField = document.getElementById('chatInputField');
    const sendBtn = document.getElementById('chatSendBtn');
    const typingRow = document.getElementById('chatTypingRow');
    const presetBtns = document.querySelectorAll('.preset-btn');
    
    // Core prompt responses mapping
    const replies = {
        "closures": `<h3>JavaScript Closures</h3>
A <strong>closure</strong> is a function that remembers its outer lexical scope variables even after the outer function has completed execution.
<pre><code class="language-javascript">function createCounter() {
    let count = 0; // Lexical variable
    return function() {
        count++; // Reads outer scope
        return count;
    };
}

const counter = createCounter();
console.log(counter()); // Output: 1
console.log(counter()); // Output: 2</code></pre>
<h4>Key Takeaways:</h4>
<ul>
  <li>Helps maintain private variables inside helper classes.</li>
  <li>Encapsulates states within callbacks.</li>
</ul>`,
        
        "schedule": `<h3>Personalized Study Schedule</h3>
Based on your study metrics, I recommend this balanced <strong>Pomodoro Study Strategy</strong>:
<br><br>
<strong>1. Active Learning Slot (50 Mins)</strong>
<ul>
  <li>Spend 25 minutes reading new concepts in <em>Modern Web Development</em>.</li>
  <li>Take a 5-minute break.</li>
  <li>Spend 20 minutes practicing with <strong>JavaScript Advanced Decks</strong>.</li>
</ul>
<strong>2. Validation Slot (30 Mins)</strong>
<ul>
  <li>Take a 15-minute quiz in the <strong>Quiz Hub</strong>.</li>
  <li>Spend 15 minutes reviewing incorrect options and updating notes.</li>
</ul>
<strong>3. Rest Cycle (15 Mins)</strong>
<ul>
  <li>Complete break. Stand up and rest.</li>
</ul>`,
        
        "pandas": `<h3>Loading CSV Datasets in Pandas</h3>
Pandas makes reading flat files simple. Use the <code>read_csv()</code> function to load files.
<pre><code class="language-python">import pandas as pd

# Load dataset
df = pd.read_csv('student_grades.csv')

# View first 5 columns
print(df.head())

# Filter data
passing_students = df[df['score'] >= 50]
print(passing_students)</code></pre>
<h4>Important tips:</h4>
<ul>
  <li>Use <code>sep=';'</code> if your columns are separated by semicolons.</li>
  <li>Set <code>encoding='utf-8'</code> to parse international character symbols safely.</li>
</ul>`,
        
        "semantics": `<h3>Section vs Article Semantic Tags</h3>
Choosing correct semantic markup tags is crucial for clean layouts:
<br><br>
<strong>1. &lt;article&gt;</strong>
Represents a self-contained, independent composition. If you cut it out and put it on a new site, it should make complete sense.
<ul>
  <li><em>Examples:</em> Blog entries, product cards, news reviews, comments.</li>
</ul>
<strong>2. &lt;section&gt;</strong>
Represents a thematic grouping of content, typically with a heading. It is part of a larger page structure.
<ul>
  <li><em>Examples:</em> Tab panels, Contact Us column, Terms of Service paragraphs.</li>
</ul>`,
        
        "default": `I am your study assistant! I specialize in core web technology design, Python programming frameworks, and customized study strategy plans.
<br><br>
Try asking me about <strong>Closures</strong>, <strong>Pandas</strong>, <strong>HTML5 Semantics</strong>, or request a <strong>Study Plan</strong>.`
    };
    
    function appendMessage(sender, text) {
        const bubble = document.createElement('div');
        bubble.className = `chat-bubble ${sender}`;
        bubble.innerHTML = text;
        chatHistory.appendChild(bubble);
        chatHistory.scrollTop = chatHistory.scrollHeight;
    }
    
    function triggerAIResponse(userInput) {
        const query = userInput.toLowerCase();
        
        // Show typing animation
        typingRow.style.display = 'block';
        chatHistory.scrollTop = chatHistory.scrollHeight;
        
        // Match reply key
        let replyText = replies.default;
        if (query.includes('closure')) {
            replyText = replies.closures;
        } else if (query.includes('schedule') || query.includes('calendar') || query.includes('plan')) {
            replyText = replies.schedule;
        } else if (query.includes('pandas') || query.includes('csv')) {
            replyText = replies.pandas;
        } else if (query.includes('section') || query.includes('article') || query.includes('semantic')) {
            replyText = replies.semantics;
        }
        
        // Simulate typing delay
        setTimeout(() => {
            typingRow.style.display = 'none';
            appendMessage('bot', replyText);
        }, 1200);
    }
    
    function handleSend() {
        const text = inputField.value.trim();
        if (text === '') return;
        
        appendMessage('user', text);
        inputField.value = '';
        triggerAIResponse(text);
    }
    
    // Action trigger clicks
    sendBtn.addEventListener('click', handleSend);
    inputField.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') handleSend();
    });
    
    // Preset buttons clicks
    presetBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const prompt = btn.getAttribute('data-prompt');
            appendMessage('user', prompt);
            triggerAIResponse(prompt);
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
