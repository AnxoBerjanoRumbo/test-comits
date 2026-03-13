<?php
include_once 'cloudinary_config.php';

/**
 * Sube una imagen a Cloudinary usando cURL
 * @param string $filePath Ruta temporal del archivo ($_FILES['tmp_name'])
 * @param string $folder Carpeta en Cloudinary (perfiles, dinos, etc)
 * @return string|false La URL de la imagen subida o false si hay error
 */
function subirImagenACloudinary($filePath, $folder = 'ark_hub')
{
    // Si no hay configuración, no intentamos subir
    if (empty(CLOUDINARY_CLOUD_NAME) || empty(CLOUDINARY_API_KEY)) {
        return false;
    }

    $timestamp = time();
    // Generar la firma (Signature) requerida por Cloudinary para subidas firmadas
    // Si prefieres usar un Upload Preset sin firmar, la lógica cambiaría un poco.
    $params = [
        'folder' => $folder,
        'timestamp' => $timestamp
    ];

    // El API Secret solo se usa para firmar, no se envía en la petición
    ksort($params);
    $signStr = "";
    foreach ($params as $key => $val) {
        $signStr .= $key . "=" . $val . "&";
    }
    $signStr = rtrim($signStr, "&") . CLOUDINARY_API_SECRET;
    $signature = sha1($signStr);

    $url = "https://api.cloudinary.com/v1_1/" . CLOUDINARY_CLOUD_NAME . "/image/upload";

    $data = [
        'file' => new CURLFile($filePath),
        'api_key' => CLOUDINARY_API_KEY,
        'timestamp' => $timestamp,
        'signature' => $signature,
        'folder' => $folder
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        error_log("Error de cURL Cloudinary: " . $err);
        return false;
    }

    $result = json_decode($response, true);

    if (isset($result['secure_url'])) {
        return $result['secure_url'];
    }
    else {
        error_log("Error de Cloudinary API: " . ($result['error']['message'] ?? 'Error desconocido'));
        return false;
    }
}

/**
 * Elimina una imagen de Cloudinary
 * @param string $imageUrl URL completa de la imagen en Cloudinary
 * @return bool
 */
function eliminarImagenDeCloudinary($imageUrl)
{
    if (empty(CLOUDINARY_CLOUD_NAME) || empty(CLOUDINARY_API_KEY) || empty(CLOUDINARY_API_SECRET)) {
        return false;
    }

    $parts = explode('/upload/', $imageUrl);
    if (count($parts) < 2)
        return false;

    $pathPart = $parts[1];
    $pathPart = preg_replace('/^v\d+\//', '', $pathPart);

    $publicId = pathinfo($pathPart, PATHINFO_DIRNAME) !== '.'
        ? pathinfo($pathPart, PATHINFO_DIRNAME) . '/' . pathinfo($pathPart, PATHINFO_FILENAME)
        : pathinfo($pathPart, PATHINFO_FILENAME);

    $timestamp = time();
    $params = [
        'public_id' => $publicId,
        'timestamp' => $timestamp
    ];

    ksort($params);
    $signStr = "";
    foreach ($params as $key => $val) {
        $signStr .= $key . "=" . $val . "&";
    }
    $signStr = rtrim($signStr, "&") . CLOUDINARY_API_SECRET;
    $signature = sha1($signStr);

    $url = "https://api.cloudinary.com/v1_1/" . CLOUDINARY_CLOUD_NAME . "/image/destroy";

    $data = [
        'public_id' => $publicId,
        'api_key' => CLOUDINARY_API_KEY,
        'timestamp' => $timestamp,
        'signature' => $signature
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        error_log("Error de cURL Cloudinary (destroy): " . $err);
        return false;
    }

    $result = json_decode($response, true);
    return (isset($result['result']) && $result['result'] === 'ok');
}

/**
 * Función unificada para gestionar la subida de una imagen (Cloudinary con Fallback Local)
 * @param array $fileArray El array de $_FILES['nombre_campo']
 * @param string $folder Carpeta destino ('dinos', 'perfiles', etc)
 * @param string $localPath Ruta relativa para el fallback local (desde el archivo que llama)
 * @param string $prefix Prefijo para el nombre de archivo local (ej: 'dino_')
 * @return string|false Devuelve la URL de Cloudinary o el nombre del archivo local, o false si falla.
 */
function gestionarSubidaImagen($fileArray, $folder, $localPath, $prefix = 'img_')
{
    if (!isset($fileArray) || $fileArray['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $img_name = $fileArray['name'];
    $img_tmp = $fileArray['tmp_name'];
    $extension = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));
    $validas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    // 1. Validar extensión
    if (!in_array($extension, $validas)) {
        return false;
    }

    // 2. Validar que sea imagen real
    if (!@getimagesize($img_tmp)) {
        return false;
    }

    // 3. Intento de subida a Cloudinary
    $url_cloudinary = subirImagenACloudinary($img_tmp, $folder);
    if ($url_cloudinary) {
        return $url_cloudinary;
    }

    // 4. Fallback: Almacenamiento local
    $nuevo_nombre = uniqid($prefix) . '.' . $extension;
    $destino = rtrim($localPath, '/') . '/' . $nuevo_nombre;

    if (move_uploaded_file($img_tmp, $destino)) {
        return $nuevo_nombre;
    }

    return false;
}
?>

