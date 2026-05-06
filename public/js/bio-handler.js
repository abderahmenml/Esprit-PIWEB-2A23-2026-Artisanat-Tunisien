// Bio Handler - manages bio generation and saving
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        var bioTextarea = document.getElementById('bio-input-textarea');
        var bioSaveBtn = document.getElementById('bio-save-btn');
        var ppeiGenerateBtn = document.getElementById('ppei-generate-inline');
        var bioStatus = document.getElementById('bio-status');

        if (!bioTextarea || !ppeiGenerateBtn) return;

        // Show save button when bio is modified
        bioTextarea.addEventListener('input', function() {
            if (bioTextarea.value.trim() !== '') {
                if (bioSaveBtn) bioSaveBtn.style.display = 'block';
            }
        });

        // Save bio
        if (bioSaveBtn) {
            bioSaveBtn.addEventListener('click', function() {
                var bioText = bioTextarea.value.trim();
                if (bioText === '') {
                    if (bioStatus) bioStatus.textContent = '❌ La bio ne peut pas être vide';
                    return;
                }
                bioSaveBtn.disabled = true;
                bioSaveBtn.textContent = '⏳ Sauvegarde...';
                if (bioStatus) bioStatus.textContent = '';

                // Assuming appUrl is available from index.js
                var updateUrl = (typeof appUrl === 'function') 
                    ? appUrl('/profil/updateBio')
                    : '/profil/updateBio';

                fetch(updateUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ bio: bioText })
                })
                .then(function(resp) { return resp.json(); })
                .then(function(data) {
                    if (data.success) {
                        bioSaveBtn.textContent = '✅ Enregistrée!';
                        if (bioStatus) bioStatus.textContent = '✅ Bio sauvegardée avec succès';
                        setTimeout(function() {
                            bioSaveBtn.style.display = 'none';
                            bioSaveBtn.disabled = false;
                            bioSaveBtn.textContent = '💾 Enregistrer';
                        }, 2000);
                    } else {
                        if (bioStatus) bioStatus.textContent = '❌ ' + (data.message || 'Erreur');
                        bioSaveBtn.disabled = false;
                        bioSaveBtn.textContent = '💾 Enregistrer';
                    }
                })
                .catch(function(err) {
                    if (bioStatus) bioStatus.textContent = '❌ Erreur réseau';
                    bioSaveBtn.disabled = false;
                    bioSaveBtn.textContent = '💾 Enregistrer';
                    console.error(err);
                });
            });
        }

        // Generate professional bio using Ollama
        ppeiGenerateBtn.addEventListener('click', function() {
            var bioText = bioTextarea.value.trim();
            if (bioText === '') {
                if (bioStatus) bioStatus.textContent = '⚠️ Écrivez une bio avant de la générer';
                return;
            }

            ppeiGenerateBtn.disabled = true;
            ppeiGenerateBtn.textContent = '⏳ Génération en cours...';
            if (bioStatus) bioStatus.textContent = '⏳ Amélioration de votre bio via Ollama...';

            var generateUrl = (typeof appUrl === 'function') 
                ? appUrl('/profil/generateBioFromText')
                : '/profil/generateBioFromText';

            fetch(generateUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ bio_input: bioText })
            })
            .then(function(resp) { return resp.json(); })
            .then(function(data) {
                ppeiGenerateBtn.disabled = false;
                ppeiGenerateBtn.textContent = '🚀 Générer une version professionnelle (Ollama)';
                
                if (data.success && data.generated_bio) {
                    bioTextarea.value = data.generated_bio;
                    if (bioStatus) bioStatus.textContent = '✅ Bio générée! Cliquez "Enregistrer" pour sauvegarder';
                    if (bioSaveBtn) bioSaveBtn.style.display = 'block';
                } else {
                    var msg = data.message || 'Erreur génération';
                    if (data.curl_error) msg += ' (' + data.curl_error + ')';
                    if (bioStatus) bioStatus.textContent = '❌ ' + msg;
                    console.error(data);
                }
            })
            .catch(function(err) {
                ppeiGenerateBtn.disabled = false;
                ppeiGenerateBtn.textContent = '🚀 Générer une version professionnelle (Ollama)';
                if (bioStatus) bioStatus.textContent = '❌ Erreur réseau - vérifiez que Ollama est accessible';
                console.error(err);
            });
        });
    });
})();
