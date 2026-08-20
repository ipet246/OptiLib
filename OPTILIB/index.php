<?php
include 'conexion.php';

// Procesamiento de Formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    
    // A) Registro de Ítem con Foto
    if ($_POST['accion'] === 'registrar') {
        $cat_id = $_POST['categoria_id'];
        $prefijos = [1 => 'INS', 2 => 'SEG', 3 => 'CRI', 4 => 'MRC', 5 => 'HER'];
        $prefijo = $prefijos[$cat_id] ?? 'GEN';

        // Autoincremento de Serial
        $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING_INDEX(codigo_serial, '-', -1) AS UNSIGNED)) FROM items WHERE codigo_serial LIKE ?");
        $stmt->execute([$prefijo . '-%']);
        $max_num = $stmt->fetchColumn() ?: 0;
        $codigo_serial = sprintf("%s-%03d", $prefijo, $max_num + 1);

        // Subida de Foto
        $nombre_imagen = 'default.png';
        if (isset($_FILES['imagen_item']) && $_FILES['imagen_item']['error'] === UPLOAD_ERR_OK) {
            $dir_subida = 'uploads/';
            if (!is_dir($dir_subida)) {
                mkdir($dir_subida, 0777, true);
            }
            $ext = pathinfo($_FILES['imagen_item']['name'], PATHINFO_EXTENSION);
            $nombre_imagen = uniqid('inst_') . '.' . $ext;
            move_uploaded_file($_FILES['imagen_item']['tmp_name'], $dir_subida . $nombre_imagen);
        }

        $sql = "INSERT INTO items (codigo_serial, categoria_id, nombre, subtipo, forma_diseno, cantidad, estado, observaciones, ultimo_usuario, imagen) 
                VALUES (?, ?, ?, ?, ?, ?, 'Disponible', ?, ?, ?)";
        $pdo->prepare($sql)->execute([
            $codigo_serial, $cat_id, $_POST['nombre'], $_POST['subtipo'], 
            $_POST['forma_diseno'], $_POST['cantidad'], $_POST['observaciones'], $_POST['usuario'], $nombre_imagen
        ]);
    } 
    // B) Simulación de Pedido a Proveedor
    elseif ($_POST['accion'] === 'simular_pedido') {
        $sql = "INSERT INTO pedidos_proveedor (proveedor, material, cantidad) VALUES (?, ?, ?)";
        $pdo->prepare($sql)->execute([$_POST['proveedor'], $_POST['material_pedido'], $_POST['cantidad_pedido']]);
    }
    // C) Actualizar / Subir Foto directamente desde la Tabla
    elseif ($_POST['accion'] === 'subir_foto_tabla') {
        $item_id = $_POST['item_id'];
        if (isset($_FILES['nueva_imagen']) && $_FILES['nueva_imagen']['error'] === UPLOAD_ERR_OK) {
            $dir_subida = 'uploads/';
            if (!is_dir($dir_subida)) {
                mkdir($dir_subida, 0777, true);
            }
            $ext = pathinfo($_FILES['nueva_imagen']['name'], PATHINFO_EXTENSION);
            $nombre_imagen = uniqid('inst_') . '.' . $ext;
            
            if (move_uploaded_file($_FILES['nueva_imagen']['tmp_name'], $dir_subida . $nombre_imagen)) {
                $sql = "UPDATE items SET imagen = ? WHERE id = ?";
                $pdo->prepare($sql)->execute([$nombre_imagen, $item_id]);
            }
        }
    }

    header("Location: index.php");
    exit;
}

$items = $pdo->query("SELECT i.*, c.nombre as cat_nombre FROM items i JOIN categorias c ON i.categoria_id = c.id ORDER BY i.id DESC")->fetchAll();
$categorias = $pdo->query("SELECT * FROM categorias")->fetchAll();
$pedidos = $pdo->query("SELECT * FROM pedidos_proveedor ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OptiLib - Exposición Escolar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .card-dash { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .img-preview { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid #dee2e6; cursor: pointer; }
        @media print {
            .no-print, .btn, .nav, nav { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow no-print">
    <div class="container">
        <a class="navbar-brand fw-bold text-info" href="#"><i class="bi bi-eye-fill me-2"></i>OptiLib - Proyecto Escolar</a>
    </div>
</nav>

<div class="container pb-5">

    <!-- Navegación por Pestañas -->
    <ul class="nav nav-pills mb-4 no-print" id="pills-tab" role="tablist">
        <li class="nav-item">
            <button class="nav-link active fw-bold" data-bs-toggle="pill" data-bs-target="#pills-inventory" type="button">
                <i class="bi bi-box-seam me-1"></i> Gestión de Inventario
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-bold" data-bs-toggle="pill" data-bs-target="#pills-pedidos" type="button">
                <i class="bi bi-cart-check me-1"></i> Pedidos a Proveedores (Modo Demo)
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- 1. PESTAÑA INVENTARIO -->
        <div class="tab-pane fade show active" id="pills-inventory">
            
            <!-- Formulario de Registro -->
            <div class="card card-dash p-4 mb-4 no-print">
                <h5 class="fw-bold mb-3">Registrar Ítem de Óptica / Laboratorio</h5>
                
                <form id="formRegistro" method="POST" enctype="multipart/form-data" class="row g-3">
                    <input type="hidden" name="accion" value="registrar">
                    
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Categoría</label>
                        <select name="categoria_id" class="form-select" required>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= $cat['nombre'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Nombre del Elemento</label>
                        <select name="nombre" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <option value="Cristales">Cristales</option>
                            <option value="Monturas / Marcos">Monturas / Marcos</option>
                            <option value="Pupilómetro">Pupilómetro</option>
                            <option value="Frontofocómetro">Frontofocómetro</option>
                            <option value="Caja de Prueba">Caja de Prueba</option>
                            <option value="Biseladora">Biseladora</option>
                            <option value="Mini-bisel">Mini-bisel</option>
                            <option value="Antiparras de Seguridad">Antiparras de Seguridad</option>
                            <option value="Chaqueta / Cofia">Chaqueta / Cofia</option>
                            <option value="Destornilladores">Destornilladores</option>
                            <option value="Torno (perforador de lentes)">Torno (perforador de lentes)</option>
                            <option value="Pinza de desbaste">Pinza de desbaste</option>
                            <option value="Pinza gira cristal">Pinza gira cristal</option>
                            <option value="Caloventor">Caloventor</option>
                            <option value="Limas">Limas</option>
                            <option value="Cartel de optotipo">Cartel de optotipo</option>
                            <option value="Soldadora embutidora">Soldadora embutidora</option>
                            <option value="Máquina Diamantada (a cinta)">Máquina Diamantada (a cinta)</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Subtipo/Material</label>
                        <select name="subtipo" class="form-select">
                            <option value="N/A">N/A</option>
                            <option value="Orgánico">Orgánico</option>
                            <option value="Policarbonato">Policarbonato</option>
                            <option value="Descartable">Descartable</option>
                            <option value="Metal">Metal</option>
                            <option value="Plástico">Plástico</option>
                            <option value="Set de precisión">Set de precisión</option>
                            <option value="Maquinaria">Maquinaria</option>
                            <option value="Eléctrico">Eléctrico</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Forma/Diseño</label>
                        <select name="forma_diseno" class="form-select">
                            <option value="N/A">N/A</option>
                            <option value="Montura Completa">Montura Completa</option>
                            <option value="Montura al aire">Montura al aire</option>
                            <option value="Media Montura">Media Montura</option>
                            <option value="Redondo">Redondo</option>
                            <option value="Cuadrado">Cuadrado</option>
                            <option value="Ovalado">Ovalado</option>
                            <option value="Rectangular">Rectangular</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Cantidad</label>
                        <input type="number" name="cantidad" value="1" min="1" class="form-control" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Responsable</label>
                        <select name="usuario" class="form-select" required>
                            <option value="">Seleccione usuario...</option>
                            <option value="Técnico Óptico">Técnico Óptico</option>
                            <option value="Encargado de Lab">Encargado de Lab</option>
                            <option value="Administración">Administración</option>
                        </select>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Foto del Instrumento</label>
                        <input type="file" name="imagen_item" class="form-control" accept="image/*">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Observaciones</label>
                        <input type="text" name="observaciones" class="form-control" placeholder="Opcional...">
                    </div>

                    <div class="col-12 text-end">
                        <button type="button" class="btn btn-info text-white fw-bold" onclick="confirmarCarga()">
                            <i class="bi bi-plus-circle me-1"></i> Ingresar al Stock
                        </button>
                    </div>
                </form>
            </div>

            <!-- Buscador y Botón Imprimir -->
            <div class="row mb-3 align-items-center no-print">
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" id="inputBuscador" class="form-control" placeholder="Buscar instrumento por nombre o código en tiempo real..." onkeyup="filtrarTabla()">
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-outline-dark fw-bold" onclick="window.print()">
                        <i class="bi bi-printer-fill me-1"></i> Imprimir Reporte
                    </button>
                </div>
            </div>

            <!-- Tabla de Inventario -->
            <div class="card card-dash p-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tablaStock">
                        <thead class="table-dark">
                            <tr>
                                <th>Código</th>
                                <th>Foto</th>
                                <th>Nombre</th>
                                <th>Detalle</th>
                                <th>Cant.</th>
                                <th>Estado</th>
                                <th>Responsable</th>
                                <th class="text-center no-print">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $i): ?>
                            <tr>
                                <td><span class="badge bg-secondary"><?= $i['codigo_serial'] ?></span></td>
                                <td>
                                    <?php if (!empty($i['imagen']) && file_exists('uploads/' . $i['imagen'])): ?>
                                        <img src="uploads/<?= $i['imagen'] ?>" alt="Foto" class="img-preview" onclick="verFotoGrande('uploads/<?= $i['imagen'] ?>', '<?= htmlspecialchars($i['nombre'], ENT_QUOTES) ?>')">
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border"><i class="bi bi-image text-muted"></i> Sin Foto</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold"><?= $i['nombre'] ?></td>
                                <td><small class="text-muted"><?= $i['subtipo'] ?> / <?= $i['forma_diseno'] ?></small></td>
                                <td>
                                    <!-- Alerta visual si queda 1 sola unidad -->
                                    <?php if ($i['cantidad'] <= 1): ?>
                                        <span class="badge bg-warning text-dark fw-bold" title="Stock bajo"><?= $i['cantidad'] ?> (Poco Stock)</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border"><?= $i['cantidad'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php $badge_cls = $i['estado'] === 'Disponible' ? 'bg-success' : 'bg-danger'; ?>
                                    <span class="badge <?= $badge_cls ?>"><?= $i['estado'] ?></span>
                                </td>
                                <td><?= $i['ultimo_usuario'] ?? '-' ?></td>
                                <td class="text-center no-print">
                                    <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="abrirModalSubirFoto(<?= $i['id'] ?>, '<?= htmlspecialchars($i['nombre'], ENT_QUOTES) ?>')">
                                        <i class="bi bi-upload me-1"></i> Subir Foto
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 2. PESTAÑA PEDIDOS PROVEEDORES -->
        <div class="tab-pane fade" id="pills-pedidos">
            <div class="row g-4">
                <div class="col-md-5">
                    <div class="card card-dash p-4">
                        <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-box-seam me-2"></i>Simulador de Pedidos</h5>
                        <div class="alert alert-info py-2 small" role="alert">
                            <i class="bi bi-info-circle me-1"></i> <strong>Modo Exposición:</strong> Permite simular el pedido de insumos a un proveedor sin realizar envíos reales.
                        </div>
                        <form method="POST">
                            <input type="hidden" name="accion" value="simular_pedido">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Proveedor Ficticio</label>
                                <select name="proveedor" class="form-select" required>
                                    <option value="Óptica Express S.A.">Óptica Express S.A.</option>
                                    <option value="Distribuidora de Lentes Global">Distribuidora de Lentes Global</option>
                                    <option value="Insumos Ópticos del Norte">Insumos Ópticos del Norte</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Material / Insumo Requerido</label>
                                <select name="material_pedido" class="form-select" required>
                                    <option value="Lote Cristales Orgánicos AR">Lote Cristales Orgánicos AR</option>
                                    <option value="Lote Cristales Policarbonato">Lote Cristales Policarbonato</option>
                                    <option value="Kits Monturas de Metal">Kits Monturas de Metal</option>
                                    <option value="Líquido Limpiador e Insumos">Líquido Limpiador e Insumos</option>
                                    <option value="Set de Herramientas de Taller">Set de Herramientas de Taller</option>
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
                                        <td><small><?= $p['fecha_pedido'] ?></small></td>
                                        <td class="fw-semibold"><?= $p['proveedor'] ?></td>
                                        <td><?= $p['material'] ?></td>
                                        <td><?= $p['cantidad'] ?></td>
                                        <td><span class="badge bg-info text-dark"><?= $p['estado'] ?></span></td>
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

<!-- Modal Confirmar Ingreso a Stock -->
<div class="modal fade" id="modalConfirmacion" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirmar Ingreso a Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                ¿Está seguro de que desea ingresar este ítem con los datos seleccionados?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar / Revisar</button>
                <button type="button" class="btn btn-primary" onclick="ejecutarIngreso()">Confirmar e Ingresar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Subir Foto Directa -->
<div class="modal fade" id="modalSubirFoto" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="accion" value="subir_foto_tabla">
                <input type="hidden" name="item_id" id="modal_item_id">
                
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-camera-fill me-2"></i>Subir Foto de Ítem</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Cargar o actualizar la imagen para: <strong id="modal_item_nombre"></strong></p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Seleccionar Imagen</label>
                        <input type="file" name="nueva_imagen" class="form-control" accept="image/*" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-bold">Guardar Imagen</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ver Foto Ampliada (Para la Exposición) -->
<div class="modal fade" id="modalVerFoto" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="verFotoTitulo">Visualizador de Imagen</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center bg-black">
                <img id="verFotoImg" src="" class="img-fluid rounded" style="max-height: 500px;" alt="Vista ampliada">
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let confirmModal, subirFotoModal, verFotoModal;

document.addEventListener("DOMContentLoaded", function() {
    confirmModal = new bootstrap.Modal(document.getElementById('modalConfirmacion'));
    subirFotoModal = new bootstrap.Modal(document.getElementById('modalSubirFoto'));
    verFotoModal = new bootstrap.Modal(document.getElementById('modalVerFoto'));
});

function confirmarCarga() {
    const form = document.getElementById('formRegistro');
    if (form.checkValidity()) {
        confirmModal.show();
    } else {
        form.reportValidity();
    }
}

function ejecutarIngreso() {
    document.getElementById('formRegistro').submit();
}

function abrirModalSubirFoto(id, nombre) {
    document.getElementById('modal_item_id').value = id;
    document.getElementById('modal_item_nombre').innerText = nombre;
    subirFotoModal.show();
}

function verFotoGrande(src, nombre) {
    document.getElementById('verFotoImg').src = src;
    document.getElementById('verFotoTitulo').innerText = nombre;
    verFotoModal.show();
}

// Filtro de tabla en tiempo real
function filtrarTabla() {
    let input = document.getElementById("inputBuscador").value.toLowerCase();
    let rows = document.querySelectorAll("#tablaStock tbody tr");

    rows.forEach(row => {
        let textoFila = row.innerText.toLowerCase();
        row.style.display = textoFila.includes(input) ? "" : "none";
    });
}
</script>
</body>
</html>