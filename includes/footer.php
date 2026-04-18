    <!-- AI Chatbot Widget -->
    <div id="ai-chat-container">
        <div id="ai-chat-header">
            <span>Classroom Assistant</span>
            <button id="toggle-chat" style="background:none; border:none; color:white; cursor:pointer;">_</button>
        </div>
        <div id="chat-body">
            <div id="chat-messages">
                <p style="color: #666; font-style: italic;">Ask me about your exams, notes, or materials!</p>
            </div>
            <div id="chat-input-area">
                <input id="chat-input" placeholder="Type a message..." onkeypress="handleKeyPress(event)">
                <button class="chat-btn" onclick="sendMessage()">Send</button>
            </div>
        </div>
    </div>

    <script>
    function handleKeyPress(e) {
        if (e.key === 'Enter') sendMessage();
    }

    function sendMessage() {
        let inputEl = document.getElementById("chat-input");
        let message = inputEl.value.trim();
        if (!message) return;

        let box = document.getElementById("chat-messages");
        box.innerHTML += `<p><strong>You:</strong> ${message}</p>`;
        inputEl.value = "";
        box.scrollTop = box.scrollHeight;

        fetch("http://127.0.0.1:8000/chat", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ message: message })
        })
        .then(res => res.json())
        .then(data => {
            box.innerHTML += `<p><strong>AI:</strong> ${data.reply.replace(/\n/g, '<br>')}</p>`;
            box.scrollTop = box.scrollHeight;
        })
        .catch(err => {
            box.innerHTML += `<p style="color:red"><strong>Error:</strong> AI server is offline.</p>`;
        });
    }

    document.getElementById('toggle-chat').addEventListener('click', function() {
        var body = document.getElementById('chat-body');
        if (body.style.display === 'none') {
            body.style.display = 'block';
            this.textContent = '_';
        } else {
            body.style.display = 'none';
            this.textContent = '▢';
        }
    });
    </script>
</div> <!-- container -->
</body>
</html>
