<?php
// views/auth/register.php
// Variables: $error
?>
<form action="" method="POST">
    <input type="text" name="nom" placeholder="Nom" minlength="2" maxlength="60" pattern="[A-Za-zÀ-ÖØ-öø-ÿ' -]+" required>
    <input type="text" name="prenom" placeholder="Prénom" minlength="2" maxlength="60" pattern="[A-Za-zÀ-ÖØ-öø-ÿ' -]+" required>
    <input type="email" name="email" placeholder="Email" maxlength="120" required>
    <input type="password" name="password" placeholder="Mot de passe" minlength="8" maxlength="72" required>
    <select name="role">
        <option value="candidat">Candidat</option>
        <option value="recruteur">Recruteur</option>
    </select>
    <button type="submit">S'inscrire</button>
    <?php if (!empty($error)): ?>
        <div style="color:#b00; margin-top:10px; text-align:center;"> <?= htmlspecialchars($error) ?> </div>
    <?php endif; ?>
</form>
