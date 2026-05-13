# 📚 API CRUD de Estudiantes — PHP Nativo

API REST en **PHP puro** (sin frameworks) que usa `estudiantes.json` como base de datos.

---

## 🚀 Instalación y uso

### Opción A — Servidor built-in de PHP (desarrollo rápido)
```bash
php -S localhost:3000 router.php
```

### Opción B — Apache con mod_rewrite
1. Copia la carpeta en `htdocs/` o `www/`
2. Asegúrate de tener `mod_rewrite` activo
3. El `.htaccess` incluido redirige todo a `index.php`

> **Permisos:** el archivo `estudiantes.json` debe ser escribible por el servidor web.
> ```bash
> chmod 664 estudiantes.json
> ```

---

## 📋 Estructura del estudiante

| Campo      | Tipo    | Descripción                      |
|------------|---------|----------------------------------|
| `id`       | string  | UUID v4 generado automáticamente |
| `nombre`   | string  | Nombre del estudiante            |
| `apellido` | string  | Apellido del estudiante          |
| `edad`     | integer | Edad (entre 1 y 120)             |
| `cedula`   | string  | Cédula única del estudiante      |

---

## 🔗 Endpoints

### 1. `GET /api/estudiantes`
Lista todos los estudiantes.

**Query params opcionales:**
| Param | Ejemplo | Descripción |
|---|---|---|
| `nombre` | `?nombre=mar` | Filtro parcial, sin distinción mayúsculas |
| `apellido` | `?apellido=gonzalez` | Filtro parcial |
| `cedula` | `?cedula=1001234567` | Búsqueda exacta |

**Respuesta `200`:**
```json
{
  "total": 3,
  "estudiantes": [ ... ]
}
```

---

### 2. `GET /api/estudiantes/{id}`
Obtiene un estudiante por su UUID.

**Respuesta `200`:**
```json
{
  "id": "a1b2c3d4-...",
  "nombre": "María",
  "apellido": "González",
  "edad": 20,
  "cedula": "1001234567"
}
```
**Error `404`:** estudiante no encontrado.

---

### 3. `POST /api/estudiantes`
Crea un nuevo estudiante. Todos los campos son **obligatorios**.

**Body `application/json`:**
```json
{
  "nombre": "Luis",
  "apellido": "Pérez",
  "edad": 21,
  "cedula": "1002223334"
}
```

**Respuesta `201`:**
```json
{
  "mensaje": "Estudiante creado correctamente.",
  "estudiante": { "id": "uuid-nuevo", ... }
}
```

**Errores:** `400` campos inválidos · `409` cédula duplicada

---

### 4. `PUT /api/estudiantes/{id}`
**Reemplaza** todos los campos de un estudiante. Todos los campos son obligatorios.

**Body `application/json`:**
```json
{
  "nombre": "Luis",
  "apellido": "Pérez",
  "edad": 22,
  "cedula": "1002223334"
}
```

**Respuesta `200`:**
```json
{
  "mensaje": "Estudiante actualizado correctamente.",
  "estudiante": { ... }
}
```

**Errores:** `400` · `404` · `409`

---

### 5. `PATCH /api/estudiantes/{id}`
**Actualización parcial** — solo envía los campos que quieres modificar.

**Body `application/json`:**
```json
{ "edad": 23 }
```

**Respuesta `200`:**
```json
{
  "mensaje": "Estudiante actualizado parcialmente.",
  "estudiante": { ... }
}
```

---

### 6. `DELETE /api/estudiantes/{id}`
Elimina un estudiante.

**Respuesta `200`:**
```json
{
  "mensaje": "Estudiante eliminado correctamente.",
  "estudiante": { ... }
}
```
**Error `404`:** estudiante no encontrado.

---

## ⚙️ CORS
Las cabeceras CORS se configuran al inicio de `index.php`:

```php
header("Access-Control-Allow-Origin: *");           // permite todo
// Para producción, restringe el origen:
header("Access-Control-Allow-Origin: https://tu-frontend.com");
```

---

## 📁 Estructura del proyecto

```
student-api-php/
├── index.php          # Lógica principal (router + controladores)
├── router.php         # Router para el servidor built-in de PHP
├── estudiantes.json   # Base de datos JSON
├── .htaccess          # Reescritura de URLs para Apache
└── README.md
```