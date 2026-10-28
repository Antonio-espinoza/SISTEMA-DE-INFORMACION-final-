<?php
require_once 'config.php';
require_once 'helpers.php';
requerir_login();

$active = 'movimiento';
$usuario = usuario_actual();
$productoPreseleccionado = isset($_GET['producto']) ? (int) $_GET['producto'] : 0;

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idProducto  = (int) ($_POST['id_producto'] ?? 0);
    $tipo        = ($_POST['tipo'] ?? '') === 'salida' ? 'salida' : 'entrada';
    $cantidad    = (int) ($_POST['cantidad'] ?? 0);
    $observacion = trim($_POST['observacion'] ?? '');

    if ($idProducto <= 0)   $errores[] = 'Elige un producto.';
    if ($cantidad <= 0)     $errores[] = 'La cantidad debe ser mayor a 0.';

    $producto = null;
    if (empty($errores)) {
        $stmt = $pdo->prepare('SELECT * FROM productos WHERE id_producto = :id AND estado = "activo"');
        $stmt->execute(['id' => $idProducto]);
        $producto = $stmt->fetch();
        if (!$producto) {
            $errores[] = 'Ese producto no existe o está inactivo.';
        } elseif ($tipo === 'salida' && $cantidad > $producto['stock_actual']) {
            // Una salida no puede dejar el stock bajo cero
            $errores[] = 'No hay stock suficiente (disponible: ' . $producto['stock_actual'] . ').';
        }
    }

    if (empty($errores)) {
        try {
            $pdo->beginTransaction();

            $ins = $pdo->prepare(
                'INSERT INTO movimientos (fecha_hora, tipo, cantidad, observacion, id_producto, id_usuario)
                 VALUES (NOW(), :tipo, :cant, :obs, :prod, :user)'
            );
            $ins->execute([
                'tipo' => $tipo, 'cant' => $cantidad, 'obs' => $observacion,
                'prod' => $idProducto, 'user' => $usuario['id_usuario'],
            ]);

            $delta = $tipo === 'entrada' ? $cantidad : -$cantidad;
            $upd = $pdo->prepare(
                'UPDATE productos SET stock_actual = stock_actual + :delta
                 WHERE id_producto = :id AND stock_actual + :delta2 >= 0'
            );
            $upd->execute(['delta' => $delta, 'delta2' => $delta, 'id' => $idProducto]);

            if ($upd->rowCount() === 0) {
                throw new Exception('El stock cambió justo antes de confirmar; intenta de nuevo.');
            }

            $pdo->commit();
            flash_set('ok', 'Movimiento registrado: ' . ($tipo === 'entrada' ? '+' : '−') . $cantidad . ' unidades de "' . $producto['nombre'] . '".');
            header('Location: movimientos.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errores[] = 'No se pudo registrar el movimiento: ' . $e->getMessage();
        }
    }
}

$productos = $pdo->query(
    "SELECT p.*, c.nombre AS categoria_nombre FROM productos p
     JOIN categorias c ON c.id_categoria = p.id_categoria
     WHERE p.estado = 'activo' ORDER BY p.nombre"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registrar movimiento · TecnoStock</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<?php include 'partials/header.php'; ?>
<?php include 'partials/nav.php'; ?>

<div class="wrap">
  <h1>Registrar movimiento</h1>
  <p class="subtitle">Una entrada suma stock; una salida lo resta y no puede dejarlo bajo cero.</p>

  <?php if (!empty($errores)): ?>
    <div class="flash flash-error">
      <?= implode('<br>', array_map('htmlspecialchars', $errores)) ?>
    </div>
  <?php endif; ?>

  <form class="form" method="post">
    <label>
      Producto
      <select name="id_producto" required id="selProducto">
        <option value="">-- selecciona --</option>
        <?php foreach ($productos as $p): ?>
          <option value="<?= $p['id_producto'] ?>"
            data-stock="<?= (int)$p['stock_actual'] ?>"
            <?= $productoPreseleccionado === (int)$p['id_producto'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($p['codigo']) ?> — <?= htmlspecialchars($p['nombre']) ?> (stock: <?= (int)$p['stock_actual'] ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <div class="form-row">
      <label>
        Tipo de movimiento
        <select name="tipo" required>
          <option value="entrada">Entrada (ingreso de stock)</option>
          <option value="salida">Salida (venta / retiro de stock)</option>
        </select>
      </label>
      <label>
        Cantidad
        <input type="number" step="1" min="1" name="cantidad" required>
      </label>
    </div>

    <label>
      Observación
      <input type="text" name="observacion" placeholder="ej: compra a proveedor, venta mostrador...">
    </label>

    <p class="subtitle" id="stockDisponible" style="margin:0;"></p>

    <div class="form-actions">
      <button type="submit" class="btn">registrar</button>
      <a class="btn-ghost" href="movimientos.php">cancelar</a>
    </div>
  </form>
</div>

<footer class="foot">
  <span>TecnoStock · sistema de inventario</span>
</footer>

<script>
// Validación inmediata en el cliente (se repite igual en PHP en el servidor)
const sel = document.getElementById('selProducto');
const info = document.getElementById('stockDisponible');
function actualizarInfo(){
  const opt = sel.options[sel.selectedIndex];
  if (opt && opt.dataset.stock !== undefined) {
    info.textContent = 'Stock disponible: ' + opt.dataset.stock + ' unidades.';
  } else {
    info.textContent = '';
  }
}
sel.addEventListener('change', actualizarInfo);
actualizarInfo();

document.querySelector('form').addEventListener('submit', function(e){
  const tipo = document.querySelector('[name=tipo]').value;
  const cantidad = parseInt(document.querySelector('[name=cantidad]').value || '0', 10);
  const opt = sel.options[sel.selectedIndex];
  const stock = opt ? parseInt(opt.dataset.stock || '0', 10) : 0;
  if (tipo === 'salida' && cantidad > stock) {
    e.preventDefault();
    alert('No hay stock suficiente. Disponible: ' + stock);
  }
});
</script>

</body>
</html>
