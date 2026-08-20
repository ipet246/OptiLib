<?php
include 'conexion.php';

// Manejador: Subir/Actualizar Foto individual desde la tabla
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'subir_foto_tabla') {
    $item_id = (int)$_POST['item_id'];
    if (isset($_FILES['nueva_imagen']) && $_FILES['nueva_imagen']['error'] === UPLOAD_ERR_OK) {
        $dir_subida = 'uploads/';
        if (!is_dir($dir_subida)) { mkdir($dir_subida, 0777, true); }
        $ext = pathinfo($_FILES['nueva_imagen']['name'], PATHINFO_EXTENSION);
        $nombre_imagen = uniqid('inst_') . '.' . $ext;
        if (move_uploaded_file($_FILES['nueva_imagen']['tmp_name'], $dir_subida . $nombre_imagen)) {
            $stmt = $pdo->prepare("UPDATE items SET imagen = ? WHERE id = ?");
            $stmt->execute([$nombre_imagen, $item_id]);
        }
    }
    header("Location: index.php");
    exit;
}

// Manejador: Simular Pedido a Proveedores
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'simular_pedido') {
    $proveedor = $_POST['proveedor'] ?? '';
    $material = $_POST['material_pedido'] ?? '';
    $cantidad = (int)($_POST['cantidad_pedido'] ?? 0);

    if (!empty($proveedor) && !empty($material) && $cantidad > 0) {
        $stmt = $pdo->prepare("INSERT INTO pedidos_proveedor (proveedor, material, cantidad, estado) VALUES (?, ?, ?, 'Simulado / Pendiente')");
        $stmt->execute([$proveedor, $material, $cantidad]);
    }
    header("Location: index.php");
    exit;
}

// Manejador: Registrar reporte de Mantenimiento / Falla
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'reportar_mantenimiento') {
    $item_id = (int)$_POST['item_id'];
    $descripcion_falla = trim($_POST['descripcion_falla']);
    $tecnico = trim($_POST['tecnico_cargo']);

    if ($item_id > 0 && !empty($descripcion_falla)) {
        $stmt_mant = $pdo->prepare("INSERT INTO mantenimientos (item_id, descripcion_falla, tecnico_cargo, estado_mantenimiento) VALUES (?, ?, ?, 'En Reparación')");
        $stmt_mant->execute([$item_id, $descripcion_falla, $tecnico]);

        $stmt_item = $pdo->prepare("UPDATE items SET estado = 'En Mantenimiento' WHERE id = ?");
        $stmt_item->execute([$item_id]);
    }
    header("Location: index.php");
    exit;
}

// Manejador: Finalizar Mantenimiento (Máquina Reparada)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'finalizar_mantenimiento') {
    $mant_id = (int)$_POST['mantenimiento_id'];
    $item_id = (int)$_POST['item_id'];

    if ($mant_id > 0 && $item_id > 0) {
        $stmt_mant = $pdo->prepare("UPDATE mantenimientos SET estado_mantenimiento = 'Reparado / Listo', fecha_solucion = NOW() WHERE id = ?");
        $stmt_mant->execute([$mant_id]);

        $stmt_item = $pdo->prepare("UPDATE items SET estado = 'Disponible' WHERE id = ?");
        $stmt_item->execute([$item_id]);
    }
    header("Location: index.php");
    exit;
}

// Manejador: Registro e Ingreso a Stock (Asigna foto a todas las máquinas creadas en la misma tanda)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'registrar') {
    $cat_id = (int)$_POST['categoria_id'];
    $nombre_base = trim($_POST['nombre']);
    $subtipo = trim($_POST['subtipo'] ?? '');
    $forma_diseno = trim($_POST['forma_diseno'] ?? '');
    $cantidad_nueva = (int)$_POST['cantidad'];
    $usuario = $_POST['usuario'] ?? '';
    $observaciones = $_POST['observaciones'] ?? '';

    $es_maquinaria = ($cat_id === 1);

    if (!$es_maquinaria) {
        $stmt_check = $pdo->prepare("
            SELECT id, cantidad FROM items 
            WHERE categoria_id = ? 
              AND (LOWER(nombre) = LOWER(?) OR LOWER(nombre) LIKE CONCAT('%', LOWER(?), '%'))
              AND (LOWER(subtipo) = LOWER(?) OR subtipo = 'N/A' OR ? = 'N/A')
            LIMIT 1
        ");
        $stmt_check->execute([$cat_id, $nombre_base, $subtipo, $subtipo, $subtipo]);
        $item_existente = $stmt_check->fetch();

        if ($item_existente) {
            $nueva_cantidad = $item_existente['cantidad'] + $cantidad_nueva;
            $stmt_update = $pdo->prepare("UPDATE items SET cantidad = ?, ultimo_usuario = ? WHERE id = ?");
            $stmt_update->execute([$nueva_cantidad, $usuario, $item_existente['id']]);
            header("Location: index.php");
            exit;
        }
    }

    // Procesar la imagen una sola vez para que aplique a todas las unidades
    $nombre_imagen = 'default.png';
    if (isset($_FILES['imagen_item']) && $_FILES['imagen_item']['error'] === UPLOAD_ERR_OK) {
        $dir_subida = 'uploads/';
        if (!is_dir($dir_subida)) { mkdir($dir_subida, 0777, true); }
        $ext = pathinfo($_FILES['imagen_item']['name'], PATHINFO_EXTENSION);
        $nombre_imagen = uniqid('inst_') . '.' . $ext;
        move_uploaded_file($_FILES['imagen_item']['tmp_name'], $dir_subida . $nombre_imagen);
    }

    $prefijos = [1 => 'INS', 2 => 'SEG', 3 => 'CRI', 4 => 'MRC', 5 => 'HER'];
    $prefijo = $prefijos[$cat_id] ?? 'GEN';

    $ciclos = $es_maquinaria ? $cantidad_nueva : 1;
    $cant_por_registro = $es_maquinaria ? 1 : $cantidad_nueva;

    for ($k = 0; $k < $ciclos; $k++) {
        $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING_INDEX(codigo_serial, '-', -1) AS UNSIGNED)) FROM items WHERE codigo_serial LIKE ?");
        $stmt->execute([$prefijo . '-%']);
        $max_num = $stmt->fetchColumn() ?: 0;
        $num_secuencial = $max_num + 1;
        $codigo_serial = sprintf("%s-%03d", $prefijo, $num_secuencial);

        $nombre_final = $es_maquinaria ? "Máquina " . $num_secuencial . " - " . $nombre_base : $nombre_base;

        $sql = "INSERT INTO items (codigo_serial, categoria_id, nombre, subtipo, forma_diseno, cantidad, estado, observaciones, ultimo_usuario, imagen) 
                VALUES (?, ?, ?, ?, ?, ?, 'Disponible', ?, ?, ?)";
        $pdo->prepare($sql)->execute([
            $codigo_serial, $cat_id, $nombre_final, $subtipo, $forma_diseno, $cant_por_registro, $observaciones, $usuario, $nombre_imagen
        ]);
    }

    header("Location: index.php");
    exit;
}

// Consultas
$items = $pdo->query("SELECT * FROM items ORDER BY id ASC")->fetchAll();
$categorias = $pdo->query("SELECT * FROM categorias ORDER BY id")->fetchAll();
$pedidos = $pdo->query("SELECT * FROM pedidos_proveedor ORDER BY fecha_pedido DESC")->fetchAll();

$sql_mant = "SELECT m.*, i.codigo_serial, i.nombre as nombre_equipo, i.subtipo 
             FROM mantenimientos m 
             JOIN items i ON m.item_id = i.id 
             ORDER BY m.fecha_reporte DESC";
$mantenimientos = $pdo->query($sql_mant)->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OptiLib - Gestión Integral</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .card-dash { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .img-preview { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid #dee2e6; cursor: pointer; }
        .btn-foto { padding: 2px 6px; font-size: 11px; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow">
    <div class="container">
        <a class="navbar-brand fw-bold text-info" href="#"><i class="bi bi-eye-fill me-2"></i>OptiLib - Control de Inventario y Taller</a>
    </div>
</nav>

<div class="container pb-5">

    <!-- Pestañas de Navegación -->
    <ul class="nav nav-pills mb-4" id="pills-tab" role="tablist">
        <li class="nav-item">
            <button class="nav-link active fw-bold" data-bs-toggle="pill" data-bs-target="#pills-inventory" type="button">
                <i class="bi bi-box-seam me-1"></i> Stock General
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-bold text-danger" data-bs-toggle="pill" data-bs-target="#pills-mantenimiento" type="button">
                <i class="bi bi-wrench-adjustable me-1"></i> Mantenimiento y Fallas
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-bold text-primary" data-bs-toggle="pill" data-bs-target="#pills-pedidos" type="button">
                <i class="bi bi-cart-check me-1"></i> Pedidos a Proveedores (Modo Demo)
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- 1. PESTAÑA INVENTARIO -->
        <div class="tab-pane fade show active" id="pills-inventory">
            
            <div class="card card-dash p-4 mb-4">
                <h5 class="fw-bold mb-3">Registrar Ítem o Máquina</h5>
                <form id="formRegistro" method="POST" enctype="multipart/form-data" class="row g-3">
                    <input type="hidden" name="accion" value="registrar">
                    
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Categoría</label>
                        <select name="categoria_id" id="selectCategoria" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Nombre del Elemento</label>
                        <select name="nombre" id="selectNombre" class="form-select" required>
                            <option value="">Seleccione categoría primero...</option>
                            <option value="Pupilómetro" data-cat="1">Pupilómetro</option>
                            <option value="Frontofocómetro" data-cat="1">Frontofocómetro</option>
                            <option value="Caja de Prueba" data-cat="1">Caja de Prueba</option>
                            <option value="Biseladora / Minibisel" data-cat="1">Biseladora / Minibisel</option>
                            <option value="Torno (perforador de lentes)" data-cat="1">Torno (perforador de lentes)</option>
                            <option value="Cristales" data-cat="3">Cristales</option>
                            <option value="Cristales Orgánicos" data-cat="3">Cristales Orgánicos</option>
                            <option value="Destornilladores" data-cat="5">Destornilladores</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Subtipo/Material</label>
                        <select name="subtipo" class="form-select">
                            <option value="N/A">N/A</option>
                            <option value="Maquinaria">Maquinaria</option>
                            <option value="Orgánico">Orgánico</option>
                            <option value="Policarbonato">Policarbonato</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Cantidad</label>
                        <input type="number" name="cantidad" value="1" min="1" class="form-control" required>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Responsable</label>
                        <select name="usuario" class="form-select" required>
                            <option value="Técnico Óptico">Técnico Óptico</option>
                            <option value="Encargado de Lab">Encargado de Lab</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Foto para la(s) Máquina(s)</label>
                        <input type="file" name="imagen_item" class="form-control" accept="image/*">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Observaciones</label>
                        <input type="text" name="observaciones" class="form-control" placeholder="Detalles o ubicación...">
                    </div>

                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-info text-white fw-bold">
                            <i class="bi bi-plus-circle me-1"></i> Cargar al Inventario
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tabla de Inventario General -->
            <div class="card card-dash p-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Código Serial</th>
                                <th>Foto</th>
                                <th>Identificación de Equipo / Ítem</th>
                                <th>Subtipo</th>
                                <th>Cantidad</th>
                                <th>Estado</th>
                                <th>Responsable</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $i): ?>
                            <tr>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($i['codigo_serial']) ?></span></td>
                                <td>
                                    <!-- Formulario para cargar/cambiar foto individual -->
                                    <form method="POST" enctype="multipart/form-data" class="d-flex flex-column align-items-start">
                                        <input type="hidden" name="accion" value="subir_foto_tabla">
                                        <input type="hidden" name="item_id" value="<?= $i['id'] ?>">
                                        
                                        <?php if (!empty($i['imagen']) && $i['imagen'] !== 'default.png' && file_exists('uploads/' . $i['imagen'])): ?>
                                            <img src="uploads/<?= htmlspecialchars($i['imagen']) ?>" class="img-preview mb-1" onclick="this.nextElementSibling.click()">
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark border mb-1" style="cursor:pointer;" onclick="this.nextElementSibling.click()">Sin Foto</span>
                                        <?php endif; ?>
                                        
                                        <input type="file" name="nueva_imagen" accept="image/*" class="d-none" onchange="this.form.submit()">
                                        <button type="button" class="btn btn-link btn-foto p-0 text-decoration-none" onclick="this.previousElementSibling.click()">
                                            <small><i class="bi bi-camera"></i> Subir/Cambiar</small>
                                        </button>
                                    </form>
                                </td>
                                <td class="fw-bold text-primary"><?= htmlspecialchars($i['nombre']) ?></td>
                                <td><small class="text-muted"><?= htmlspecialchars($i['subtipo']) ?></small></td>
                                <td><span class="badge bg-light text-dark border"><?= $i['cantidad'] ?></span></td>
                                <td>
                                    <?php if ($i['estado'] === 'Disponible'): ?>
                                        <span class="badge bg-success">Disponible</span>
                                    <?php elseif ($i['estado'] === 'En Mantenimiento'): ?>
                                        <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i> En Mantenimiento</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark"><?= $i['estado'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($i['ultimo_usuario'] ?? '-') ?></td>
                                <td class="text-center">
                                    <?php if ($i['estado'] === 'Disponible'): ?>
                                        <button class="btn btn-sm btn-outline-danger fw-bold" onclick="abrirModalReporte(<?= $i['id'] ?>, '<?= htmlspecialchars($i['codigo_serial']) ?> - <?= htmlspecialchars($i['nombre'], ENT_QUOTES) ?>')">
                                            <i class="bi bi-wrench me-1"></i> Reportar Falla
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted small">En Mantenimiento</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 2. PESTAÑA MANTENIMIENTO -->
        <div class="tab-pane fade" id="pills-mantenimiento">
            <div class="card card-dash p-4">
                <h5 class="fw-bold text-danger mb-3"><i class="bi bi-tools me-2"></i>Historial y Diagnóstico de Máquinas en Mantenimiento</h5>
                
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th>Máquina Afectada</th>
                                <th>Problema / Falla Especificada</th>
                                <th>Técnico a Cargo</th>
                                <th>Fecha Reporte</th>
                                <th>Estado</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mantenimientos as $m): ?>
                            <tr>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($m['codigo_serial']) ?></span></td>
                                <td class="fw-bold text-danger"><?= htmlspecialchars($m['nombre_equipo']) ?></td>
                                <td class="text-wrap" style="max-width: 320px;">
                                    <div class="p-2 bg-light rounded text-dark border">
                                        <i class="bi bi-exclamation-circle-fill text-danger me-1"></i> 
                                        <?= nl2br(htmlspecialchars($m['descripcion_falla'])) ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($m['tecnico_cargo'] ?: 'No asignado') ?></td>
                                <td><small><?= htmlspecialchars($m['fecha_reporte']) ?></small></td>
                                <td>
                                    <?php if ($m['estado_mantenimiento'] === 'Reparado / Listo'): ?>
                                        <span class="badge bg-success">Reparado / Listo</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark"><?= $m['estado_mantenimiento'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($m['estado_mantenimiento'] !== 'Reparado / Listo'): ?>
                                        <form method="POST" onsubmit="return confirm('¿Confirmar que la máquina fue reparada?');">
                                            <input type="hidden" name="accion" value="finalizar_mantenimiento">
                                            <input type="hidden" name="mantenimiento_id" value="<?= $m['id'] ?>">
                                            <input type="hidden" name="item_id" value="<?= $m['item_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-success fw-bold">
                                                <i class="bi bi-check-circle me-1"></i> Dar de Alta
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <small class="text-muted">Resuelto: <?= htmlspecialchars($m['fecha_solucion']) ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($mantenimientos)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No hay máquinas registradas en mantenimiento actualmente.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 3. PESTAÑA PEDIDOS PROVEEDORES (MODO DEMO) -->
        <div class="tab-pane fade" id="pills-pedidos">
            <div class="row g-4">
                <div class="col-md-5">
                    <div class="card card-dash p-4">
                        <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-box-seam me-2"></i>Simulador de Pedidos</h5>
                        <form method="POST">
                            <input type="hidden" name="accion" value="simular_pedido">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Proveedor Ficticio</label>
                                <select name="proveedor" class="form-select" required>
                                    <option value="Óptica Express S.A.">Óptica Express S.A.</option>
                                    <option value="Distribuidora de Lentes Global">Distribuidora de Lentes Global</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Material / Insumo Requerido</label>
                                <select name="material_pedido" class="form-select" required>
                                    <option value="Lote Cristales Orgánicos AR">Lote Cristales Orgánicos AR</option>
                                    <option value="Lote Cristales Policarbonato">Lote Cristales Policarbonato</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Cantidad Unidades</label>
                                <input type="number" name="cantidad_pedido" value="10" min="1" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 fw-bold">Generar Orden Simulada</button>
                        </form>
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="card card-dash p-3">
                        <h6 class="fw-bold mb-3">Historial de Pedidos Generados</h6>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Proveedor</th>
                                        <th>Material</th>
                                        <th>Cant.</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pedidos as $p): ?>
                                    <tr>
                                        <td><small><?= htmlspecialchars($p['fecha_pedido']) ?></small></td>
                                        <td class="fw-semibold"><?= htmlspecialchars($p['proveedor']) ?></td>
                                        <td><?= htmlspecialchars($p['material']) ?></td>
                                        <td><?= htmlspecialchars($p['cantidad']) ?></td>
                                        <td><span class="badge bg-info text-dark"><?= htmlspecialchars($p['estado']) ?></span></td>
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
</div>

<!-- Modal para Reportar Falla -->
<div class="modal fade" id="modalReportarFalla" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="accion" value="reportar_mantenimiento">
                <input type="hidden" name="item_id" id="reporte_item_id">

                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Reportar Falla de Máquina</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Máquina seleccionada: <strong id="reporte_equipo_nombre" class="text-danger"></strong></p>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Técnico que reporta</label>
                        <input type="text" name="tecnico_cargo" class="form-control" placeholder="Ej: Juan Pérez" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Descripción del Problema o Falla</label>
                        <textarea name="descripcion_falla" class="form-control" rows="4" placeholder="Ej: Ruido extraño en el motor de corte al biselar..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger fw-bold">Enviar a Mantenimiento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let reporteModal;
document.addEventListener("DOMContentLoaded", function() {
    reporteModal = new bootstrap.Modal(document.getElementById('modalReportarFalla'));
});
function abrirModalReporte(id, nombreCompleto) {
    document.getElementById('reporte_item_id').value = id;
    document.getElementById('reporte_equipo_nombre').innerText = nombreCompleto;
    reporteModal.show();
}
</script>
</body>
</html>