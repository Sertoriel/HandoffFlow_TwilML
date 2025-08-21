<!DOCTYPE html>
<html>

<head>
    <title>Painel do Operador</title>

    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.0/dist/echo.iife.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-reverb@1.0.0/dist/reverb.iife.min.js"></script>

    <script>
        window.Echo = new Echo({
            broadcaster: Reverb,
            key: '{{ env('REVERB_APP_KEY') }}',
            wsHost: '{{ env('VITE_REVERB_HOST', '127.0.0.1') }}',
            wsPort: {{ env('VITE_REVERB_PORT', 8080) }},
            wssPort: {{ env('VITE_REVERB_PORT', 443) }},
            forceTLS: false,
            enabledTransports: ['ws', 'wss'],
        });

        // Ouvir por novas sessões de chat para atualizar a lista
        window.Echo.channel('chat.sessions')
            .listen('new.session', (e) => {
                console.log('New session event received:', e.session);
                const activeSessionsList = document.getElementById('activeSessionsList');
                const newSessionItem = document.createElement('li');
                newSessionItem.id = `session-${e.session.id}`;
                newSessionItem.innerHTML = `
                    ${e.session.customer_id} ${e.session.id}
                    <button onclick="openChat('${e.session.id}', '${e.session.customer_id}')">
                        Atender
                    </button>
                `;
                activeSessionsList.appendChild(newSessionItem);
            });
    </script>
</head>

<body>
    <h1>Sessões Ativas</h1>
    <ul id="activeSessionsList">
        @foreach ($activeSessions as $session)
            <li id="session-{{ $session->id }}">
                {{ $session->customer_id }} {{ $session->id }}
                <button onclick="openChat('{{ $session->id }}', '{{ $session->customer_id }}')">
                    Atender
                </button>
            </li>
        @endforeach
    </ul>

    <div id="chatWindow" style="display:none">
        <h2>Chat com <span id="customerId"></span></h2>
        <div id="messageHistory"></div>
        <input type="text" id="messageInput">
        <button onclick="sendMessage()">Enviar</button>
        <button onclick="closeChat()">Finalizar Atendimento</button>
    </div>

    <script>
        let currentSession = null;

        function openChat(sessionId, customerId) {
            currentSession = sessionId;
            document.getElementById('customerId').textContent = customerId;
            document.getElementById('chatWindow').style.display = 'block';

            fetch(`/operator/session/${sessionId}/messages`)
                .then(response => response.json())
                .then(messages => {
                    const history = document.getElementById('messageHistory');
                    history.innerHTML = '';
                    messages.forEach(msg => {
                        history.innerHTML += `<div>[${msg.direction}] ${msg.body}</div>`;
                    });
                    history.scrollTop = history.scrollHeight; // Scroll para o final
                })
                .catch(error => console.error('Error loading messages:', error));

            // Ouvir canal Reverb para mensagens específicas desta sessão
            window.Echo.channel(`chat.${sessionId}`)
                .listen('NewMessageEvent', (e) => {
                    const history = document.getElementById('messageHistory');
                    history.innerHTML += `<div>[${e.direction}] ${e.message}</div>`;
                    history.scrollTop = history.scrollHeight; // Scroll para o final
                });
        }

        function sendMessage() {
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value;
            if (!message.trim()) return;

            fetch('/operator/send', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        session_id: currentSession,
                        customer: document.getElementById('customerId').textContent,
                        message: message
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Message sent:', data);
                    messageInput.value = '';
                    // Adicionar a mensagem enviada ao histórico localmente sem esperar o broadcast
                    const history = document.getElementById('messageHistory');
                    history.innerHTML += `<div>[outbound] ${message}</div>`;
                    history.scrollTop = history.scrollHeight; // Scroll para o final
                })
                .catch(error => console.error('Error sending message:', error));
        }

        function closeChat() {
            fetch('/operator/close', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        session_id: currentSession
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Session close initiated:', data);
                    document.getElementById('chatWindow').style.display = 'none';
                    const sessionElement = document.getElementById(`session-${currentSession}`);
                    if (sessionElement) {
                        sessionElement.remove();
                    }
                    currentSession = null;
                })
                .catch(error => console.error('Error closing chat:', error));
        }
    </script>
</body>

</html>
