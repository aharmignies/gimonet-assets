<?php
/**
 * Hermelin Jouzeau — Handler du formulaire de contact
 *
 * Déploiement o2switch :
 *   1. Copier ce fichier dans `public_html/contact.php`
 *   2. Créer `public_html/config.local.php` (jamais committer) avec :
 *        <?php
 *        return [
 *          'smtp_host' => 'mail.hermelin-peinture.fr',
 *          'smtp_port' => 465,
 *          'smtp_user' => 'contact@hermelin-peinture.fr',
 *          'smtp_pass' => 'MOT_DE_PASSE_BOITE_MAIL',
 *          'smtp_from' => 'contact@hermelin-peinture.fr',
 *          'smtp_to'   => 'contact@hermelin-peinture.fr',
 *        ];
 *   3. Téléverser PHPMailer dans `public_html/lib/PHPMailer/`
 *      (depuis https://github.com/PHPMailer/PHPMailer/releases — récupérer
 *       le zip de la dernière release, garder uniquement le dossier `src/`)
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée.']);
    exit;
}

// --- 1. Honeypot anti-bot ---
if (!empty($_POST['website'] ?? '')) {
    // On répond "succès" silencieusement pour ne pas alerter le bot
    echo json_encode(['success' => true]);
    exit;
}

// --- 2. Validation des champs ---
$name    = trim((string) ($_POST['name']    ?? ''));
$email   = trim((string) ($_POST['email']   ?? ''));
$phone   = trim((string) ($_POST['phone']   ?? ''));
$project = trim((string) ($_POST['project'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));
$rgpd    = isset($_POST['rgpd']);

$errors = [];
if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
    $errors[] = 'Nom invalide.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email invalide.';
}
if ($project === '') {
    $errors[] = 'Type de projet requis.';
}
if (mb_strlen($message) < 10 || mb_strlen($message) > 5000) {
    $errors[] = 'Message invalide (10 à 5000 caractères).';
}
if (!$rgpd) {
    $errors[] = 'Vous devez accepter la politique de confidentialité.';
}

if ($errors) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => implode(' ', $errors)]);
    exit;
}

// --- 3. Rate-limit basique par IP (3 envois / heure) ---
$ip       = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$logFile  = sys_get_temp_dir() . '/hj-contact-' . md5($ip) . '.log';
$now      = time();
$attempts = file_exists($logFile)
    ? array_filter((array) json_decode((string) file_get_contents($logFile), true), fn($t) => $t > $now - 3600)
    : [];

if (count($attempts) >= 3) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Trop de demandes. Réessayez dans une heure.']);
    exit;
}
$attempts[] = $now;
file_put_contents($logFile, json_encode(array_values($attempts)));

// --- 4. Chargement config + PHPMailer ---
$configFile = __DIR__ . '/config.local.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Configuration serveur manquante.']);
    error_log('[contact.php] config.local.php manquant');
    exit;
}
$config = require $configFile;

$phpmailerBase = __DIR__ . '/lib/PHPMailer/src';
if (!file_exists($phpmailerBase . '/PHPMailer.php')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Bibliothèque email manquante.']);
    error_log('[contact.php] PHPMailer non trouvé');
    exit;
}
require_once $phpmailerBase . '/Exception.php';
require_once $phpmailerBase . '/PHPMailer.php';
require_once $phpmailerBase . '/SMTP.php';

// --- 5. Envoi de l'email ---
$mail = new PHPMailer\PHPMailer\PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = $config['smtp_host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['smtp_user'];
    $mail->Password   = $config['smtp_pass'];
    $mail->SMTPSecure = $config['smtp_port'] === 465
        ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = (int) $config['smtp_port'];
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom($config['smtp_from'], 'Site Hermelin Jouzeau');
    $mail->addAddress($config['smtp_to']);
    $mail->addReplyTo($email, $name);

    $mail->Subject = "Nouveau message — " . $name . " — " . $project;
    $mail->isHTML(false);
    $mail->Body = "Nouveau message depuis le site hermelin-peinture.fr\n"
        . "------------------------------------------------------\n\n"
        . "Nom         : {$name}\n"
        . "Email       : {$email}\n"
        . "Téléphone   : " . ($phone !== '' ? $phone : '(non renseigné)') . "\n"
        . "Projet      : {$project}\n\n"
        . "Message :\n{$message}\n";

    $mail->send();
    echo json_encode(['success' => true]);
} catch (\Throwable $e) {
    http_response_code(500);
    error_log('[contact.php] Erreur envoi : ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => "L'envoi a échoué. Merci de nous écrire directement à contact@hermelin-peinture.fr."]);
}
