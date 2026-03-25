<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Incluir los archivos de PHPMailer de forma local
require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

// Incluir credenciales de forma segura
if (file_exists(__DIR__ . '/mailer_config.php')) {
    include_once __DIR__ . '/mailer_config.php';
}

function sendArkEmail($toEmail, $subject, $bodyHtml)
{
    $mail = new PHPMailer(true);

    try {
        // Configuraciones del servidor SMTP
        $mail->isSMTP();
        $mail->Host = defined('MAILER_HOST') ? MAILER_HOST : 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = defined('MAILER_USER') ? MAILER_USER : '';
        $mail->Password = defined('MAILER_PASS') ? MAILER_PASS : '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = defined('MAILER_PORT') ? MAILER_PORT : 587;

        // Propiedades de codificación
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        // Remitente y destinatario
        $mail->setFrom('no-reply@arksurvivalhub.com', 'ARK Survival Hub');
        $mail->addAddress($toEmail);

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $bodyHtml;
        $mail->AltBody = strip_tags(str_replace('<br>', "\n", $bodyHtml));

        $mail->send();
        return true;
    }
    catch (Exception $e) {
        error_log("El mensaje no pudo ser enviado. Error de Mailer: {$mail->ErrorInfo}");
        return false;
    }
}
?>
