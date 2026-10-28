<?php
require_once 'config.php';
require_once 'helpers.php';
requerir_login();

$active = 'movimientos';
$filtroProducto = isset($_GET['producto']) ? (int) $_GET['producto'] : 0;

$productos = $pdo->query('SELECT id_producto, codigo, nombre FROM productos ORDER BY nombre')->fetchAll();

$sql = "SELECT m.*, p.codigo, p.nombre AS producto_nombre, u.nombre AS usuario_nombre
        FROM movimientos m
        JOIN productos p ON p.id_producto = m.id_producto
        JOIN usuarios u ON u.id_usuario = m.id_usuario
        WHERE 1=1";
$params = [];
if ($filtroProducto > 0) {
    $sql .= ' AND m.id_producto = :prod';
    $params['prod'] = $filtroProducto;
}
$sql .= ' ORDER BY m.fecha_hora DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movimientos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Historial de movimientos · TecnoStock</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<?php include 'partials/header.php'; ?>
<?php include 'partials/nav.php'; ?>

<div class="wrap">
  <?php flash_mostrar(); ?>

  <div class="panel-head">
    <h1>Historial de movimientos</h1>
    <a class="btn" href="movimiento_nuevo.php">+ registrar movimiento</a>
  </div>

  <form class="search" method="get" style="max-width:340px;">
    <select name="producto" onchange="this.form.submit()" style="border:none; background:transparent; font-family:inherit; font-size:.9rem; flex:1;">
      <option value="0">todos los productos</option>
      <?php foreach ($productos as $p): ?>
        <option value="<?= $p['id_producto'] ?>" <?= $filtroProducto === (int)$p['id_producto'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($p['codigo']) ?> — <?= htmlspecialchars($p['nombre']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Producto</th>
          <th>Tipo</th>
          <th>Cantidad</th>
          <th>Observación</th>
          <th>Usuario</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($movimientos as $m): ?>
          <tr>
            <td><?= date('d-m-Y H:i', strtotime($m['fecha_hora'])) ?></td>
            <td><?= htmlspecialchars($m['codigo']) ?> — <?= htmlspecialchars($m['producto_nombre']) ?></td>
            <td>
              <span class="badge <?= $m['tipo'] === 'entrada' ? 'badge-entrada' : 'badge-salida' ?>">
                <?= $m['tipo'] === 'entrada' ? '⬆ entrada' : '⬇ salida' ?>
              </span>
            </td>
            <td><?= (int)$m['cantidad'] ?></td>
            <td><?= htmlspecialchars($m['observacion'] ?: '—') ?></td>
            <td><?= htmlspecialchars($m['usuario_nombre']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (count($movimientos) === 0): ?>
          <tr><td colspan="6" class="empty-row">todavía no hay movimientos registrados.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<footer class="foot">
  <span>TecnoStock · sistema de inventario</span>
</footer>

</body>
</html>
