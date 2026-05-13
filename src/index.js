import express from "express";
import cors from "cors";
import fs from "fs";
import path from "path";
import { v4 as uuidv4 } from "uuid";
import { fileURLToPath } from "url";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = process.env.PORT || 3000;
const DB_PATH = path.join(__dirname, "db.json");

// ─── Middlewares ───────────────────────────────────────────────────────────────
app.use(cors()); // Permite todas las origenes
app.use(express.json());

// ─── Helpers ──────────────────────────────────────────────────────────────────
function leerDB() {
  const raw = fs.readFileSync(DB_PATH, "utf-8");
  return JSON.parse(raw);
}

function guardarDB(data) {
  fs.writeFileSync(DB_PATH, JSON.stringify(data, null, 2), "utf-8");
}

function validarEstudiante(body, esCreacion = true) {
  const errores = [];

  if (esCreacion || body.nombre !== undefined) {
    if (
      !body.nombre ||
      typeof body.nombre !== "string" ||
      body.nombre.trim() === ""
    )
      errores.push("El campo 'nombre' es obligatorio y debe ser texto.");
  }

  if (esCreacion || body.apellido !== undefined) {
    if (
      !body.apellido ||
      typeof body.apellido !== "string" ||
      body.apellido.trim() === ""
    )
      errores.push("El campo 'apellido' es obligatorio y debe ser texto.");
  }

  if (esCreacion || body.edad !== undefined) {
    const edad = Number(body.edad);
    if (isNaN(edad) || !Number.isInteger(edad) || edad < 1 || edad > 120)
      errores.push("El campo 'edad' debe ser un número entero entre 1 y 120.");
  }

  if (esCreacion || body.cedula !== undefined) {
    if (
      !body.cedula ||
      typeof body.cedula !== "string" ||
      body.cedula.trim() === ""
    )
      errores.push("El campo 'cedula' es obligatorio y debe ser texto.");
  }

  return errores;
}

// ─── Rutas ────────────────────────────────────────────────────────────────────

/**
 * GET /api/estudiantes
 * Lista todos los estudiantes.
 * Query params opcionales: ?nombre=&apellido=&cedula=
 */
app.get("/api/estudiantes", (req, res) => {
  const db = leerDB();
  let lista = db.estudiantes;

  const { nombre, apellido, cedula } = req.query;
  if (nombre)
    lista = lista.filter((e) =>
      e.nombre.toLowerCase().includes(nombre.toLowerCase()),
    );
  if (apellido)
    lista = lista.filter((e) =>
      e.apellido.toLowerCase().includes(apellido.toLowerCase()),
    );
  if (cedula) lista = lista.filter((e) => e.cedula === cedula);

  res.json({ total: lista.length, estudiantes: lista });
});

/**
 * GET /api/estudiantes/:id
 * Obtiene un estudiante por su ID.
 */
app.get("/api/estudiantes/:id", (req, res) => {
  const db = leerDB();
  const estudiante = db.estudiantes.find((e) => e.id === req.params.id);

  if (!estudiante)
    return res.status(404).json({ error: "Estudiante no encontrado." });

  res.json(estudiante);
});

/**
 * POST /api/estudiantes
 * Crea un nuevo estudiante.
 * Body: { nombre, apellido, edad, cedula }
 */
app.post("/api/estudiantes", (req, res) => {
  const errores = validarEstudiante(req.body, true);
  if (errores.length) return res.status(400).json({ errores });

  const db = leerDB();

  const cedulaExiste = db.estudiantes.find(
    (e) => e.cedula === req.body.cedula.trim(),
  );
  if (cedulaExiste)
    return res
      .status(409)
      .json({ error: "Ya existe un estudiante con esa cédula." });

  const nuevo = {
    id: uuidv4(),
    nombre: req.body.nombre.trim(),
    apellido: req.body.apellido.trim(),
    edad: Number(req.body.edad),
    cedula: req.body.cedula.trim(),
  };

  db.estudiantes.push(nuevo);
  guardarDB(db);

  res
    .status(201)
    .json({ mensaje: "Estudiante creado correctamente.", estudiante: nuevo });
});

/**
 * PUT /api/estudiantes/:id
 * Reemplaza completamente un estudiante por su ID.
 * Body: { nombre, apellido, edad, cedula }
 */
app.put("/api/estudiantes/:id", (req, res) => {
  const db = leerDB();
  const index = db.estudiantes.findIndex((e) => e.id === req.params.id);

  if (index === -1)
    return res.status(404).json({ error: "Estudiante no encontrado." });

  const errores = validarEstudiante(req.body, true);
  if (errores.length) return res.status(400).json({ errores });

  const cedulaDuplicada = db.estudiantes.find(
    (e) => e.cedula === req.body.cedula.trim() && e.id !== req.params.id,
  );
  if (cedulaDuplicada)
    return res
      .status(409)
      .json({ error: "Ya existe otro estudiante con esa cédula." });

  db.estudiantes[index] = {
    id: req.params.id,
    nombre: req.body.nombre.trim(),
    apellido: req.body.apellido.trim(),
    edad: Number(req.body.edad),
    cedula: req.body.cedula.trim(),
  };

  guardarDB(db);
  res.json({
    mensaje: "Estudiante actualizado correctamente.",
    estudiante: db.estudiantes[index],
  });
});

/**
 * PATCH /api/estudiantes/:id
 * Actualiza parcialmente un estudiante.
 * Body: uno o más campos { nombre?, apellido?, edad?, cedula? }
 */
app.patch("/api/estudiantes/:id", (req, res) => {
  const db = leerDB();
  const index = db.estudiantes.findIndex((e) => e.id === req.params.id);

  if (index === -1)
    return res.status(404).json({ error: "Estudiante no encontrado." });

  const errores = validarEstudiante(req.body, false);
  if (errores.length) return res.status(400).json({ errores });

  if (req.body.cedula) {
    const cedulaDuplicada = db.estudiantes.find(
      (e) => e.cedula === req.body.cedula.trim() && e.id !== req.params.id,
    );
    if (cedulaDuplicada)
      return res
        .status(409)
        .json({ error: "Ya existe otro estudiante con esa cédula." });
  }

  const camposPermitidos = ["nombre", "apellido", "edad", "cedula"];
  camposPermitidos.forEach((campo) => {
    if (req.body[campo] !== undefined) {
      db.estudiantes[index][campo] =
        campo === "edad"
          ? Number(req.body[campo])
          : String(req.body[campo]).trim();
    }
  });

  guardarDB(db);
  res.json({
    mensaje: "Estudiante actualizado parcialmente.",
    estudiante: db.estudiantes[index],
  });
});

/**
 * DELETE /api/estudiantes/:id
 * Elimina un estudiante por su ID.
 */
app.delete("/api/estudiantes/:id", (req, res) => {
  const db = leerDB();
  const index = db.estudiantes.findIndex((e) => e.id === req.params.id);

  if (index === -1)
    return res.status(404).json({ error: "Estudiante no encontrado." });

  const eliminado = db.estudiantes.splice(index, 1)[0];
  guardarDB(db);

  res.json({
    mensaje: "Estudiante eliminado correctamente.",
    estudiante: eliminado,
  });
});

// ─── 404 genérico ─────────────────────────────────────────────────────────────
app.use((req, res) => {
  res.status(404).json({ error: "Ruta no encontrada." });
});

// ─── Inicio del servidor ──────────────────────────────────────────────────────
app.listen(PORT, () => {
  console.log(`✅  API de Estudiantes corriendo en http://localhost:${PORT}`);
});

export default app;
