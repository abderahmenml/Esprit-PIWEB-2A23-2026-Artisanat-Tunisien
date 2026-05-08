(function () {
    function getAppUrl(path) {
        if (typeof window.appUrl === 'function') {
            return window.appUrl(path);
        }
        return path;
    }

    function notify(message, type) {
        if (typeof window.showAppNotification === 'function') {
            window.showAppNotification(message, type || 'success');
            return;
        }
        if (type === 'error') {
            window.alert(message);
        }
    }

    function byId(id) {
        return document.getElementById(id);
    }

    function clampLevel(value) {
        var n = Number.parseInt(String(value || '0'), 10);
        if (Number.isNaN(n)) {
            return 50;
        }
        return Math.max(0, Math.min(100, n));
    }

    function safeArray(value) {
        return Array.isArray(value) ? value : [];
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function parseSeed() {
        var seedScript = byId('cv-ai-seed');
        if (!seedScript) {
            return null;
        }
        try {
            return JSON.parse(seedScript.textContent || seedScript.innerText);
        } catch (e) {
            return null;
        }
    }

    function buildSkillRow(item, idx) {
        return '<div class="cv-ia-row" data-skill-row="' + idx + '">' +
            '<input type="text" data-skill-name value="' + escapeHtml(item.name || '') + '" placeholder="Competence" />' +
            '<input type="number" data-skill-level min="0" max="100" value="' + clampLevel(item.level) + '" placeholder="Niveau" />' +
            '<button type="button" class="cv-ia-remove" data-remove-skill="' + idx + '">✕</button>' +
            '</div>';
    }

    function buildExpRow(item, idx, type) {
        return '<div class="cv-ia-block" data-' + type + '-row="' + idx + '">' +
            '<div class="cv-ia-grid-two">' +
            '<input type="text" data-role value="' + escapeHtml(item.role || item.degree || '') + '" placeholder="Poste / Diplome" />' +
            '<input type="text" data-company value="' + escapeHtml(item.company || item.school || '') + '" placeholder="Entreprise / Ecole" />' +
            '<input type="text" data-start value="' + escapeHtml(item.start || '') + '" placeholder="Debut (YYYY-MM)" />' +
            '<input type="text" data-end value="' + escapeHtml(item.end || '') + '" placeholder="Fin (YYYY-MM ou Present)" />' +
            '</div>' +
            '<textarea rows="3" data-description placeholder="Description">' + escapeHtml(item.description || '') + '</textarea>' +
            '<button type="button" class="cv-ia-remove" data-remove-' + type + '="' + idx + '">✕ Supprimer</button>' +
            '</div>';
    }

    function renderPreview(state) {
        var preview = byId('cv-preview');
        if (!preview) {
            return;
        }

        preview.classList.remove('cv-template-moderne', 'cv-template-classique', 'cv-template-epure');
        preview.classList.add('cv-template-' + (state.template || 'moderne'));
        preview.style.setProperty('--cv-accent', state.primaryColor || '#2E6B3E');

        var skillsHtml = safeArray(state.skills).map(function (s) {
            return '<span class="cv-pill">' + escapeHtml(s.name || '') + ' <span class="cv-level">' + clampLevel(s.level) + '%</span></span>';
        }).join('');

        var expHtml = safeArray(state.experiences).map(function (e) {
            return '<div class="cv-item"><div class="cv-item-title">' + escapeHtml(e.role || '') + '</div>' +
                '<div class="cv-item-sub">' + escapeHtml(e.company || '') + ' • ' + escapeHtml((e.start || '') + ' - ' + (e.end || 'Présent')) + '</div>' +
                '<div class="cv-item-text">' + escapeHtml(e.description || '').replace(/\n/g, '<br />') + '</div></div>';
        }).join('');

        var eduHtml = safeArray(state.education).map(function (e) {
            return '<div class="cv-item"><div class="cv-item-title">' + escapeHtml(e.degree || '') + '</div>' +
                '<div class="cv-item-sub">' + escapeHtml(e.school || '') + ' • ' + escapeHtml((e.start || '') + ' - ' + (e.end || '')) + '</div>' +
                '<div class="cv-item-text">' + escapeHtml(e.description || '') + '</div></div>';
        }).join('');

        var qualitiesHtml = safeArray(state.qualities).map(function (q) {
            return '<span class="cv-quality-tag">' + escapeHtml(q) + '</span>';
        }).join('');

        var adviceList = safeArray(state.aiAdvice || state.recommendations || state.tips || []).slice(0, 4);
        var adviceHtml = adviceList.length ? '<ul class="cv-advice-list">' + adviceList.map(function (item) {
            return '<li>' + escapeHtml(item) + '</li>';
        }).join('') + '</ul>' : '';

        preview.innerHTML = '' +
            '<div class="cv-header">' +
            '<div class="cv-header-main">' +
            '<h2>' + escapeHtml(state.personal.fullName || '') + '</h2>' +
            '<p class="cv-title">' + escapeHtml(state.personal.title || '') + '</p>' +
            (state.slogan ? '<p class="cv-slogan">' + escapeHtml(state.slogan) + '</p>' : '') +
            '</div>' +
            '<div class="cv-contact">' +
            (state.personal.email ? '<div>' + escapeHtml(state.personal.email) + '</div>' : '') +
            (state.personal.phone ? '<div>' + escapeHtml(state.personal.phone) + '</div>' : '') +
            (state.personal.city ? '<div>' + escapeHtml(state.personal.city) + '</div>' : '') +
            '</div></div>' +
            (state.personal.summary ? '<div class="cv-section"><h3>Résumé Professionnel</h3><p class="cv-summary">' + escapeHtml(state.personal.summary).replace(/\n/g, '<br />') + '</p></div>' : '') +
            (skillsHtml ? '<div class="cv-section"><h3>Compétences</h3><div class="cv-pills">' + skillsHtml + '</div></div>' : '') +
            (expHtml ? '<div class="cv-section"><h3>Expériences</h3>' + expHtml + '</div>' : '') +
            (eduHtml ? '<div class="cv-section"><h3>Formations & Certifications</h3>' + eduHtml + '</div>' : '') +
            (adviceHtml ? '<div class="cv-section cv-advice-section"><h3>Conseils IA</h3>' + adviceHtml + '</div>' : '') +
            (qualitiesHtml ? '<div class="cv-section"><h3>Qualités Professionnelles</h3><div class="cv-qualities">' + qualitiesHtml + '</div></div>' : '');
    }

    function renderScoresAndTips(state) {
        var scoreRow = byId('cv-score-row');
        var tips = byId('cv-ia-tips');
        if (!scoreRow || !tips) {
            return;
        }

        var scores = state.scores || {};
        scoreRow.innerHTML = '' +
            '<div class="cv-score"><span class="cv-score-label">ATS</span> <strong>' + clampLevel(scores.ats) + '%</strong></div>' +
            '<div class="cv-score"><span class="cv-score-label">Impact</span> <strong>' + clampLevel(scores.impact) + '%</strong></div>' +
            '<div class="cv-score"><span class="cv-score-label">Lisibilité</span> <strong>' + clampLevel(scores.readability) + '%</strong></div>';

        var recsHtml = safeArray(state.recommendations || state.aiAdvice || state.tips || []).map(function (rec) {
            return '<div class="cv-tip">💡 ' + escapeHtml(rec) + '</div>';
        }).join('');

        tips.innerHTML = recsHtml || '<div class="cv-tip">Remplissez vos informations pour voir les recommandations</div>';
    }

    function collectInfos(state) {
        state.personal.fullName = (byId('cv-full-name') || {}).value || '';
        state.personal.title = (byId('cv-title') || {}).value || '';
        state.personal.email = (byId('cv-email') || {}).value || '';
        state.personal.phone = (byId('cv-phone') || {}).value || '';
        state.personal.city = (byId('cv-city') || {}).value || '';
        state.personal.summary = (byId('cv-summary') || {}).value || '';
        state.template = (byId('cv-template') || {}).value || 'moderne';
        state.language = (byId('cv-language') || {}).value || 'fr';
        state.model = (byId('cv-model') || {}).value || 'mistral';
        state.primaryColor = (byId('cv-primary-color') || {}).value || '#2E6B3E';

        state.skills = Array.from(document.querySelectorAll('[data-skill-row]')).map(function (row) {
            return {
                name: (row.querySelector('[data-skill-name]') || {}).value || '',
                level: clampLevel((row.querySelector('[data-skill-level]') || {}).value || '50')
            };
        }).filter(function (item) {
            return item.name.trim() !== '';
        });

        state.experiences = Array.from(document.querySelectorAll('[data-exp-row]')).map(function (row) {
            return {
                role: (row.querySelector('[data-role]') || {}).value || '',
                company: (row.querySelector('[data-company]') || {}).value || '',
                start: (row.querySelector('[data-start]') || {}).value || '',
                end: (row.querySelector('[data-end]') || {}).value || '',
                description: (row.querySelector('[data-description]') || {}).value || ''
            };
        }).filter(function (item) {
            return item.role.trim() !== '';
        });

        state.education = Array.from(document.querySelectorAll('[data-edu-row]')).map(function (row) {
            return {
                degree: (row.querySelector('[data-role]') || {}).value || '',
                school: (row.querySelector('[data-company]') || {}).value || '',
                start: (row.querySelector('[data-start]') || {}).value || '',
                end: (row.querySelector('[data-end]') || {}).value || '',
                description: (row.querySelector('[data-description]') || {}).value || ''
            };
        }).filter(function (item) {
            return item.degree.trim() !== '';
        });
    }

    function rerenderEditors(state) {
        var skillsEditor = byId('cv-skills-editor');
        var expEditor = byId('cv-experiences-editor');
        var eduEditor = byId('cv-education-editor');
        if (!skillsEditor || !expEditor || !eduEditor) {
            return;
        }

        skillsEditor.innerHTML = safeArray(state.skills).map(function (item, idx) {
            return buildSkillRow(item, idx);
        }).join('');

        expEditor.innerHTML = safeArray(state.experiences).map(function (item, idx) {
            return buildExpRow(item, idx, 'exp');
        }).join('');

        eduEditor.innerHTML = safeArray(state.education).map(function (item, idx) {
            return buildExpRow(item, idx, 'edu');
        }).join('');
    }

    function refresh(state) {
        collectInfos(state);
        renderPreview(state);
        renderScoresAndTips(state);
    }

    function switchTab(name) {
        document.querySelectorAll('[data-cv-tab]').forEach(function (btn) {
            btn.classList.toggle('is-active', btn.getAttribute('data-cv-tab') === name);
        });
        document.querySelectorAll('[data-cv-panel]').forEach(function (panel) {
            panel.classList.toggle('is-active', panel.getAttribute('data-cv-panel') === name);
        });
    }

    function callOllamaGenerate(state, action) {
        var status = byId('cv-ia-status');
        if (!status) {
            return;
        }

        status.textContent = 'Connexion à Ollama...';
        status.className = 'cv-status loading';

        collectInfos(state);

        var formData = new FormData();
        formData.append('prompt', '');
        formData.append('language', state.language || 'fr');
        formData.append('model', state.model || 'mistral');
        if (action === 'optimize') {
            formData.append('cv', JSON.stringify(state));
        }

        var endpoint = action === 'optimize' ? '/profil/optimizeCvAi' : '/profil/generateCvAi';

        fetch(getAppUrl(endpoint), {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (resp) {
                if (resp.status === 401) {
                    notify('Veuillez vous connecter', 'error');
                    window.location.href = getAppUrl('/auth/login');
                    return Promise.reject('Unauthorized');
                }
                if (!resp.ok) {
                    throw new Error('HTTP ' + resp.status);
                }
                return resp.json();
            })
            .then(function (result) {
                if (result.success && result.data) {
                    Object.assign(state, result.data);
                    if (!Array.isArray(state.aiAdvice) || !state.aiAdvice.length) {
                        state.aiAdvice = safeArray(state.recommendations || state.tips || []);
                    }
                    refresh(state);

                    status.textContent = '✅ ' + result.message + ' (' + (result.provider || 'local') + ')';
                    status.className = 'cv-status success';
                    notify(result.message, 'success');

                    setTimeout(function () {
                        status.textContent = '';
                        status.className = '';
                    }, 4000);
                } else {
                    throw new Error(result.message || 'Réponse invalide');
                }
            })
            .catch(function (err) {
                console.error('Error:', err);
                status.textContent = '❌ Erreur: ' + (err.message || 'Ollama indisponible');
                status.className = 'cv-status error';
                notify('Erreur Ollama: ' + (err.message || 'Vérifiez que Ollama est lancé sur localhost:11434'), 'error');

                setTimeout(function () {
                    status.textContent = '';
                    status.className = '';
                }, 5000);
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var root = byId('cv-ia-studio');
        if (!root) {
            return;
        }

        var seed = parseSeed() || {};
        var state = {
            personal: seed.personal || { fullName: '', title: '', email: '', phone: '', city: '', summary: '' },
            skills: safeArray(seed.skills),
            experiences: safeArray(seed.experiences),
            education: safeArray(seed.education),
            qualities: safeArray(seed.qualities || []),
            scores: seed.scores || { ats: 60, impact: 55, readability: 75 },
            recommendations: safeArray(seed.recommendations || seed.tips || []),
            aiAdvice: safeArray(seed.aiAdvice || seed.advice || seed.tips || seed.recommendations || []),
            slogan: seed.slogan || '',
            language: seed.language || 'fr',
            model: seed.model || 'mistral',
            template: seed.template || 'moderne',
            primaryColor: seed.primaryColor || '#2E6B3E'
        };

        var status = byId('cv-ia-status');
        var generateBtn = byId('cv-ia-generate');
        var optimizeBtn = byId('cv-ia-optimize');
        var downloadBtn = byId('cv-ia-download');

        var fullNameInput = byId('cv-full-name');
        if (fullNameInput && state.personal.fullName) {
            fullNameInput.value = state.personal.fullName;
        }

        if (byId('cv-title')) byId('cv-title').value = state.personal.title || '';
        if (byId('cv-email')) byId('cv-email').value = state.personal.email || '';
        if (byId('cv-phone')) byId('cv-phone').value = state.personal.phone || '';
        if (byId('cv-city')) byId('cv-city').value = state.personal.city || '';
        if (byId('cv-summary')) byId('cv-summary').value = state.personal.summary || '';
        if (byId('cv-template')) byId('cv-template').value = state.template;
        if (byId('cv-language')) byId('cv-language').value = state.language;
        if (byId('cv-model')) byId('cv-model').value = state.model;
        if (byId('cv-primary-color')) byId('cv-primary-color').value = state.primaryColor;

        rerenderEditors(state);
        refresh(state);

        root.addEventListener('click', function (event) {
            var target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }

            var tab = target.getAttribute('data-cv-tab');
            if (tab) {
                switchTab(tab);
                return;
            }

            if (target.hasAttribute('data-remove-skill')) {
                var idxSkill = Number.parseInt(target.getAttribute('data-remove-skill') || '-1', 10);
                if (idxSkill >= 0) {
                    state.skills.splice(idxSkill, 1);
                    rerenderEditors(state);
                    refresh(state);
                }
                return;
            }

            if (target.hasAttribute('data-remove-exp')) {
                var idxExp = Number.parseInt(target.getAttribute('data-remove-exp') || '-1', 10);
                if (idxExp >= 0) {
                    state.experiences.splice(idxExp, 1);
                    rerenderEditors(state);
                    refresh(state);
                }
                return;
            }

            if (target.hasAttribute('data-remove-edu')) {
                var idxEdu = Number.parseInt(target.getAttribute('data-remove-edu') || '-1', 10);
                if (idxEdu >= 0) {
                    state.education.splice(idxEdu, 1);
                    rerenderEditors(state);
                    refresh(state);
                }
            }
        });

        if (byId('cv-add-skill')) {
            byId('cv-add-skill').addEventListener('click', function () {
                state.skills.push({ name: '', level: 50 });
                rerenderEditors(state);
                refresh(state);
            });
        }

        if (byId('cv-add-exp')) {
            byId('cv-add-exp').addEventListener('click', function () {
                state.experiences.push({ role: '', company: '', start: '', end: '', description: '' });
                rerenderEditors(state);
                refresh(state);
            });
        }

        if (byId('cv-add-edu')) {
            byId('cv-add-edu').addEventListener('click', function () {
                state.education.push({ degree: '', school: '', start: '', end: '', description: '' });
                rerenderEditors(state);
                refresh(state);
            });
        }

        root.addEventListener('input', function () {
            refresh(state);
        });

        if (generateBtn) {
            generateBtn.addEventListener('click', function () {
                callOllamaGenerate(state, 'generate');
            });
        }

        if (optimizeBtn) {
            optimizeBtn.addEventListener('click', function () {
                callOllamaGenerate(state, 'optimize');
            });
        }

        // Ajouter le bouton d'import PDF
        var importPdfBtn = byId('cv-ia-import-pdf');
        if (importPdfBtn) {
            importPdfBtn.addEventListener('click', function () {
                var fileInput = byId('cv-pdf-file-input');
                if (!fileInput) {
                    var hidden = document.createElement('input');
                    hidden.type = 'file';
                    hidden.id = 'cv-pdf-file-input';
                    hidden.accept = 'application/pdf';
                    hidden.style.display = 'none';
                    hidden.addEventListener('change', function (e) {
                        if (this.files && this.files[0]) {
                            uploadAndParsePdf(this.files[0], state);
                        }
                        this.value = '';
                    });
                    document.body.appendChild(hidden);
                    fileInput = hidden;
                }
                fileInput.click();
            });
        }

        function uploadAndParsePdf(file, state) {
            var status = byId('cv-ia-status');
            if (!status) return;

            status.textContent = 'Traitement du PDF...';
            status.className = 'cv-status loading';

            var formData = new FormData();
            formData.append('pdf', file);

            fetch(getAppUrl('/api/pdf-import'), {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (resp) {
                    if (!resp.ok) {
                        throw new Error('HTTP ' + resp.status);
                    }
                    return resp.json();
                })
                .then(function (result) {
                    if (!result.success || !result.data) {
                        throw new Error(result.message || 'Impossible d\'importer le PDF');
                    }

                    var data = result.data;

                    // Remplir les champs personnels
                    if (data.personal) {
                        if (data.personal.fullName && byId('cv-full-name')) {
                            byId('cv-full-name').value = data.personal.fullName;
                            state.personal.fullName = data.personal.fullName;
                        }
                        if (data.personal.title && byId('cv-title')) {
                            byId('cv-title').value = data.personal.title;
                            state.personal.title = data.personal.title;
                        }
                        if (data.personal.email && byId('cv-email')) {
                            byId('cv-email').value = data.personal.email;
                            state.personal.email = data.personal.email;
                        }
                        if (data.personal.phone && byId('cv-phone')) {
                            byId('cv-phone').value = data.personal.phone;
                            state.personal.phone = data.personal.phone;
                        }
                        if (data.personal.city && byId('cv-city')) {
                            byId('cv-city').value = data.personal.city;
                            state.personal.city = data.personal.city;
                        }
                        if (data.personal.summary && byId('cv-summary')) {
                            byId('cv-summary').value = data.personal.summary;
                            state.personal.summary = data.personal.summary;
                        }
                    }

                    // Remplir les compétences
                    if (Array.isArray(data.skills) && data.skills.length > 0) {
                        state.skills = data.skills;
                    }

                    // Remplir les expériences
                    if (Array.isArray(data.experiences) && data.experiences.length > 0) {
                        state.experiences = data.experiences;
                    }

                    // Remplir les formations
                    if (Array.isArray(data.education) && data.education.length > 0) {
                        state.education = data.education;
                    }

                    // Rafraîchir l'interface
                    rerenderEditors(state);
                    refresh(state);

                    status.textContent = '✅ PDF importé avec succès! ' + 
                        (data.skills.length + data.experiences.length + data.education.length) + 
                        ' éléments extraits.';
                    status.className = 'cv-status success';
                    notify('PDF importé et parsé avec succès!', 'success');

                    // Afficher les informations extraites
                    var summary = '📋 Éléments importés:\n';
                    if (data.personal.fullName) summary += '👤 ' + data.personal.fullName + '\n';
                    if (data.personal.email) summary += '📧 ' + data.personal.email + '\n';
                    if (data.skills.length > 0) summary += '🎯 ' + data.skills.length + ' compétences\n';
                    if (data.experiences.length > 0) summary += '💼 ' + data.experiences.length + ' expériences\n';
                    if (data.education.length > 0) summary += '🎓 ' + data.education.length + ' formations\n';
                    console.log(summary);

                    setTimeout(function () {
                        status.textContent = '';
                        status.className = '';
                    }, 5000);
                })
                .catch(function (err) {
                    console.error('Erreur PDF:', err);
                    status.textContent = '❌ Erreur: ' + (err.message || 'Impossible d\'importer le PDF');
                    status.className = 'cv-status error';
                    notify('Erreur lors de l\'import du PDF: ' + (err.message || 'Vérifiez le format'), 'error');

                    setTimeout(function () {
                        status.textContent = '';
                        status.className = '';
                    }, 5000);
                });
        }

        if (downloadBtn) {
            downloadBtn.addEventListener('click', function () {
                var element = byId('cv-preview');
                if (!element) {
                    notify('CV non trouvé', 'error');
                    return;
                }

                var fileName = ((state.personal && state.personal.fullName) || 'cv').trim().replace(/\s+/g, '_') + '_' + new Date().toISOString().split('T')[0] + '.pdf';
                
                // Try html2pdf if available
                if (window.html2pdf && typeof window.html2pdf === 'function') {
                    try {
                        notify('Génération du PDF en cours...', 'info');
                        document.body.classList.add('cv-pdf-export');
                        
                        var pdfWorker = window.html2pdf().set({
                            margin: [6, 6, 6, 6],
                            filename: fileName,
                            image: { 
                                type: 'jpeg', 
                                quality: 0.95 
                            },
                            html2canvas: { 
                                scale: 1.6,
                                useCORS: true,
                                allowTaint: true,
                                backgroundColor: '#FFFFFF',
                                letterRendering: true,
                                logging: false
                            },
                            jsPDF: { 
                                unit: 'mm',
                                format: 'a4',
                                orientation: 'portrait',
                                compress: true
                            }
                        }).from(element).save();

                        if (pdfWorker && typeof pdfWorker.then === 'function') {
                            pdfWorker.then(function () {
                                document.body.classList.remove('cv-pdf-export');
                            }).catch(function () {
                                document.body.classList.remove('cv-pdf-export');
                            });
                        } else {
                            setTimeout(function () {
                                document.body.classList.remove('cv-pdf-export');
                            }, 2000);
                        }
                        
                        notify('PDF téléchargé: ' + fileName, 'success');
                        return;
                    } catch (err) {
                        document.body.classList.remove('cv-pdf-export');
                        notify('Erreur PDF html2pdf: ' + err.message, 'error');
                        console.error('html2pdf error:', err);
                    }
                }

                // Fallback: Browser Print to PDF
                notify('html2pdf non disponible, utilisation de l\'impression', 'info');
                document.body.classList.add('cv-pdf-export');
                window.focus();
                window.print();
                setTimeout(function () {
                    document.body.classList.remove('cv-pdf-export');
                }, 2000);
            });
        }
    });
})();