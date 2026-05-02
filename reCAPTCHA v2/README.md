# reCAPTCHA v2 - CraftLink

Ce dossier contient le code principal de verification reCAPTCHA v2:

- `RecaptchaV2.php`: helper serveur qui verifie le token avec Google.

## Configuration

Ajoute tes cles Google dans `config/config.local.php`:

```php
$_ENV['RECAPTCHA_V2_SITE_KEY'] = 'ta-site-key';
$_ENV['RECAPTCHA_V2_SECRET_KEY'] = 'ta-secret-key';
```

Sans ces deux cles, reCAPTCHA reste desactive pour ne pas bloquer le site local.

## Pages protegees

- Connexion classique par email/mot de passe
- Inscription

La connexion par reconnaissance faciale reste separee pour garder le flux automatique.
