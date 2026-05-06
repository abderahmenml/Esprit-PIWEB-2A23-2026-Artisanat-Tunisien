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

            var attempt = 0;
            var maxAttempts = 2;

            while (attempt < maxAttempts) {
                attempt++;
                try {
                    var controller = new AbortController();
                    var timeoutId = setTimeout(function () { controller.abort(); }, 45000);

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
                        }),
                        signal: controller.signal
                    });
                    clearTimeout(timeoutId);

                    var contentType = response.headers.get('Content-Type') || '';
                    var data = null;
                    
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
                        var errMsg = (data && data.message) || (data && data.error) || 'Erreur assistant';
                        throw new Error(String(errMsg));
                    }

                    // Extraire la réponse (format attendu: { success, data: { reply } })
                    var reply = '';
                    if (data && data.data && data.data.reply) {
                        reply = String(data.data.reply);
                    } else if (data && data.reply) {
                        reply = String(data.reply);
                    } else {
                        reply = 'Je suis là pour vous aider. Quelle est votre question ?';
                    }
                    
                    addMessage(messagesBox, 'bot', reply);
                    history.push({ role: 'assistant', content: reply });

                    var usedProvider = (data && data.data && data.data.provider) ? String(data.data.provider) : provider;
                    if (usedProvider === 'ollama') {
                        providerLabel.textContent = 'IA intégrée';
                    } else if (usedProvider === 'xai') {
                        providerLabel.textContent = 'xAI';
                    } else {
                        providerLabel.textContent = 'Assistant';
                    }

                    var suggestions = (data && data.data && data.data.suggestions) ? data.data.suggestions : [];
                    if (suggestions.length > 0) {
                        setSuggestions(suggestionsBox, suggestions, sendMessage);
                    }

                    // Sortie de la boucle - succès
                    break;

                } catch (error) {
                    var isAbort = error && error.name === 'AbortError';
                    var errMsg = (error && error.message) ? String(error.message) : 'Erreur réseau';
                    console.warn('Chatbot attempt ' + attempt + ' failed:', errMsg);

                    if (attempt < maxAttempts) {
                        await new Promise(function (res) { setTimeout(res, 800); });
                        continue;
                    }

                    if (isAbort) {
                        errMsg = 'Le serveur met trop de temps à répondre. Veuillez réessayer.';
                    } else {
                        errMsg = 'Erreur de connexion. Veuillez réessayer.';
                    }

                    addMessage(messagesBox, 'bot', errMsg);
                    console.error('Chatbot error:', error);
                    break;
                } finally {
                    input.disabled = false;
                    input.focus();
                }
            }
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

        warmupModel();
    });
})()