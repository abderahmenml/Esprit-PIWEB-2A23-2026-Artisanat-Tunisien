(function () {
    function $(id) {
        return document.getElementById(id);
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function addMessage(container, role, text) {
        var item = document.createElement('div');
        item.className = 'chatbot-msg ' + (role === 'user' ? 'user' : 'bot');
        item.innerHTML = escapeHtml(text || '');
        container.appendChild(item);
        container.scrollTop = container.scrollHeight;
    }

    function setSuggestions(container, suggestions, onClick) {
        container.innerHTML = '';
        (Array.isArray(suggestions) ? suggestions : []).slice(0, 5).forEach(function (question) {
            if (!question) {
                return;
            }

            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'chatbot-chip';
            btn.textContent = String(question);
            btn.addEventListener('click', function () {
                onClick(String(question));
            });
            container.appendChild(btn);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var config = window.ChatbotConfig || {};
        var endpoint = String(config.endpoint || '');
        var profilId = Number.parseInt(String(config.profilId || '0'), 10);
        var provider = String(config.provider || 'ollama');

        if (!endpoint || !profilId) {
            return;
        }

        var toggle = $('chatbot-toggle');
        var panel = $('chatbot-panel');
        var closeBtn = $('chatbot-close');
        var form = $('chatbot-form');
        var input = $('chatbot-input');
        var messagesBox = $('chatbot-messages');
        var suggestionsBox = $('chatbot-suggestions');
        var providerLabel = $('chatbot-provider-label');

        if (!toggle || !panel || !closeBtn || !form || !input || !messagesBox || !suggestionsBox || !providerLabel) {
            return;
        }

        var history = [];
        var warmupStarted = false;

        function warmupModel() {
            if (warmupStarted) {
                return;
            }

            warmupStarted = true;
            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    message: 'Bonjour',
                    history: [],
                    profil_id: profilId,
                    provider: provider,
                    warmup: true
                })
            }).catch(function () {
                warmupStarted = false;
            });
        }

        function openPanel() {
            panel.classList.add('is-open');
            panel.setAttribute('aria-hidden', 'false');
            input.focus();
            warmupModel();
        }

        function closePanel() {
            panel.classList.remove('is-open');
            panel.setAttribute('aria-hidden', 'true');
        }

        toggle.addEventListener('click', function () {
            if (panel.classList.contains('is-open')) {
                closePanel();
            } else {
                openPanel();
            }
        });

        closeBtn.addEventListener('click', function () {
            closePanel();
        });

        async function sendMessage(text) {
            var message = String(text || '').trim();
            if (!message) {
                return;
            }

            addMessage(messagesBox, 'user', message);
            history.push({ role: 'user', content: message });

            input.value = '';
            input.disabled = true;

            try {
                var response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        message: message,
                        history: history,
                        profil_id: profilId,
                        provider: provider
                    })
                });

                var data = await response.json();

                if (!response.ok) {
                    throw new Error((data && data.error) || 'Erreur assistant');
                }

                var reply = (data && data.reply) ? String(data.reply) : 'Aucune reponse recue.';
                addMessage(messagesBox, 'bot', reply);
                history.push({ role: 'assistant', content: reply });

                var usedProvider = (data && data.provider) ? String(data.provider) : provider;
                if (usedProvider === 'ollama') {
                    providerLabel.textContent = 'Ollama';
                } else if (usedProvider === 'xai') {
                    providerLabel.textContent = 'xAI';
                } else {
                    providerLabel.textContent = 'Local';
                }

                if (data && Array.isArray(data.suggestions)) {
                    setSuggestions(suggestionsBox, data.suggestions, sendMessage);
                }
            } catch (error) {
                addMessage(messagesBox, 'bot', error.message || 'Erreur reseau, reessayez.');
            } finally {
                input.disabled = false;
                input.focus();
            }
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            sendMessage(input.value);
        });

        setSuggestions(suggestionsBox, [
            'Quel service recommandez-vous pour commencer ?',
            'Pouvez-vous proposer une solution en 3 etapes ?',
            'Quel est le delai moyen ?',
            'Quel budget approximatif faut-il prevoir ?'
        ], sendMessage);

        warmupModel();
    });
})();
