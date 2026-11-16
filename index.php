<?php
// Archivo: C:\laragon\www\crud-ibero\index.php
require_once "conexion.php";

$mensaje = "";
$error = "";

// -------------------------------------------------------------------------
// 1. PROCESAR ACCIONES POST (CREAR, ACTUALIZAR O ELIMINAR)
// -------------------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $accion = $_POST["accion"] ?? "";

  // A) ELIMINAR UN PRODUCTO (DELETE)
  if ($accion === "eliminar") {
    $idEliminar = intval($_POST["id"] ?? 0);
    if ($idEliminar > 0) {
      $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
      $stmt->execute([$idEliminar]);
      header("Location: index.php?ok=eliminado");
      exit;
    }
  }

  // B) CREAR O ACTUALIZAR UN PRODUCTO (INSERT / UPDATE)
  if ($accion === "guardar") {
    $id = intval($_POST["id"] ?? 0);
    $nombre = trim($_POST["nombre"] ?? "");
    $categoria = trim($_POST["categoria"] ?? "");
    $precio = $_POST["precio"] ?? "";
    $stock = $_POST["stock"] ?? "";

    if ($nombre === "" || $categoria === "" || $precio === "" || $stock === "") {
      $error = "Todos los campos son obligatorios.";
    } elseif (!is_numeric($precio) || floatval($precio) < 0 || !is_numeric($stock) || intval($stock) < 0) {
      $error = "El precio y el stock deben ser números mayores o iguales a 0.";
    } elseif(strlen($nombre)<3) {
      $error="El nombre del producto debe tener al menos 3 letras";
    } elseif(floatval($precio)<1000) {
      $error="El precio del producto debe ser al menos de 1000 pesos";
    } else  {
      if ($id > 0) {
        // UPDATE: Actualizar producto existente
        $sql = "UPDATE productos SET nombre = ?, categoria = ?, precio = ?, stock = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $categoria, floatval($precio), intval($stock), $id]);
        header("Location: index.php?ok=actualizado");
        exit;
      } else {
        // INSERT: Crear producto nuevo
        $sql = "INSERT INTO productos (nombre, categoria, precio, stock) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $categoria, floatval($precio), intval($stock)]);
        header("Location: index.php?ok=creado");
        exit;
      }
    }
  }
}

// -------------------------------------------------------------------------
// 2. SI PULSARON "EDITAR", BUSCAR ESE PRODUCTO PARA LLENAR EL FORMULARIO
// -------------------------------------------------------------------------
$productoEditar = [
  "id" => 0,
  "nombre" => "",
  "categoria" => "Accesorios",
  "precio" => "",
  "stock" => 0
];

if (isset($_GET["editar"])) {
  $idEditar = intval($_GET["editar"]);
  $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
  $stmt->execute([$idEditar]);
  $encontrado = $stmt->fetch();
  if ($encontrado) {
    $productoEditar = $encontrado;
  }
}

// -------------------------------------------------------------------------
// 3. LEER TODOS LOS PRODUCTOS (SELECT) + BUSCADOR OPCIONAL POR GET
// -------------------------------------------------------------------------
$busqueda = trim($_GET["q"] ?? "");
if ($busqueda !== "") {
  $stmt = $pdo->prepare("SELECT * FROM productos WHERE nombre LIKE ? OR categoria LIKE ? ORDER BY id DESC");
  $termino = "%" . $busqueda . "%";
  $stmt->execute([$termino, $termino]);
} else {
  $stmt = $pdo->query("SELECT * FROM productos ORDER BY id DESC");
}
$productos = $stmt->fetchAll();

// Calcular totales del inventario
$totalUnidades = 0;
$valorInventario = 0;
foreach ($productos as $p) {
  $totalUnidades += $p["stock"];
  $valorInventario += ($p["precio"] * $p["stock"]);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CRUD Inventario IberoTech — PHP & MySQL</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #f4f6f9; }
    .kpi-card { border: 1px solid #e3e8ee; transition: transform .15s ease; }
    .kpi-card:hover { transform: translateY(-2px); }
    .kpi-card small { color: #6c757d; font-size: .78rem; text-transform: uppercase; letter-spacing: .5px; }
    .kpi-card .valor { font-size: 1.5rem; font-weight: 700; }
    .form-sticky { position: sticky; top: 16px; }
    @media (max-width: 991.98px) { .form-sticky { position: static; } }
    .tabla-productos td, .tabla-productos th { vertical-align: middle; }
    .acciones-cell { white-space: nowrap; }
  </style>
</head>
<body>
<div class="container py-4">

  <!-- Header -->
  <header class="bg-primary bg-gradient text-white rounded-3 p-4 mb-4 shadow-sm">
    <div class="d-flex align-items-center gap-3">
      <i class="bi bi-box-seam fs-1"></i>
      <div>
        <h1 class="h4 mb-0">Inventario IberoTech</h1>
        <small class="opacity-75">Aplicación CRUD con PHP + MySQL</small>
      </div>
    </div>
  </header>

  <!-- Alertas -->
  <?php if (isset($_GET["ok"])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle-fill me-2"></i>
      Operación realizada con éxito: registro <strong><?= htmlspecialchars($_GET["ok"]) ?></strong>.
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if ($error !== ""): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>
      <?= htmlspecialchars($error) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
      <div class="card kpi-card shadow-sm h-100">
        <div class="card-body">
          <small><i class="bi bi-list-check me-1"></i>Productos listados</small>
          <div class="valor"><?= count($productos) ?></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card kpi-card shadow-sm h-100">
        <div class="card-body">
          <small><i class="bi bi-boxes me-1"></i>Unidades en stock</small>
          <div class="valor"><?= $totalUnidades ?> <span class="fs-6 fw-normal text-muted">uds</span></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card kpi-card shadow-sm h-100">
        <div class="card-body">
          <small><i class="bi bi-cash-stack me-1"></i>Valor del inventario</small>
          <div class="valor text-success">$<?= number_format($valorInventario, 0, ',', '.') ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">

    <!-- Formulario Crear / Editar -->
    <div class="col-12 col-lg-4">
      <div class="card shadow-sm form-sticky">
        <div class="card-header bg-white border-bottom-0 pt-3">
          <h2 class="h5 mb-0">
            <?php if ($productoEditar["id"] > 0): ?>
              <i class="bi bi-pencil-square text-primary me-1"></i>Editar producto #<?= $productoEditar["id"] ?>
            <?php else: ?>
              <i class="bi bi-plus-circle text-primary me-1"></i>Nuevo producto
            <?php endif; ?>
          </h2>
        </div>
        <div class="card-body">
          <form method="POST" action="index.php" novalidate>
            <input type="hidden" name="accion" value="guardar">
            <input type="hidden" name="id" value="<?= intval($productoEditar["id"]) ?>">

            <div class="mb-3">
              <label class="form-label fw-semibold">Nombre del producto</label>
              <input type="text" name="nombre" class="form-control" required
                     minlength="3"
                     value="<?= htmlspecialchars($productoEditar["nombre"]) ?>"
                     placeholder="Ej: Teclado mecánico">
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Categoría</label>
              <select name="categoria" class="form-select">
                <?php
                  $categorias = ["Computadores", "Monitores", "Accesorios", "Almacenamiento", "Audio", "Redes"];
                  foreach ($categorias as $cat):
                    $sel = ($productoEditar["categoria"] === $cat) ? "selected" : "";
                ?>
                  <option value="<?= $cat ?>" <?= $sel ?>><?= $cat ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Precio unitario ($ COP)</label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="number" name="precio" class="form-control" min="1000" step="100" required
                       value="<?= htmlspecialchars($productoEditar["precio"]) ?>">
              </div>
              <div class="form-text">Mínimo $1.000 COP</div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Stock (unidades)</label>
              <input type="number" name="stock" class="form-control" min="0" step="1" required
                     value="<?= htmlspecialchars($productoEditar["stock"]) ?>">
            </div>

            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i>
                <?= $productoEditar["id"] > 0 ? "Guardar cambios" : "Agregar al inventario" ?>
              </button>
              <?php if ($productoEditar["id"] > 0): ?>
                <a href="index.php" class="btn btn-outline-secondary">
                  <i class="bi bi-x-circle me-1"></i>Cancelar edición
                </a>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Tabla de productos -->
    <div class="col-12 col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body">

          <!-- Buscador -->
          <form method="GET" action="index.php" class="row g-2 mb-3">
            <div class="col-12 col-sm">
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="q" class="form-control"
                       placeholder="Buscar por nombre o categoría..."
                       value="<?= htmlspecialchars($busqueda) ?>">
              </div>
            </div>
            <div class="col-12 col-sm-auto d-grid d-sm-flex gap-2">
              <button type="submit" class="btn btn-dark">
                <i class="bi bi-search me-1"></i>Buscar
              </button>
              <?php if ($busqueda !== ""): ?>
                <a href="index.php" class="btn btn-outline-secondary">
                  <i class="bi bi-eraser me-1"></i>Limpiar
                </a>
              <?php endif; ?>
            </div>
          </form>

          <!-- Tabla responsive -->
          <div class="table-responsive">
            <table class="table table-hover tabla-productos align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>ID</th>
                  <th>Producto</th>
                  <th>Categoría</th>
                  <th class="text-end">Precio</th>
                  <th class="text-center">Stock</th>
                  <th class="text-end">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($productos) === 0): ?>
                  <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                      <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                      No se encontraron productos.
                    </td>
                  </tr>
                <?php endif; ?>
                <?php foreach ($productos as $p): ?>
                  <tr>
                    <td class="text-muted">#<?= $p["id"] ?></td>
                    <td><strong><?= htmlspecialchars($p["nombre"]) ?></strong></td>
                    <td><span class="badge text-bg-light border"><?= htmlspecialchars($p["categoria"]) ?></span></td>
                    <td class="text-end">$<?= number_format($p["precio"], 0, ',', '.') ?></td>
                    <td class="text-center">
                      <?php if ($p["stock"] > 0): ?>
                        <span class="badge text-bg-success"><?= $p["stock"] ?> disp.</span>
                      <?php else: ?>
                        <span class="badge text-bg-danger">Agotado</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-end acciones-cell">
                      <a href="index.php?editar=<?= $p["id"] ?>"
                         class="btn btn-sm btn-outline-primary" title="Editar">
                        <i class="bi bi-pencil"></i>
                        <span class="d-none d-md-inline ms-1">Editar</span>
                      </a>
                      <form method="POST" action="index.php" class="d-inline"
                            onsubmit="return confirm('¿Seguro que deseas eliminar este producto?');">
                        <input type="hidden" name="accion" value="eliminar">
                        <input type="hidden" name="id" value="<?= $p["id"] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Borrar">
                          <i class="bi bi-trash"></i>
                          <span class="d-none d-md-inline ms-1">Borrar</span>
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

        </div>
      </div>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>