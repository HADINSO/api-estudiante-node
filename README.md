# 📚 API CRUD de Estudiantes

API REST construida con **Node.js + Express** que usa un archivo JSON como base de datos.

---

## 🚀 Instalación y uso

```bash
npm install
node index.js
```

El servidor corre en `http://localhost:3000`.

---

## 📋 Estructura del estudiante

| Campo      | Tipo    | Descripción                        |
|------------|---------|------------------------------------|
| `id`       | string  | UUID generado automáticamente      |
| `nombre`   | string  | Nombre del estudiante              |
| `apellido` | string  | Apellido del estudiante            |
| `edad`     | integer | Edad (entre 1 y 120)               |
| `cedula`   | string  | Cédula única del estudiante        |

---

## 🔗 Endpoints

### 1. `GET /api/estudiantes`
Lista todos los estudiantes.

**Query params opcionales:**
- `?nombre=María` — filtra por nombre (parcial, sin distinción de mayúsculas)
- `?apellido=González` — filtra por apellido
- `?cedula=1001234567` — filtra por cédula exacta

**Respuesta exitosa `200`:**
```json
{
  "total": 3,
  "estudiantes": [...]
}
```

---

### 2. `GET /api/estudiantes/:id`
Obtiene un estudiante por su ID.

**Respuesta exitosa `200`:**
```json
{
  "id": "uuid",
  "nombre": "María",
  "apellido": "González",
  "edad": 20,
  "cedula": "1001234567"
}
```

**Error `404`:** Estudiante no encontrado.

---

### 3. `POST /api/estudiantes`
Crea un nuevo estudiante. Todos los campos son obligatorios.

**Body `application/json`:**
```json
{
  "nombre": "Luis",
  "apellido": "Pérez",
  "edad": 21,
  "cedula": "1002223334"
}
```

**Respuesta exitosa `201`:**
```json
{
  "mensaje": "Estudiante creado correctamente.",
  "estudiante": { "id": "uuid", ... }
}
```

**Errores:**
- `400` — Campos inválidos o faltantes
- `409` — La cédula ya existe

---

### 4. `PUT /api/estudiantes/:id`
Reemplaza **todos** los campos de un estudiante. Todos los campos son obligatorios.

**Body `application/json`:**
```json
{
  "nombre": "Luis",
  "apellido": "Pérez",
  "edad": 22,
  "cedula": "1002223334"
}
```

**Respuesta exitosa `200`:**
```json
{
  "mensaje": "Estudiante actualizado correctamente.",
  "estudiante": { ... }
}
```

**Errores:**
- `400` — Campos inválidos
- `404` — No encontrado
- `409` — Cédula duplicada

---

### 5. `PATCH /api/estudiantes/:id`
Actualiza **parcialmente** un estudiante. Solo envía los campos a modificar.

**Body `application/json` (ejemplo parcial):**
```json
{
  "edad": 23
}
```

**Respuesta exitosa `200`:**
```json
{
  "mensaje": "Estudiante actualizado parcialmente.",
  "estudiante": { ... }
}
```

---

### 6. `DELETE /api/estudiantes/:id`
Elimina un estudiante por su ID.

**Respuesta exitosa `200`:**
```json
{
  "mensaje": "Estudiante eliminado correctamente.",
  "estudiante": { ... }
}
```

**Error `404`:** Estudiante no encontrado.

---

## ⚙️ CORS
La API acepta peticiones desde **cualquier origen** (`cors()` sin restricciones). Para producción, limitar orígenes en `index.js`:

```js
app.use(cors({ origin: "https://tu-frontend.com" }));
```

---

## 📁 Estructura del proyecto

```
student-api/
├── index.js           # Servidor y rutas
├── estudiantes.json   # Base de datos JSON
├── package.json
└── README.md
```