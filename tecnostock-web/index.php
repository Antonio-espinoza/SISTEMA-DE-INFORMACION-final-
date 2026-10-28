<?php
require_once 'config.php';
require_once 'helpers.php';
requerir_login();

$active = 'dashboard';

$totalActivos = $pdo->query("SELECT COUNT(*) FROM productos WHERE estado='activo'")->fetchColumn();
$bajoStockRows = $pdo->query(
    "SELECT p.*, c.nombre AS categoria_nombre FROM productos p
     JOIN categorias c ON c.id_categoria = p.id_categoria
     WHERE p.estado='activo' AND p.stock_actual <= p.stock_minimo
     ORDER BY p.stock_actual ASC"
)->fetchAll();
$movimientosHoy = $pdo->query("SELECT COUNT(*) FROM movimientos WHERE DATE(fecha_hora) = CURDATE()")->fetchColumn();
$valorInventario = $pdo->query("SELECT COALESCE(SUM(precio * stock_actual),0) FROM productos WHERE estado='activo'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel · TecnoStock</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<?php include 'partials/header.php'; ?>
<?php include 'partials/nav.php'; ?>

<div class="wrap">
  <?php flash_mostrar(); ?>
  <h1>Panel de inventario</h1>
  <p class="subtitle">Resumen general de TecnoStock, en vivo desde la base de datos.</p>

  <div class="stats">
    <div class="stat-card">
      <div class="num"><?= (int)$totalActivos ?></div>
      <div class="label">productos activos</div>
    </div>
    <div class="stat-card">
      <div class="num"><?= count($bajoStockRows) ?></div>
      <div class="label">con stock bajo el mínimo</div>
    </div>
    <div class="stat-card">
      <div class="num"><?= (int)$movimientosHoy ?></div>
      <div class="label">movimientos hoy</div>
    </div>
    <div class="stat-card">
      <div class="num"><?= money($valorInventario) ?></div>
      <div class="label">valor total del inventario</div>
    </div>
  </div>

  <?php if (count($bajoStockRows) > 0): ?>
    <div class="stock-alert">
      <strong>⚠️ Productos que necesitan reposición</strong>
      Estos productos activos están en su stock mínimo o por debajo.
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr><th>Código</th><th>Producto</th><th>Categoría</th><th>Stock actual</th><th>Stock mínimo</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($bajoStockRows as $p): ?>
            <tr>
              <td><?= htmlspecialchars($p['codigo']) ?></td>
              <td><?= iconoCategoria($p['categoria_nombre']) ?> <?= htmlspecialchars($p['nombre']) ?></td>
              <td><?= htmlspecialchars($p['categoria_nombre']) ?></td>
              <td>
                <span class="badge <?= $p['stock_actual'] == 0 ? 'badge-out' : 'badge-low' ?>">
                  <?= (int)$p['stock_actual'] ?>
                </span>
              </td>
              <td><?= (int)$p['stock_minimo'] ?></td>
              <td class="col-actions">
                <a class="btn-ghost" href="movimiento_nuevo.php?producto=<?= $p['id_producto'] ?>">registrar entrada</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="flash flash-ok" style="margin-top:18px;">✅ Todo el inventario activo está por sobre su stock mínimo.</div>
  <?php endif; ?>
</div>

<footer class="foot">
  <span>TecnoStock · sistema de inventario</span>
</footer>

</body>
</html>
