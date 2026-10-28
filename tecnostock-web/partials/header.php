<?php $u = usuario_actual(); ?>
<header class="top">
  <a class="logo" href="index.php">
    <span class="logo-mark">TS</span>
    TecnoStock
  </a>
  <?php if ($u): ?>
    <div class="user-box">
      <span>👋 <strong><?= htmlspecialchars($u['nombre']) ?></strong></span>
      <a href="logout.php">cerrar sesión</a>
    </div>
  <?php endif; ?>
</header>
