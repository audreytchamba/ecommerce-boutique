<?php
/**
 * actions/send-confirmation-email.php
 * Fonction d'envoi de l'e-mail de confirmation de commande au client.
 * Utilise PHPMailer si le vendor Composer est présent (recommandé),
 * avec repli automatique sur mail() natif sinon (dépannage rapide en local).
 *
 * N'affiche jamais rien : lève simplement une exception en cas d'échec,
 * à charge de l'appelant (process_order.php) de logger sans bloquer la
 * commande déjà enregistrée en base.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/mail-config.php';

/**
 * @param array<string, mixed> $order  Ligne de la table orders (déjà insérée)
 * @param array<int, array<string, mixed>> $items  Lignes order_items correspondantes
 */
function send_order_confirmation_email(array $order, array $items): bool
{
    $subject = sprintf('Confirmation de votre commande %s — %s', $order['order_ref'], SITE_NAME);
    $htmlBody = build_order_email_html($order, $items);

    $vendorAutoload = __DIR__ . '/../vendor/autoload.php';

    if (file_exists($vendorAutoload)) {
        require_once $vendorAutoload;
        return send_via_phpmailer($order['customer_email'], $subject, $htmlBody);
    }

    return send_via_native_mail($order['customer_email'], $subject, $htmlBody);
}

function send_via_phpmailer(string $toEmail, string $subject, string $htmlBody): bool
{
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        if (SMTP_USER !== '') {
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
        } else {
            $mail->SMTPAuth = false; // ex: MailHog en local, pas d'auth requise
        }

        if (SMTP_SECURE !== '') {
            $mail->SMTPSecure = SMTP_SECURE; // 'tls' ou 'ssl'
        } else {
            $mail->SMTPAutoTLS = false;
        }

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($toEmail);
        if (defined('ADMIN_NOTIFICATION_EMAIL') && ADMIN_NOTIFICATION_EMAIL !== '') {
            $mail->addBCC(ADMIN_NOTIFICATION_EMAIL);
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags($htmlBody);

        $mail->send();
        return true;
    } catch (\Throwable $e) {
        error_log('Échec envoi email (PHPMailer) : ' . $e->getMessage());
        return false;
    }
}

function send_via_native_mail(string $toEmail, string $subject, string $htmlBody): bool
{
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . ">\r\n";

    $success = @mail($toEmail, $subject, $htmlBody, $headers);

    if (!$success) {
        error_log("Échec envoi email (mail() natif) vers {$toEmail}");
    }

    return $success;
}

function build_order_email_html(array $order, array $items): string
{
    $rows = '';
    foreach ($items as $item) {
        $rows .= sprintf(
            '<tr>
                <td style="padding:8px;border-bottom:1px solid #eee;">%s</td>
                <td style="padding:8px;border-bottom:1px solid #eee;text-align:center;">%d</td>
                <td style="padding:8px;border-bottom:1px solid #eee;text-align:right;">%s FCFA</td>
            </tr>',
            htmlspecialchars((string) $item['product_name'], ENT_QUOTES, 'UTF-8'),
            (int) $item['quantity'],
            number_format((float) $item['subtotal'], 0, ',', ' ')
        );
    }

    $lastname   = htmlspecialchars((string) $order['customer_lastname'], ENT_QUOTES, 'UTF-8');
    $firstname  = htmlspecialchars((string) $order['customer_firstname'], ENT_QUOTES, 'UTF-8');
    $ref        = htmlspecialchars((string) $order['order_ref'], ENT_QUOTES, 'UTF-8');
    $city       = htmlspecialchars((string) $order['city'], ENT_QUOTES, 'UTF-8');
    $district   = htmlspecialchars((string) $order['neighborhood'], ENT_QUOTES, 'UTF-8');
    $deliveryDate = htmlspecialchars((string) $order['delivery_date'], ENT_QUOTES, 'UTF-8');
    $total      = number_format((float) $order['total_amount'], 0, ',', ' ');
    $siteName   = htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8');

    return <<<HTML
    <div style="font-family: Arial, sans-serif; max-width:600px; margin:auto; color:#2B2118;">
        <div style="background:#4A0E17; padding:20px; text-align:center;">
            <h1 style="color:#D4AF37; margin:0;">{$siteName}</h1>
        </div>
        <div style="padding:24px;">
            <p>Bonjour {$firstname} {$lastname},</p>
            <p>Votre commande <strong>{$ref}</strong> a bien été enregistrée. Voici le récapitulatif :</p>

            <table style="width:100%; border-collapse:collapse; margin:16px 0;">
                <thead>
                    <tr style="background:#F3ECE0;">
                        <th style="padding:8px;text-align:left;">Article</th>
                        <th style="padding:8px;text-align:center;">Qté</th>
                        <th style="padding:8px;text-align:right;">Sous-total</th>
                    </tr>
                </thead>
                <tbody>{$rows}</tbody>
            </table>

            <p style="text-align:right; font-size:1.1em; font-weight:bold; color:#4A0E17;">
                Total : {$total} FCFA
            </p>

            <h3 style="color:#4A0E17;">Livraison</h3>
            <p>
                {$city}, {$district}<br>
                Date souhaitée : {$deliveryDate}
            </p>

            <p style="background:#D4AF37; color:#2A070C; padding:10px; border-radius:6px; display:inline-block;">
                💵 Paiement à la livraison
            </p>

            <p style="margin-top:24px; font-size:0.85em; color:#6B5E4F;">
                Merci de votre confiance.<br>{$siteName}
            </p>
        </div>
    </div>
    HTML;
}
