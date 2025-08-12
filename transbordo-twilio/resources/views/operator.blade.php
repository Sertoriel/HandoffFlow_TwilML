<!DOCTYPE html>
<html>

<head>
    <title>Painel do Operador</title>
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
        }

        function sendMessage() {
            const message = document.getElementById('messageInput').value;
            fetch('/operator/send', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
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
                    'Content-Type': 'application/json'
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
