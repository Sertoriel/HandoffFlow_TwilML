<!DOCTYPE html>
<html>
<head>
    <title>Painel do Operador</title>
    
    <!-- Remova o import de módulo e use esta abordagem -->
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.0/dist/echo.iife.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-reverb@1.0.0/dist/reverb.iife.min.js"></script>
    
    <script>
        // Configuração do Echo usando valores do PHP
        window.Echo = new Echo({
            broadcaster: Reverb,
            key: '{{ env("REVERB_APP_KEY") }}',
            wsHost: '{{ env("VITE_REVERB_HOST", "127.0.0.1") }}',
            wsPort: {{ env("VITE_REVERB_PORT", 8080) }},
            wssPort: {{ env("VITE_REVERB_PORT", 443) }},
            forceTLS: false, // Mantenha false para desenvolvimento local
            enabledTransports: ['ws', 'wss'],
        });
    </script>
</head>

<body>
    <h1>Sessões Ativas</h1>
    <ul>
        @foreach ($activeSessions as $session)
            <li>
                {{ $session->customer_id }}
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
            
            // Carregar histórico de mensagens
            fetch(`/operator/session/${sessionId}/messages`)
                .then(response => response.json())
                .then(messages => {
                    const history = document.getElementById('messageHistory');
                    history.innerHTML = '';
                    messages.forEach(msg => {
                        history.innerHTML += `<div>[${msg.direction}] ${msg.body}</div>`;
                    });
                });
            
            // Ouvir canal Reverb
            window.Echo.channel(`chat.${sessionId}`)
                .listen('NewMessageEvent', (e) => {
                    const history = document.getElementById('messageHistory');
                    history.innerHTML += `<div>[${e.direction}] ${e.message}</div>`;
                });
        }

        function sendMessage() {
            const message = document.getElementById('messageInput').value;
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
            });
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
            });
            document.getElementById('chatWindow').style.display = 'none';
        }
    </script>
</body>
</html>