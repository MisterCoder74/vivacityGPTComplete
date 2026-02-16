<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$email = $data['email'];
$videoId = $data['videoId'];
$apiKey = $data['apiKey'];

// Scarica il video
$ch = curl_init("https://api.openai.com/v1/videos/{$videoId}/content");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$videoContent = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$videoContent) {
    echo json_encode(['success' => false, 'error' => 'Impossibile scaricare il video']);
    exit;
}

// Salva il video
$videoDir = 'videos';
if (!file_exists($videoDir)) {
    mkdir($videoDir, 0755, true);
}

$filename = $videoId . '.mp4';
$filepath = $videoDir . '/' . $filename;
file_put_contents($filepath, $videoContent);

// Link diretto
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'];
$videoUrl = $protocol . $domain . '/' . $filepath;

// Email HTML
$subject = "Il tuo video Sora è pronto! 🎬";
$htmlMessage = "
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #667eea; }
        .button { display: inline-block; padding: 15px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
        .footer { margin-top: 30px; font-size: 12px; color: #999; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🎬 Il tuo video è pronto!</h1>
        <p>Ciao!</p>
        <p>Il video che hai generato con Sora è stato completato con successo ed è pronto per essere visualizzato.</p>
        
        <a href='{$videoUrl}' class='button'>📹 GUARDA IL VIDEO</a>
        
        <p style='font-size: 14px; color: #666;'>
            <strong>Video ID:</strong> {$videoId}<br>
            <strong>Link diretto:</strong> <a href='{$videoUrl}'>{$videoUrl}</a>
        </p>
        
        <p style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;'>
            ⚠️ <strong>Nota:</strong> Il link rimarrà attivo per 7 giorni. Scarica il video se desideri conservarlo.
        </p>
        
        <div class='footer'>
            <p>Generato con Sora Video Generator</p>
        </div>
    </div>
</body>
</html>
";

$headers = "From: Sora Video Generator <noreply@tuosito.com>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

$success = mail($email, $subject, $htmlMessage, $headers);

echo json_encode([
    'success' => $success, 
    'videoUrl' => $videoUrl
]);
?>