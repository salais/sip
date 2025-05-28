<?php
session_start();
header('Content-Type: application/json');  // Asegura que la respuesta sea en formato JSON

// Definir el token esperado
$tokenEsperado = "S3curity2025";  // El token de autenticaci�n

// Verificar si el par�metro 'token' est� presente en la URL y si es v�lido
if (!isset($_GET['token']) || $_GET['token'] !== $tokenEsperado) {
    http_response_code(403);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

// Si el token es correcto, devuelve los datos
echo json_encode([
    "usuario" => "3000",
    "clave" => "Admin01@"
]);
