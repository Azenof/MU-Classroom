    <?php if (isset($_SESSION['user_id'])): ?>
    <!-- AI Chatbot Widget -->
    <div id="ai-chat-container">
        <div id="ai-chat-header">
            <span>Classroom Assistant</span>
            <button id="toggle-chat" style="background:none; border:none; color:white; cursor:pointer;">▢</button>
        </div>
        <div id="chat-body" style="display: none;">
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

    document.getElementById('ai-chat-header').addEventListener('click', function() {
        var body = document.getElementById('chat-body');
        var toggleBtn = document.getElementById('toggle-chat');
        if (body.style.display === 'none') {
            body.style.display = 'block';
            toggleBtn.textContent = '_';
        } else {
            body.style.display = 'none';
            toggleBtn.textContent = '▢';
        }
    });
    </script>
    <?php endif; ?>

    <script>
    // Sidebar Toggle Logic
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebar-toggle');
    const mainContent = document.querySelector('.main-content');
    const toggleIcon = toggle ? toggle.querySelector('span') : null;

    if (sidebar && toggle) {
        // Load initial state
        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            sidebar.classList.add('collapsed');
            if (mainContent) mainContent.classList.add('expanded');
            if (toggleIcon) toggleIcon.textContent = '›';
        }

        toggle.addEventListener('click', () => {
            const isCollapsed = sidebar.classList.toggle('collapsed');
            if (mainContent) mainContent.classList.toggle('expanded');
            if (toggleIcon) toggleIcon.textContent = isCollapsed ? '›' : '‹';
            localStorage.setItem('sidebar-collapsed', isCollapsed);
        });
    }

    // Animated Counter Logic
    document.addEventListener("DOMContentLoaded", () => {
        const counters = document.querySelectorAll('.stat-count');
        const duration = 1500; // Animation duration in ms

        counters.forEach(counter => {
            const target = parseFloat(counter.getAttribute('data-target'));
            const decimals = parseInt(counter.getAttribute('data-decimals')) || 0;
            let startTime = null;

            const step = (timestamp) => {
                if (!startTime) startTime = timestamp;
                const progress = Math.min((timestamp - startTime) / duration, 1);
                const currentCount = progress * target;
                
                counter.innerText = currentCount.toFixed(decimals);
                
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                } else {
                    counter.innerText = target.toFixed(decimals);
                }
            };
            
            window.requestAnimationFrame(step);
        });
    });
    </script>
        </div> <!-- container -->
    </main> <!-- main-content -->
</div> <!-- app-shell -->
</body>
</html>
