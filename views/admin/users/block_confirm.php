<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bloquer un Compte - CraftLink Admin</title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/admin.css">
    <style>
        .block-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
        }

        .warning-header {
            background: #fff3cd;
            color: #856404;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border: 1px solid #ffeaa7;
        }

        .warning-header h1 {
            margin-top: 0;
            font-size: 24px;
        }

        .form-card {
            background: white;
            border: 2px solid #ff9800;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .form-card h2 {
            color: #ff9800;
            margin-top: 0;
        }

        .user-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }

        .user-info p {
            margin: 5px 0;
        }

        .user-info strong {
            color: #333;
        }

        .warning-list {
            background: #f8f9fa;
            border-left: 4px solid #ff9800;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }

        .warning-list ul {
            margin: 10px 0;
            padding-left: 20px;
        }

        .warning-list li {
            margin-bottom: 8px;
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
            min-height: 100px;
        }

        .form-group textarea:focus {
            outline: none;
            border-color: #ff9800;
            box-shadow: 0 0 0 3px rgba(255, 152, 0, 0.1);
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-warning {
            background: #ff9800;
            color: white;
        }

        .btn-warning:hover {
            background: #e68900;
        }

        .btn-secondary {
            background: #f5f5f5;
            color: #333;
            border: 1px solid #ddd;
        }

        .btn-secondary:hover {
            background: #e8e8e8;
        }

        .info-box {
            background: #e3f2fd;
            color: #1976d2;
            border: 1px solid #bbdefb;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="block-container">
        <div class="warning-header">
            <h1>🚫 Bloquer un Compte</h1>
            <p>L'utilisateur ne pourra plus accéder à son espace.</p>
        </div>

        <div class="form-card">
            <h2>Confirmez le Blocage</h2>

            <div class="user-info">
                <p><strong>Utilisateur :</strong> <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></p>
                <p><strong>Email :</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Rôle :</strong> <?php echo ucfirst(htmlspecialchars($user['role'])); ?></p>
                <p><strong>Statut :</strong> <?php echo ucfirst(htmlspecialchars($user['etat_compte'])); ?></p>
            </div>

            <div class="info-box">
                <strong>ℹ️ Information :</strong><br>
                Une fois bloqué, cet utilisateur ne pourra plus se connecter ou accéder à son compte jusqu'à ce que vous le débloquiez.
            </div>

            <div class="warning-list">
                <strong>Conséquences du blocage :</strong>
                <ul>
                    <li>L'utilisateur ne pourra plus se connecter</li>
                    <li>Toutes ses sessions actives seront fermées</li>
                    <li>L'accès à tous ses données sera restreint</li>
                    <li>Vous pourrez débloquer le compte à tout moment</li>
                </ul>
            </div>

            <form method="POST" action="index.php?action=users&method=blockForm&id=<?php echo $user['id_user']; ?>" id="blockForm">
                <div class="form-group">
                    <label for="reason">Raison du Blocage (optionnel)</label>
                    <textarea id="reason" name="reason" placeholder="Entrez la raison du blocage..."></textarea>
                    <div style="color: #666; font-size: 12px; margin-top: 5px;">
                        Cette raison sera notée pour le suivi administratif
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-warning" onclick="confirmBlock()">
                        ✓ Bloquer l'Utilisateur
                    </button>
                    <a href="index.php?action=users" class="btn btn-secondary" style="text-decoration: none;">
                        ← Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function confirmBlock() {
            const userEmail = '<?php echo htmlspecialchars($user['email']); ?>';
            
            if (!confirm('Êtes-vous sûr de vouloir bloquer le compte de ' + userEmail + ' ?')) {
                return;
            }

            const userId = <?php echo $user['id_user']; ?>;
            const reason = document.getElementById('reason').value;

            const formData = new FormData();
            formData.append('id', userId);
            formData.append('reason', reason);

            fetch('index.php?action=users&method=toggleBlock', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Compte bloqué avec succès.');
                    window.location.href = 'index.php?action=users';
                } else {
                    alert('Erreur : ' + (data.message || 'Impossible de bloquer le compte.'));
                }
            })
            .catch(error => {
                alert('Erreur : ' + error.message);
            });
        }
    </script>
</body>
</html>
