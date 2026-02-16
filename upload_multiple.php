<?php
header('Content-Type: application/json');

// Configurazione
$uploadDir = 'upload/';
$maxFiles = 3;
$allowedExtensions = ['jpg', 'jpeg', 'png'];
$maxFileSize = 5 * 1024 * 1024; // 5MB per file

// Crea la cartella uploads se non esiste
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to create upload directory'
        ]);
        exit;
    }
}

// Verifica che ci siano file caricati
if (empty($_FILES)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No files uploaded'
    ]);
    exit;
}

// Conta i file ricevuti
$fileCount = isset($_POST['count']) ? intval($_POST['count']) : 0;

if ($fileCount === 0 || $fileCount > $maxFiles) {
    echo json_encode([
        'status' => 'error',
        'message' => "Invalid number of files. Maximum $maxFiles allowed."
    ]);
    exit;
}

$uploadedFiles = [];
$errors = [];

// Processa ogni file
for ($i = 0; $i < $fileCount; $i++) {
    $fileKey = "file$i";
    
    if (!isset($_FILES[$fileKey])) {
        $errors[] = "File $i not found in upload";
        continue;
    }
    
    $file = $_FILES[$fileKey];
    
    // Verifica errori di upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Upload error for file $i: " . $file['error'];
        continue;
    }
    
    // Verifica dimensione file
    if ($file['size'] > $maxFileSize) {
        $errors[] = "File $i exceeds maximum size of 5MB";
        continue;
    }
    
    // Verifica estensione
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, $allowedExtensions)) {
        $errors[] = "File $i has invalid extension. Allowed: " . implode(', ', $allowedExtensions);
        continue;
    }
    
    // Verifica che sia un'immagine reale
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        $errors[] = "File $i is not a valid image";
        continue;
    }
    
    // Genera nome file unico
    $uniqueName = uniqid('img_', true) . '_' . time() . '.' . $fileExtension;
    $targetPath = $uploadDir . $uniqueName;
    
    // Sposta il file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $uploadedFiles[] = [
            'filename' => $uniqueName,
            'original_name' => $file['name'],
            'url' => $targetPath,
            'size' => $file['size'],
            'type' => $imageInfo['mime']
        ];
    } else {
        $errors[] = "Failed to move file $i to upload directory";
    }
}

// Prepara risposta
if (!empty($uploadedFiles)) {
    $response = [
        'status' => 'success',
        'message' => count($uploadedFiles) . ' file(s) uploaded successfully',
        'count' => count($uploadedFiles),
        'files' => $uploadedFiles,
        'urls' => array_column($uploadedFiles, 'url')
    ];
    
    if (!empty($errors)) {
        $response['warnings'] = $errors;
    }
    
    echo json_encode($response);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'No files were uploaded successfully',
        'errors' => $errors
    ]);
}
?>