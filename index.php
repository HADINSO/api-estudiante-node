<?php

// ══════════════════════════════════════════════════════════════
//  API CRUD de Estudiantes — PHP Nativo
//  Base de datos: estudiantes.json
// ══════════════════════════════════════════════════════════════

// ─── CORS ──────────────────────────────────────────────────────
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Responder preflight OPTIONS directamente
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

// ─── Constantes ────────────────────────────────────────────────
define("DB_PATH", __DIR__ . "/estudiantes.json");

// ─── Helpers: JSON DB ──────────────────────────────────────────
function leerDB(): array {
    if (!file_exists(DB_PATH)) {
        file_put_contents(DB_PATH, json_encode(["estudiantes" => []], JSON_PRETTY_PRINT));
    }
    $contenido = file_get_contents(DB_PATH);
    return json_decode($contenido, true);
}

function guardarDB(array $data): void {
    file_put_contents(DB_PATH, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// ─── Helpers: Respuestas HTTP ──────────────────────────────────
function responder(int $codigo, array $datos): void {
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// ─── Helper: Generar UUID v4 ───────────────────────────────────
function generarUUID(): string {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf("%s%s-%s-%s-%s-%s%s%s", str_split(bin2hex($data), 4));
}

// ─── Helper: Validar campos ────────────────────────────────────
function validarCampos(array $body, bool $esCreacion): array {
    $errores = [];

    if ($esCreacion || array_key_exists("nombre", $body)) {
        if (empty(trim($body["nombre"] ?? "")))
            $errores[] = "El campo 'nombre' es obligatorio y debe ser texto.";
    }

    if ($esCreacion || array_key_exists("apellido", $body)) {
        if (empty(trim($body["apellido"] ?? "")))
            $errores[] = "El campo 'apellido' es obligatorio y debe ser texto.";
    }

    if ($esCreacion || array_key_exists("edad", $body)) {
        $edad = $body["edad"] ?? null;
        if (!is_numeric($edad) || (int)$edad < 1 || (int)$edad > 120)
            $errores[] = "El campo 'edad' debe ser un número entero entre 1 y 120.";
    }

    if ($esCreacion || array_key_exists("cedula", $body)) {
        if (empty(trim($body["cedula"] ?? "")))
            $errores[] = "El campo 'cedula' es obligatorio y debe ser texto.";
    }

    return $errores;
}

// ─── Router ────────────────────────────────────────────────────
$metodo  = $_SERVER["REQUEST_METHOD"];
$uri     = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$uri     = rtrim($uri, "/");

// Quitar base path si se sirve en subdirectorio (ej: /api)
// Ajusta esta línea si tu proyecto no está en la raíz del servidor:
$uri = preg_replace("#^/api#", "", $uri);

// Extraer segmentos: /estudiantes  o  /estudiantes/{id}
$partes = array_values(array_filter(explode("/", $uri)));
$recurso = $partes[0] ?? "";
$id      = $partes[1] ?? null;

if ($recurso !== "estudiantes") {
    responder(404, ["error" => "Ruta no encontrada. Usa /api/estudiantes"]);
}

// Leer body JSON para POST / PUT / PATCH
$body = [];
if (in_array($metodo, ["POST", "PUT", "PATCH"])) {
    $rawInput = file_get_contents("php://input");
    $body = json_decode($rawInput, true) ?? [];
}

// ══════════════════════════════════════════════════════════════
//  GET /api/estudiantes          → listar todos (con filtros)
//  GET /api/estudiantes/{id}     → obtener uno
// ══════════════════════════════════════════════════════════════
if ($metodo === "GET") {

    $db = leerDB();

    // GET /api/estudiantes/{id}
    if ($id !== null) {
        $encontrado = null;
        foreach ($db["estudiantes"] as $e) {
            if ($e["id"] === $id) { $encontrado = $e; break; }
        }
        if ($encontrado === null)
            responder(404, ["error" => "Estudiante no encontrado."]);

        responder(200, $encontrado);
    }

    // GET /api/estudiantes  (con filtros opcionales por query string)
    $lista = $db["estudiantes"];

    if (!empty($_GET["nombre"])) {
        $filtro = strtolower($_GET["nombre"]);
        $lista  = array_filter($lista, fn($e) => str_contains(strtolower($e["nombre"]), $filtro));
    }
    if (!empty($_GET["apellido"])) {
        $filtro = strtolower($_GET["apellido"]);
        $lista  = array_filter($lista, fn($e) => str_contains(strtolower($e["apellido"]), $filtro));
    }
    if (!empty($_GET["cedula"])) {
        $filtro = $_GET["cedula"];
        $lista  = array_filter($lista, fn($e) => $e["cedula"] === $filtro);
    }

    $lista = array_values($lista);
    responder(200, ["total" => count($lista), "estudiantes" => $lista]);
}

// ══════════════════════════════════════════════════════════════
//  POST /api/estudiantes   → crear estudiante
// ══════════════════════════════════════════════════════════════
if ($metodo === "POST") {

    if ($id !== null)
        responder(405, ["error" => "Método no permitido en esta ruta."]);

    $errores = validarCampos($body, true);
    if (!empty($errores))
        responder(400, ["errores" => $errores]);

    $db = leerDB();

    // Verificar cédula única
    foreach ($db["estudiantes"] as $e) {
        if ($e["cedula"] === trim($body["cedula"]))
            responder(409, ["error" => "Ya existe un estudiante con esa cédula."]);
    }

    $nuevo = [
        "id"       => generarUUID(),
        "nombre"   => trim($body["nombre"]),
        "apellido" => trim($body["apellido"]),
        "edad"     => (int)$body["edad"],
        "cedula"   => trim($body["cedula"]),
    ];

    $db["estudiantes"][] = $nuevo;
    guardarDB($db);

    responder(201, ["mensaje" => "Estudiante creado correctamente.", "estudiante" => $nuevo]);
}

// ══════════════════════════════════════════════════════════════
//  PUT /api/estudiantes/{id}   → reemplazar completamente
// ══════════════════════════════════════════════════════════════
if ($metodo === "PUT") {

    if ($id === null)
        responder(400, ["error" => "Debes indicar el ID del estudiante en la URL."]);

    $errores = validarCampos($body, true);
    if (!empty($errores))
        responder(400, ["errores" => $errores]);

    $db    = leerDB();
    $index = null;

    foreach ($db["estudiantes"] as $i => $e) {
        if ($e["id"] === $id) { $index = $i; break; }
    }

    if ($index === null)
        responder(404, ["error" => "Estudiante no encontrado."]);

    // Verificar cédula duplicada (ignorar el propio registro)
    foreach ($db["estudiantes"] as $e) {
        if ($e["cedula"] === trim($body["cedula"]) && $e["id"] !== $id)
            responder(409, ["error" => "Ya existe otro estudiante con esa cédula."]);
    }

    $db["estudiantes"][$index] = [
        "id"       => $id,
        "nombre"   => trim($body["nombre"]),
        "apellido" => trim($body["apellido"]),
        "edad"     => (int)$body["edad"],
        "cedula"   => trim($body["cedula"]),
    ];

    guardarDB($db);
    responder(200, [
        "mensaje"     => "Estudiante actualizado correctamente.",
        "estudiante"  => $db["estudiantes"][$index],
    ]);
}

// ══════════════════════════════════════════════════════════════
//  PATCH /api/estudiantes/{id}   → actualización parcial
// ══════════════════════════════════════════════════════════════
if ($metodo === "PATCH") {

    if ($id === null)
        responder(400, ["error" => "Debes indicar el ID del estudiante en la URL."]);

    $errores = validarCampos($body, false);
    if (!empty($errores))
        responder(400, ["errores" => $errores]);

    $db    = leerDB();
    $index = null;

    foreach ($db["estudiantes"] as $i => $e) {
        if ($e["id"] === $id) { $index = $i; break; }
    }

    if ($index === null)
        responder(404, ["error" => "Estudiante no encontrado."]);

    // Verificar cédula duplicada si se está cambiando
    if (array_key_exists("cedula", $body)) {
        foreach ($db["estudiantes"] as $e) {
            if ($e["cedula"] === trim($body["cedula"]) && $e["id"] !== $id)
                responder(409, ["error" => "Ya existe otro estudiante con esa cédula."]);
        }
    }

    $camposPermitidos = ["nombre", "apellido", "edad", "cedula"];
    foreach ($camposPermitidos as $campo) {
        if (array_key_exists($campo, $body)) {
            $db["estudiantes"][$index][$campo] =
                $campo === "edad" ? (int)$body[$campo] : trim((string)$body[$campo]);
        }
    }

    guardarDB($db);
    responder(200, [
        "mensaje"    => "Estudiante actualizado parcialmente.",
        "estudiante" => $db["estudiantes"][$index],
    ]);
}

// ══════════════════════════════════════════════════════════════
//  DELETE /api/estudiantes/{id}   → eliminar
// ══════════════════════════════════════════════════════════════
if ($metodo === "DELETE") {

    if ($id === null)
        responder(400, ["error" => "Debes indicar el ID del estudiante en la URL."]);

    $db    = leerDB();
    $index = null;

    foreach ($db["estudiantes"] as $i => $e) {
        if ($e["id"] === $id) { $index = $i; break; }
    }

    if ($index === null)
        responder(404, ["error" => "Estudiante no encontrado."]);

    $eliminado = $db["estudiantes"][$index];
    array_splice($db["estudiantes"], $index, 1);
    guardarDB($db);

    responder(200, [
        "mensaje"    => "Estudiante eliminado correctamente.",
        "estudiante" => $eliminado,
    ]);
}

// ─── Método no soportado ───────────────────────────────────────
responder(405, ["error" => "Método HTTP no soportado."]);