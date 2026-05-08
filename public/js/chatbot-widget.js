// chatbot-widget.js - Version optimisée
(function () {
    const CONFIG = {
        maxRetries: 3,
        timeout: 120000,  // 120 secondes max (2 minutes)
        maxHistoryLength: 6
    };

    function $(id) { return document.getElementById(id); }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function addMessage(container, role, text, isLoading = false) {
        var item = document.createElement('div');
        item.className = 'chatbot-msg ' + (role === 'user' ? 'user' : 'bot');
        if (isLoading) {
            item.className += ' chatbot-loading';
            item.innerHTML = '<span class="loading-dots">⏳</span>';
        } else {
            item.innerHTML = escapeHtml(text || '').replace(/\n/g, '<br>');
        }
        container.appendChild(item);
        container.scrollTop = container.scrollHeight;
        return item;
    }

    function setSuggestions(container, suggestions, onClick) {
        container.innerHTML = '';
        (Array.isArray(suggestions) ? suggestions : []).slice(0, 5).forEach(function (question) {
            if (!question) return;
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
            console.warn('Chatbot: configuration manquante');
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
            console.warn('Chatbot: éléments HTML manquants');
            return;
        }

        var history = [];
        var isProcessing = false;
        var abortController = null;

        function abortCurrentRequest() {
            if (abortController) {
                abortController.abort();
                abortController = null;
            }
        }

        function addUserMessage(text) {
            addMessage(messagesBox, 'user', text);
            history.push({ role: 'user', content: text });
            if (history.length > CONFIG.maxHistoryLength) {
                history = history.slice(-CONFIG.maxHistoryLength);
            }
        }

        function openPanel() {
            panel.classList.add('is-open');
            panel.setAttribute('aria-hidden', 'false');
            input.focus();
        }

        function closePanel() {
            panel.classList.remove('is-open');
            panel.setAttribute('aria-hidden', 'true');
            abortCurrentRequest();
        }

        toggle.addEventListener('click', function () {
            if (panel.classList.contains('is-open')) {
                closePanel();
            } else {
                openPanel();
            }
        });

        closeBtn.addEventListener('click', closePanel);

        async function sendMessage(text) {
            var message = String(text || '').trim();
            if (!message || isProcessing) return;

            addUserMessage(message);
            input.value = '';
            input.disabled = true;
            isProcessing = true;
            abortCurrentRequest();

            var loadingMsg = addMessage(messagesBox, 'bot', '', true);
            var attempt = 0;
            var maxAttempts = CONFIG.maxRetries;

            while (attempt < maxAttempts) {
                attempt++;
                try {
                    abortController = new AbortController();
                    var timeoutId = setTimeout(function () { 
                        abortController.abort(); 
                    }, CONFIG.timeout);

                    var response = await fetch(endpoint, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({
                            message: message,
                            history: history.slice(-CONFIG.maxHistoryLength),
                            profil_id: profilId,
                            provider: provider
                        }),
                        signal: abortController.signal
                    });
                    clearTimeout(timeoutId);

                    var data = null;
                    var contentType = response.headers.get('Content-Type') || '';
                    
                    if (contentType.indexOf('application/json') !== -1) {
                        try {
                            data = await response.json();
                        } catch (e) {
                            throw new Error('Réponse JSON invalide');
                        }
                    } else {
                        data = { reply: await response.text() };
                    }

                    if (!response.ok) {
                        throw new Error((data && data.message) || (data && data.error) || 'Erreur serveur');
                    }

                    var reply = '';
                    if (data && data.data && data.data.reply) {
                        reply = String(data.data.reply);
                    } else if (data && data.reply) {
                        reply = String(data.reply);
                    } else {
                        reply = "Je suis là pour vous aider. Quelle est votre question ?";
                    }

                    // Remplacer le message de chargement par la réponse
                    loadingMsg.className = 'chatbot-msg bot';
                    loadingMsg.innerHTML = escapeHtml(reply).replace(/\n/g, '<br>');
                    
                    history.push({ role: 'assistant', content: reply });

                    var usedProvider = (data && data.data && data.data.provider) ? String(data.data.provider) : provider;
                    providerLabel.textContent = usedProvider === 'ollama' ? 'IA intégrée' : (usedProvider === 'xai' ? 'xAI' : 'Assistant');

                    var suggestions = (data && data.data && data.data.suggestions) ? data.data.suggestions : [];
                    if (suggestions.length > 0) {
                        setSuggestions(suggestionsBox, suggestions, sendMessage);
                    }

                    break; // Succès

                } catch (error) {
                    var isAbort = error && error.name === 'AbortError';
                    var errMsg = isAbort ? 'Le serveur met trop de temps à répondre.' : (error.message || 'Erreur réseau');
                    
                    if (attempt < maxAttempts) {
                        await new Promise(function (res) { setTimeout(res, 500); });
                        continue;
                    }

                    loadingMsg.className = 'chatbot-msg bot';
                    loadingMsg.innerHTML = '⚠️ ' + escapeHtml(errMsg) + ' Veuillez réessayer.';
                    console.error('Chatbot error:', error);
                    break;
                } finally {
                    abortController = null;
                }
            }

            input.disabled = false;
            input.focus();
            isProcessing = false;
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            sendMessage(input.value);
        });

        // Suggestions initiales
        setSuggestions(suggestionsBox, [
            'Quels sont vos services ?',
            'Comment puis-je vous contacter ?',
            'Quels sont vos tarifs ?',
            'Proposez-vous des devis gratuits ?'
        ], sendMessage);
    });
})();