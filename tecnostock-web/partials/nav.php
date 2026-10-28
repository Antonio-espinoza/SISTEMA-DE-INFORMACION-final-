<?php
$active = $active ?? '';
?>
<nav class="admin-nav">
  <a href="index.php" class="<?= $active === 'dashboard' ? 'is-active' : '' ?>">📊 Panel</a>
  <a href="productos.php" class="<?= $active === 'productos' ? 'is-active' : '' ?>">📦 Productos</a>
  <a href="movimiento_nuevo.php" class="<?= $active === 'movimiento' ? 'is-active' : '' ?>">🔁 Registrar movimiento</a>
  <a href="movimientos.php" class="<?= $active === 'movimientos' ? 'is-active' : '' ?>">📜 Historial</a>
</nav>
