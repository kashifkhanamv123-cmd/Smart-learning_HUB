document.addEventListener('DOMContentLoaded', () => {
    const mdInput = document.getElementById('markdownInput');
    const mdPreview = document.getElementById('markdownPreview');
    
    if (!mdInput || !mdPreview) return; // Editor workspace is not active
    
    // 1. Core Markdown Compiler Function
    function compileMarkdown(text) {
        // Escape HTML tags to prevent XSS during live preview
        let html = text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
            
        // Compile code blocks (```code```)
        html = html.replace(/```([a-zA-Z0-9]+)?\r?\n([\s\S]*?)\r?\n```/g, (match, lang, code) => {
            return `<pre><code class="language-${lang || 'txt'}">${code}</code></pre>`;
        });
        
        // Compile headers (# Header)
        html = html.replace(/^# (.*?)$/gm, '<h1>$1</h1>');
        html = html.replace(/^## (.*?)$/gm, '<h2>$1</h2>');
        html = html.replace(/^### (.*?)$/gm, '<h3>$1</h3>');
        
        // Compile bold (**text**)
        html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        
        // Compile italic (*text*)
        html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');
        
        // Compile lists (- list)
        html = html.replace(/^- (.*?)$/gm, '<li>$1</li>');
        html = html.replace(/(<li>.*?<\/li>)+/gs, '<ul>$0</ul>');
        
        // Compile inline code (`code`)
        html = html.replace(/`(.*?)`/g, '<code>$1</code>');
        
        // Compile line breaks (br)
        html = html.replace(/\n/g, '<br>');
        
        // Clean up double breaks inside tags
        html = html.replace(/<\/h1><br>/g, '</h1>');
        html = html.replace(/<\/h2><br>/g, '</h2>');
        html = html.replace(/<\/h3><br>/g, '<h3>');
        html = html.replace(/<\/pre><br>/g, '</pre>');
        html = html.replace(/<\/ul><br>/g, '</ul>');
        html = html.replace(/<li>(.*?)<\/li><br>/g, '<li>$1</li>');
        
        return html;
    }
    
    // 2. Trigger preview updates on input changes
    function updatePreview() {
        const text = mdInput.value;
        mdPreview.innerHTML = compileMarkdown(text);
    }
    
    // Listen for inputs
    mdInput.addEventListener('input', updatePreview);
    
    // Trigger compilation once initially
    updatePreview();
});
