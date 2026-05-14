<?php
// includes/footer.php
// Closes out the body layout and attaches necessary scripts
?>
    <!-- Floating Chatbot UI -->
    <div id="chatbot-container" style="position: fixed; bottom: 50px; right: 40px; z-index: 1000; font-family: 'Inter', sans-serif;">
        <!-- Dynamic Alignment for Desktop -->
        <style>
            @media (min-width: 1300px) {
                #chatbot-container {
                    right: calc(50% - 580px) !important;
                }
            }
        </style>
        <!-- Chat Toggle Button -->
        <button id="chatbot-toggle" style="width: 60px; height: 60px; border-radius: 30px; background: linear-gradient(135deg, #8b5cf6, #ec4899); border: none; color: #fff; font-size: 1.5rem; cursor: pointer; box-shadow: 0 10px 25px rgba(139, 92, 246, 0.4); display: flex; align-items: center; justify-content: center; transition: 0.3s ease;">
            <i class="fas fa-comment-dots"></i>
        </button>

        <!-- Chat Popup Window -->
        <div id="chatbot-popup" style="display: none; position: absolute; bottom: 80px; right: 0; width: 350px; height: 500px; background: #fff; border-radius: 25px; box-shadow: 0 20px 50px rgba(0,0,0,0.15); overflow: hidden; border: 1px solid #f1f5f9; flex-direction: column;">
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #8b5cf6, #ec4899); padding: 25px; color: #fff;">
                <h4 style="margin: 0; font-size: 1.1rem; font-weight: 800;">Academic Assistant</h4>
                <p style="margin: 5px 0 0; font-size: 0.75rem; opacity: 0.8; font-weight: 600;">Ask me anything about the system!</p>
            </div>

            <!-- Messages Area -->
            <div id="chatbot-messages" style="flex: 1; padding: 20px; overflow-y: auto; background: #f8fafc; display: flex; flex-direction: column; gap: 15px;">
                <div style="background: #fff; padding: 12px 16px; border-radius: 15px 15px 15px 0; max-width: 85%; font-size: 0.85rem; color: #475569; font-weight: 600; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
                    Hello! I'm your AMS help bot. You can ask about results, timetables, or just type where you want to go!
                </div>
            </div>

            <!-- Suggestion Chips -->
            <div id="chatbot-suggestions" style="padding: 10px 15px; background: #fff; display: flex; gap: 8px; overflow-x: auto; white-space: nowrap; border-top: 1px solid #f1f5f9;">
                <button onclick="sendQuickMsg('How do I view my results?')" style="padding: 6px 12px; background: #f1f5f9; border: none; border-radius: 20px; font-size: 0.7rem; font-weight: 700; color: #64748b; cursor: pointer;">Results?</button>
                <button onclick="sendQuickMsg('How do I view timetable?')" style="padding: 6px 12px; background: #f1f5f9; border: none; border-radius: 20px; font-size: 0.7rem; font-weight: 700; color: #64748b; cursor: pointer;">Timetable?</button>
                <button onclick="sendQuickMsg('How do I submit assignment?')" style="padding: 6px 12px; background: #f1f5f9; border: none; border-radius: 20px; font-size: 0.7rem; font-weight: 700; color: #64748b; cursor: pointer;">Assignments?</button>
            </div>

            <!-- Input Area -->
            <div style="padding: 15px; background: #fff; border-top: 1px solid #f1f5f9; display: flex; gap: 10px;">
                <input type="text" id="chatbot-input" placeholder="Type your question..." style="flex: 1; border: none; background: #f1f5f9; padding: 12px; border-radius: 12px; font-size: 0.85rem; font-weight: 600; outline: none;">
                <button id="chatbot-send" style="width: 40px; height: 40px; border-radius: 10px; background: #8b5cf6; border: none; color: #fff; cursor: pointer;">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        const toggleBtn = document.getElementById('chatbot-toggle');
        const chatPopup = document.getElementById('chatbot-popup');
        const chatInput = document.getElementById('chatbot-input');
        const chatSend = document.getElementById('chatbot-send');
        const chatMsgs = document.getElementById('chatbot-messages');

        toggleBtn.onclick = () => {
            chatPopup.style.display = chatPopup.style.display === 'none' ? 'flex' : 'none';
        };

        function addMessage(text, isBot = true, navLink = null) {
            const msgDiv = document.createElement('div');
            msgDiv.style.padding = '12px 16px';
            msgDiv.style.borderRadius = isBot ? '15px 15px 15px 0' : '15px 15px 0 15px';
            msgDiv.style.maxWidth = '85%';
            msgDiv.style.fontSize = '0.85rem';
            msgDiv.style.fontWeight = '600';
            msgDiv.style.boxShadow = '0 2px 10px rgba(0,0,0,0.03)';
            
            if (isBot) {
                msgDiv.style.background = '#fff';
                msgDiv.style.color = '#475569';
                msgDiv.style.alignSelf = 'flex-start';
            } else {
                msgDiv.style.background = '#8b5cf6';
                msgDiv.style.color = '#fff';
                msgDiv.style.alignSelf = 'flex-end';
            }

            msgDiv.textContent = text;
            
            if (navLink) {
                const linkBtn = document.createElement('a');
                linkBtn.href = navLink.url;
                linkBtn.textContent = navLink.text;
                linkBtn.style.display = 'block';
                linkBtn.style.marginTop = '10px';
                linkBtn.style.padding = '8px';
                linkBtn.style.background = '#f5f3ff';
                linkBtn.style.color = '#7c3aed';
                linkBtn.style.textAlign = 'center';
                linkBtn.style.borderRadius = '8px';
                linkBtn.style.textDecoration = 'none';
                linkBtn.style.fontSize = '0.75rem';
                msgDiv.appendChild(linkBtn);
            }

            chatMsgs.appendChild(msgDiv);
            chatMsgs.scrollTop = chatMsgs.scrollHeight;
        }

        async function sendMessage(text) {
            if (!text) return;
            addMessage(text, false);
            chatInput.value = '';

            try {
                const response = await fetch('chatbot_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: text })
                });
                const data = await response.json();
                addMessage(data.response, true, data.navLink);
            } catch (err) {
                addMessage("Oops! I'm having trouble connecting to the server. Please try again later.");
            }
        }

        function sendQuickMsg(text) {
            sendMessage(text);
        }

        chatSend.onclick = () => sendMessage(chatInput.value);
        chatInput.onkeypress = (e) => { if (e.key === 'Enter') sendMessage(chatInput.value); };
    </script>

    <script src="../assets/script.js?v=<?php echo time(); ?>"></script>
</body>
</html>
