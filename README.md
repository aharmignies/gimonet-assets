# Hermelin Jouzeau — Site vitrine

Site vitrine pour **Hermelin Jouzeau Peinture & Sol**, artisan peintre
et poseur de sols.

## Stack

- [Astro](https://astro.build/) — générateur de sites statiques
- HTML / CSS vanille (variables CSS pour la charte)
- PHP (formulaire de contact, via PHPMailer SMTP)
- Hébergement : o2switch (mutualisé français, déploiement FTP)

## Développement local

```bash
npm install
npm run dev        # http://localhost:4321
npm run build      # génère ./dist/
npm run preview    # sert ./dist/ localement (statique seulement)
```

Pour tester le formulaire PHP en local :

```bash
npm run build
php -S localhost:8000 -t dist/
```

## Pages

| Route             | Fichier                       |
|-------------------|-------------------------------|
| `/`               | `src/pages/index.astro`       |
| `/services`       | `src/pages/services.astro`    |
| `/realisations`   | `src/pages/realisations.astro`|
| `/a-propos`       | `src/pages/a-propos.astro`    |
| `/contact`        | `src/pages/contact.astro`     |

## Déploiement sur o2switch

1. `npm run build` → génère `dist/`
2. Téléverser le contenu de `dist/` dans `public_html/` (FileZilla ou
   cPanel File Manager)
3. Créer la boîte mail `contact@hermelin-peinture.fr` dans cPanel
4. Télécharger PHPMailer
   ([releases](https://github.com/PHPMailer/PHPMailer/releases)) et
   placer le dossier `src/` dans `public_html/lib/PHPMailer/`
5. Créer `public_html/config.local.php` (**ne jamais committer**) :

   ```php
   <?php
   return [
     'smtp_host' => 'mail.hermelin-peinture.fr',
     'smtp_port' => 465,
     'smtp_user' => 'contact@hermelin-peinture.fr',
     'smtp_pass' => 'MOT_DE_PASSE_BOITE_MAIL',
     'smtp_from' => 'contact@hermelin-peinture.fr',
     'smtp_to'   => 'contact@hermelin-peinture.fr',
   ];
   ```

6. Activer HTTPS via cPanel → SSL/TLS Status (Let's Encrypt)
7. Tester le formulaire en envoyant un message depuis `/contact`

## Charte graphique

Définie dans `src/styles/global.css` via variables CSS :

- `--color-primary: #8b1a1a` (rouge bordeaux du logo)
- `--color-dark: #1a1a1a`
- `--color-light: #fafafa`
- `--color-text: #4a4a4a`
- Fonts : Playfair Display (titres) + Inter (texte)

## Workflow de design avec Stitch

Voir [`/root/.claude/plans/je-voudrais-faire-un-luminous-token.md`](#)
pour le brief Stitch prêt à copier-coller.
