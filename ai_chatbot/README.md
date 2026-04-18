# Classroom AI Chatbot System

This system integrates a LangGraph-powered AI assistant into your PHP Classroom website.

## Setup Instructions

1.  **Install Python Dependencies:**
    ```bash
    pip install -r requirements.txt
    ```

2.  **Database Configuration:**
    - Open `database.py` and ensure the `get_connection()` function has the correct credentials for your local MySQL server (default is `root` with no password).
    - Ensure your database name is `mu_classroom`.

3.  **Run the AI Server:**
    ```bash
    python app.py
    ```
    The server will start at `http://127.0.0.1:8000`.

## Web Integration

Add the following HTML and CSS to your PHP website (e.g., in `footer.php` or `index.php`) to show the chat widget.

### CSS (Add to your stylesheet)
```css
#ai-chat-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 300px;
    background: white;
    border: 1px solid #ccc;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    font-family: Arial, sans-serif;
    display: flex;
    flex-direction: column;
}
#ai-chat-header {
    background: #007bff;
    color: white;
    padding: 10px;
    border-radius: 10px 10px 0 0;
}
#chat-messages {
    height: 300px;
    overflow-y: auto;
    padding: 10px;
    border-bottom: 1px solid #eee;
}
#chat-input-area {
    display: flex;
    padding: 10px;
}
#chat-input {
    flex: 1;
    padding: 5px;
    border: 1px solid #ddd;
    border-radius: 4px;
}
.chat-btn {
    background: #007bff;
    color: white;
    border: none;
    padding: 5px 10px;
    margin-left: 5px;
    border-radius: 4px;
    cursor: pointer;
}
```

### HTML & JS
```html
<div id="ai-chat-container">
    <div id="ai-chat-header">Classroom Assistant</div>
    <div id="chat-messages"></div>
    <div id="chat-input-area">
        <input id="chat-input" placeholder="Ask about exams...">
        <button class="chat-btn" onclick="sendMessage()">Send</button>
    </div>
</div>

<script>
function sendMessage() {
    let inputEl = document.getElementById("chat-input");
    let message = inputEl.value.trim();
    if (!message) return;

    let box = document.getElementById("chat-messages");
    box.innerHTML += `<p><b>You:</b> ${message}</p>`;
    inputEl.value = "";
    box.scrollTop = box.scrollHeight;

    fetch("http://127.0.0.1:8000/chat", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ message: message })
    })
    .then(res => res.json())
    .then(data => {
        box.innerHTML += `<p><b>AI:</b> ${data.reply}</p>`;
        box.scrollTop = box.scrollHeight;
    })
    .catch(err => {
        box.innerHTML += `<p style="color:red"><b>Error:</b> Could not connect to AI server.</p>`;
    });
}
</script>
```
