<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Mon Profil - CraftLink</title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/css/style.css">
    <style>
        body { background:#f7f1e6; margin:0; }
        .edit-container { max-width:820px; margin:40px auto; padding:20px; }
        .form-header { background:linear-gradient(135deg, #2E6B3E 0%, #C49A6C 100%); color:white; padding:28px; border-radius:18px; margin-bottom:24px; }
        .form-card { background:white; border-radius:16px; padding:28px; box-shadow:0 8px 26px rgba(59,35,20,.08); }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
        .form-group { margin-bottom:18px; }
        .form-group label { display:block; font-weight:bold; margin-bottom:8px; color:#5b3b1f; }
        .form-group input, .form-group select { width:100%; padding:12px; border:1px solid #d8c7af; border-radius:10px; font-size:14px; }
        .form-group.has-error input, .form-group.has-error select { border-color:#b84040; }
        .error { color:#b84040; font-size:12px; margin-top:6px; }
        .help-text { color:#8b7a67; font-size:12px; margin-top:6px; }
        .preview { display:flex; align-items:center; gap:16px; margin-bottom:12px; }
        .face-box { border:1px solid #e4d8c5; border-radius:14px; padding:16px; background:#fcf8f2; }
        .face-actions { display:flex; gap:12px; flex-wrap:wrap; margin-top:12px; }
        .face-video { width:100%; max-height:260px; border-radius:12px; background:#000; display:none; }
        .avatar { width:88px; height:88px; border-radius:50%; background:#efe4d0; overflow:hidden; display:flex; align-items:center; justify-content:center; font-weight:bold; color:#5b3b1f; }
        .avatar img { width:100%; height:100%; object-fit:cover; }
        .form-actions { display:flex; gap:12px; flex-wrap:wrap; margin-top:24px; }
        .btn { padding:12px 18px; border:none; border-radius:10px; font-weight:bold; text-decoration:none; cursor:pointer; }
        .btn-primary { background:#2E6B3E; color:white; }
        .btn-secondary { background:#efe4d0; color:#5b3b1f; }
        @media (max-width: 700px) { .form-row { grid-template-columns:1fr; } }
    </style>
</head>
<body>
    <?php
    $imageUrl = !empty($user['image_profil']) ? APP_URL . '/' . ltrim($user['image_profil'], '/') : null;
    $initials = strtoupper(substr((string) ($user['prenom'] ?? 'U'), 0, 1) . substr((string) ($user['nom'] ?? ''), 0, 1));
    ?>
    <div class="edit-container">
        <div class="form-header">
            <h1 style="margin:0;">Modifier mon profil</h1>
            <p style="margin:8px 0 0;">Vous pouvez changer vos informations, votre photo et votre situation personnelle.</p>
        </div>

        <div class="form-card">
            <?php if (!empty($errors['general'])): ?>
                <div class="error" style="margin-bottom:16px;"><?php echo htmlspecialchars($errors['general']); ?></div>
            <?php endif; ?>

            <form method="POST" action="index.php?page=profile&method=edit" enctype="multipart/form-data">
                <div class="form-group <?php echo isset($errors['image_profil']) ? 'has-error' : ''; ?>">
                    <label for="image_profil">Image de profil</label>
                    <div class="preview">
                        <div class="avatar">
                            <?php if ($imageUrl): ?>
                                <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="Photo de profil">
                            <?php else: ?>
                                <?php echo htmlspecialchars($initials ?: 'U'); ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <input type="file" id="image_profil" name="image_profil" accept=".jpg,.jpeg,.png,.webp">
                            <div class="help-text">Formats acceptes : JPG, PNG, WEBP. Taille max : 2 Mo.</div>
                        </div>
                    </div>
                    <?php if (isset($errors['image_profil'])): ?>
                        <div class="error"><?php echo htmlspecialchars($errors['image_profil']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-row">
                    <div class="form-group <?php echo isset($errors['prenom']) ? 'has-error' : ''; ?>">
                        <label for="prenom">Prenom *</label>
                        <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($user['prenom'] ?? ''); ?>">
                        <?php if (isset($errors['prenom'])): ?><div class="error"><?php echo htmlspecialchars($errors['prenom']); ?></div><?php endif; ?>
                    </div>

                    <div class="form-group <?php echo isset($errors['nom']) ? 'has-error' : ''; ?>">
                        <label for="nom">Nom *</label>
                        <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($user['nom'] ?? ''); ?>">
                        <?php if (isset($errors['nom'])): ?><div class="error"><?php echo htmlspecialchars($errors['nom']); ?></div><?php endif; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group <?php echo isset($errors['date_naissance']) ? 'has-error' : ''; ?>">
                        <label for="date_naissance">Date de naissance</label>
                        <input type="date" id="date_naissance" name="date_naissance" value="<?php echo htmlspecialchars($user['date_naissance'] ?? ''); ?>">
                        <?php if (isset($errors['date_naissance'])): ?><div class="error"><?php echo htmlspecialchars($errors['date_naissance']); ?></div><?php endif; ?>
                    </div>

                    <div class="form-group <?php echo isset($errors['statut_marital']) ? 'has-error' : ''; ?>">
                        <label for="statut_marital">Statut marital</label>
                        <select id="statut_marital" name="statut_marital">
                            <option value="">-- Selectionner --</option>
                            <option value="celibataire" <?php echo ($user['statut_marital'] ?? '') === 'celibataire' ? 'selected' : ''; ?>>Celibataire</option>
                            <option value="marie" <?php echo ($user['statut_marital'] ?? '') === 'marie' ? 'selected' : ''; ?>>Marie(e)</option>
                            <option value="divorce" <?php echo ($user['statut_marital'] ?? '') === 'divorce' ? 'selected' : ''; ?>>Divorce(e)</option>
                            <option value="veuf" <?php echo ($user['statut_marital'] ?? '') === 'veuf' ? 'selected' : ''; ?>>Veuf(ve)</option>
                            <option value="autre" <?php echo ($user['statut_marital'] ?? '') === 'autre' ? 'selected' : ''; ?>>Autre</option>
                        </select>
                        <?php if (isset($errors['statut_marital'])): ?><div class="error"><?php echo htmlspecialchars($errors['statut_marital']); ?></div><?php endif; ?>
                    </div>
                </div>

                <div class="form-group <?php echo isset($errors['face_descriptor']) ? 'has-error' : ''; ?>">
                    <label>Identifiant du visage</label>
                    <div class="face-box">
                        <p style="margin:0 0 8px;">ID actuel : <strong id="rf-profile-face-id-label"><?php echo htmlspecialchars($user['face_id'] ?? 'non defini'); ?></strong></p>
                        <p style="margin:0 0 12px;" class="help-text">Vous pouvez conserver votre ID visage actuel ou capturer un nouveau visage pour le remplacer. Cette option est disponible pour tous les utilisateurs, y compris l'admin.</p>
                        <video id="rf-profile-video" class="face-video" autoplay muted playsinline></video>
                        <input type="hidden" id="rf-profile-descriptor" name="face_descriptor" value="">
                        <div id="rf-profile-status" class="help-text">Aucune nouvelle capture faciale.</div>
                        <div class="face-actions">
                            <button type="button" class="btn btn-secondary" onclick="startProfileFaceCapture()">Activer la camera</button>
                            <button type="button" class="btn btn-primary" onclick="captureProfileFace()">Capturer un nouveau visage</button>
                        </div>
                    </div>
                    <?php if (isset($errors['face_descriptor'])): ?>
                        <div class="error"><?php echo htmlspecialchars($errors['face_descriptor']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    <a href="index.php?page=profile" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
    <script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <script>
        const PROFILE_FACE_MODEL_URL = 'https://justadudewhohacks.github.io/face-api.js/models';
        const profileFaceState = {
            stream: null,
            loadingPromise: null
        };

        async function ensureProfileFaceApiLoaded() {
            const status = document.getElementById('rf-profile-status');

            if (!window.faceapi) {
                status.textContent = 'Librairie faciale indisponible. Rechargez la page et autorisez Internet.';
                throw new Error('Face API indisponible');
            }

            if (!profileFaceState.loadingPromise) {
                profileFaceState.loadingPromise = Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri(PROFILE_FACE_MODEL_URL),
                    faceapi.nets.faceLandmark68Net.loadFromUri(PROFILE_FACE_MODEL_URL),
                    faceapi.nets.faceRecognitionNet.loadFromUri(PROFILE_FACE_MODEL_URL)
                ]);
            }

            return profileFaceState.loadingPromise;
        }

        async function startProfileFaceCapture() {
            const video = document.getElementById('rf-profile-video');
            const status = document.getElementById('rf-profile-status');

            try {
                status.textContent = 'Chargement de la reconnaissance faciale...';
                await ensureProfileFaceApiLoaded();

                if (profileFaceState.stream) {
                    video.style.display = 'block';
                    status.textContent = 'Camera deja activee. Placez votre visage bien en face.';
                    return;
                }

                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user' },
                    audio: false
                });

                profileFaceState.stream = stream;
                video.srcObject = stream;
                video.style.display = 'block';
                status.textContent = 'Camera activee. Vous pouvez capturer un nouveau visage.';
            } catch (error) {
                status.textContent = 'Impossible d acceder a la camera.';
            }
        }

        function computeProfileFaceId(descriptor) {
            const raw = JSON.stringify(descriptor);
            let hash = 0;

            for (let i = 0; i < raw.length; i++) {
                hash = ((hash << 5) - hash + raw.charCodeAt(i)) | 0;
            }

            return 'Nouveau visage detecte';
        }

        async function captureProfileFace() {
            const video = document.getElementById('rf-profile-video');
            const hidden = document.getElementById('rf-profile-descriptor');
            const status = document.getElementById('rf-profile-status');
            const faceIdLabel = document.getElementById('rf-profile-face-id-label');

            try {
                await ensureProfileFaceApiLoaded();

                if (!profileFaceState.stream) {
                    await startProfileFaceCapture();
                }

                status.textContent = 'Analyse de votre visage...';
                const detection = await faceapi
                    .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                    .withFaceLandmarks()
                    .withFaceDescriptor();

                if (!detection) {
                    status.textContent = 'Aucun visage detecte. Veuillez reessayer.';
                    return;
                }

                const descriptor = Array.from(detection.descriptor);
                hidden.value = JSON.stringify(descriptor);
                faceIdLabel.textContent = computeProfileFaceId(descriptor);
                status.textContent = 'Nouveau visage capture. Enregistrez le formulaire pour mettre a jour votre ID visage.';
            } catch (error) {
                status.textContent = 'Erreur pendant la capture du visage.';
            }
        }
    </script>
</body>
</html>
