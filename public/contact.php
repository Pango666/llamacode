<?php
// contact.php — LLAMACODE
// Enviar formulario a correo usando mail()

/* ========= CONFIG ========= */
$TO = 'joseckan1@gmail.com';
$FROM_EMAIL = 'noreply@' . ($_SERVER['SERVER_NAME'] ?? 'llamacode.dev'); // cámbialo si quieres
$SITE_NAME = 'LLAMACODE';
/* ========================= */

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Método no permitido');
}

// Recoger y sanear
$nombre  = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS));
$email   = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
$mensaje = trim(filter_input(INPUT_POST, 'message', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

// Honeypot (campo oculto opcional en el form: <input type="text" name="website" style="display:none">)
$honeypot = isset($_POST['website']) ? trim($_POST['website']) : '';
if ($honeypot !== '') {
  // Bot detectado: responder OK para no revelar nada
  ok('Mensaje recibido.');
}

// Validación básica
$errores = [];
if ($nombre === '' || mb_strlen($nombre) < 2) $errores[] = 'Nombre inválido.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = 'Correo inválido.';
if ($mensaje === '' || mb_strlen($mensaje) < 5) $errores[] = 'Mensaje demasiado corto.';

if ($errores) {
  fail('Revisa los datos del formulario.', $errores);
}

// Construir email (HTML)
$ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ua  = $_SERVER['HTTP_USER_AGENT'] ?? '';
$date= date('Y-m-d H:i:s');

$subject = "Nuevo contacto — $SITE_NAME: $nombre";
$subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

$body = <<<HTML
<!doctype html>
<html lang="es">
<meta charset="utf-8">
<title>Contacto</title>
<body style="font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Arial,sans-serif;background:#f6f7fb;padding:24px;">
  <div style="max-width:640px;margin:auto;background:#fff;border-radius:12px;padding:20px;border:1px solid #e5e7eb">
    <h2 style="margin:0 0 12px 0;">Nuevo mensaje desde el sitio</h2>
    <table cellpadding="6" style="width:100%;border-collapse:collapse">
      <tr><td style="width:140px;color:#6b7280;">Nombre</td><td><strong>{$nombre}</strong></td></tr>
      <tr><td style="color:#6b7280;">Correo</td><td><a href="mailto:{$email}">{$email}</a></td></tr>
      <tr><td style="color:#6b7280;">Fecha</td><td>{$date}</td></tr>
      <tr><td style="color:#6b7280;">IP</td><td>{$ip}</td></tr>
      <tr><td style="color:#6b7280;">Navegador</td><td style="word-break:break-word;">{$ua}</td></tr>
    </table>
    <hr style="border:none;border-top:1px solid #e5e7eb;margin:16px 0">
    <p style="white-space:pre-wrap;margin:0;">{$mensaje}</p>
  </div>
</body>
</html>
HTML;

$headers = [];
$headers[] = "MIME-Version: 1.0";
$headers[] = "Content-Type: text/html; charset=UTF-8";
$headers[] = "From: $SITE_NAME <{$FROM_EMAIL}>";
$headers[] = "Reply-To: {$nombre} <{$email}>";
$headers[] = "X-Mailer: PHP/" . phpversion();

// Enviar (con envelope sender para mejorar entrega)
$ok = @mail($TO, $subject, $body, implode("\r\n", $headers), "-f{$FROM_EMAIL}");

if ($ok) {
  ok('¡Gracias! Te responderemos pronto.');
} else {
  // Log opcional si falla (revisa permisos en /tmp)
  @file_put_contents('/tmp/contact.log', "[".date('c')."] Falló mail() de {$email}\n", FILE_APPEND);
  fail('No pudimos enviar tu mensaje en este momento. Inténtalo más tarde.');
}

/* ========= Helpers ========= */
function isAjax() {
  return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
         strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}
function ok($msg) {
  if (isAjax()) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'message' => $msg]);
  } else {
    thanksPage(true, $msg);
  }
  exit;
}
function fail($msg, $errors = []) {
  if (isAjax()) {
    header('Content-Type: application/json'); http_response_code(422);
    echo json_encode(['ok' => false, 'message' => $msg, 'errors' => $errors]);
  } else {
    thanksPage(false, $msg, $errors);
  }
  exit;
}
function thanksPage($success, $msg, $errors = []) {
  $color = $success ? '#10b981' : '#ef4444';
  $title = $success ? 'Mensaje enviado' : 'Error al enviar';
  echo "<!doctype html><meta charset='utf-8'><title>{$title}</title>
  <meta name='viewport' content='width=device-width,initial-scale=1'>
  <body style=\"font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Arial,sans-serif;background:#f6f7fb;padding:24px;\">
    <div style=\"max-width:720px;margin:auto;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;\">
      <h2 style=\"margin:0 0 8px 0;color:{$color}\">{$title}</h2>
      <p>{$msg}</p>";
  if ($errors) {
    echo "<ul>";
    foreach ($errors as $e) echo "<li>".htmlspecialchars($e)."</li>";
    echo "</ul>";
  }
  echo "<p><a href='/' style='color:#0b74b7'>Volver al sitio</a></p></div></body>";
}
