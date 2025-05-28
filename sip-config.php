<?php
session_start();
header('Content-Type: application/json');  // Asegura que la respuesta sea en formato JSON

// Definir el token esperado
$tokenEsperado = "S3curity2025";  // El token de autenticación

// Verificar si el parámetro 'token' está presente en la URL y si es válido
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
