<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supprimer Mon Compte - CraftLink</title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/style.css">
    <style>
        body { background:#f7f1e6; margin:0; }
        .delete-container { max-width:700px; margin:40px auto; padding:20px; }
        .warning-header, .form-card { background:white; border-radius:16px; padding:24px; box-shadow:0 8px 26px rgba(59,35,20,.08); }
        .warning-header { border-left:6px solid #b84040; margin-bottom:20px; }
        .form-card { border:1px solid #ead8c0; }
        .warning-list { background:#fff5f5; border-radius:12px; padding:16px; margin:18px 0; }
        .form-group { margin-bottom:18px; }
        .form-group label { display:block; font-weight:bold; margin-bottom:8px; color:#5b3b1f; }
        .form-group input { width:100%; padding:12px; border:1px solid #d8c7af; border-radius:10px; }
        .form-actions { display:flex; gap:12px; flex-wrap:wrap; margin-top:20px; }
        .btn { padding:12px 18px; border:none; border-radius:10px; font-weight:bold; cursor:pointer; text-decoration:none; }
        .btn-danger { background:#b84040; color:white; }
        .btn-secondary { background:#efe4d0; color:#5b3b1f; }
    </style>
</head>
<body>
    <div class="delete-container">
        <div class="warning-header">
            <h1 style="margin-top:0;">Suppression irreversible</h1>
            <p>Cette action supprimera votre compte et votre profil. Elle ne peut pas etre annulee.</p>
        </div>

        <div class="form-card">
            <div class="warning-list">
                <strong>Seront supprimes :</strong>
                <ul>
                    <li>Votre profil utilisateur</li>
                    <li>Votre image de profil</li>
                    <li>Vos informations personnelles</li>
                    <li>Vos donnees de compte</li>
                </ul>
            </div>

            <form id="deleteForm" method="POST" action="index.php?page=profile&method=deleteAccount">
                <div class="form-group">
                    <label for="password">Confirmez votre mot de passe</label>
                    <input type="password" id="password" name="password" placeholder="Entrez votre mot de passe" required>
                </div>

                <div class="form-group" style="display:flex; gap:10px; align-items:center;">
                    <input type="checkbox" id="confirm" style="width:auto;">
                    <label for="confirm" style="margin:0;">Je comprends que cette suppression est definitive.</label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-danger" id="deleteBtn" disabled>Supprimer mon compte</button>
                    <a href="index.php?page=profile" class="btn btn-secondary">Retour au profil</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const confirmCheckbox = document.getElementById('confirm');
        const deleteBtn = document.getElementById('deleteBtn');
        const deleteForm = document.getElementById('deleteForm');

        confirmCheckbox.addEventListener('change', function () {
            deleteBtn.disabled = !this.checked;
        });

        deleteForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            if (!confirmCheckbox.checked) return;

            const formData = new FormData(deleteForm);
            const response = await fetch('index.php?page=profile&method=deleteAccount', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                window.location.href = data.redirect || 'index.php?page=login';
                return;
            }

            alert(data.message || 'Suppression impossible.');
        });
    </script>
</body>
</html>
