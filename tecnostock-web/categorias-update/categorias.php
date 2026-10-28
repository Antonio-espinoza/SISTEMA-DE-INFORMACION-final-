<?php
require_once 'config.php';
require_once 'helpers.php';
requerir_login();

$active = 'categorias';
$errores = [];
$nombre = '';
$descripcion = '';

// --- Activar / desactivar categoría (no se borra físicamente) ---
if (isset($_GET['cambiar_estado'])) {
    $id = (int) $_GET['cambiar_estado'];
    $stmt = $pdo->prepare('SELECT estado FROM categorias WHERE id_categoria = :id');
    $stmt->execute(['id' => $id]);
    $actual = $stmt->fetchColumn();
    if ($actual !== false) {
        $nuevo = $actual === 'activo' ? 'inactivo' : 'activo';
        $pdo->prepare('UPDATE categorias SET estado = :e WHERE id_categoria = :id')
            ->execute(['e' => $nuevo, 'id' => $id]);
        flash_set('ok', $nuevo === 'inactivo' ? 'Categoría desactivada.' : 'Categoría reactivada.');
    }
    header('Location: categorias.php');
    exit;
}

// --- Crear categoría nueva ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre      = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if ($nombre === '')              $errores[] = 'El nombre es obligatorio.';
    if (mb_strlen($nombre) > 60)     $errores[] = 'El nombre no puede superar los 60 caracteres.';
    if (mb_strlen($descripcion) > 150) $errores[] = 'La descripción no puede superar los 150 caracteres.';

    // Evitar categorías repetidas (sin distinguir mayúsculas)
    if (empty($errores)) {
        $chk = $pdo->prepare('SELECT id_categoria FROM categorias WHERE LOWER(nombre) = LOWER(:n)');
        $chk->execute(['n' => $nombre]);
        if ($chk->fetch()) {
            $errores[] = 'Ya existe una categoría con ese nombre.';
        }
    }

    if (empty($errores)) {
        $ins = $pdo->prepare('INSERT INTO categorias (nombre, descripcion, estado) VALUES (:n, :d, "activo")');
        $ins->execute(['n' => $nombre, 'd' => $descripcion]);
        flash_set('ok', 'Categoría «' . $nombre . '» creada ✨');
        header('Location: categorias.php');
        exit;
    }
}

$categorias = $pdo->query(
    'SELECT c.*, COUNT(p.id_producto) AS total_productos
     FROM categorias c
     LEFT JOIN productos p ON p.id_categoria = c.id_categoria
     GROUP BY c.id_categoria
     ORDER BY c.estado DESC, c.nombre'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Categorías · TecnoStock</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<?php include 'partials/header.php'; ?>
<?php include 'partials/nav.php'; ?>

<div class="wrap">
  <?php flash_mostrar(); ?>
  <h1>Categorías</h1>
  <p class="subtitle">Crea nuevas categorías para organizar tus productos.</p>

  <?php if (!empty($errores)): ?>
    <div class="flash flash-error">
      <?= implode('<br>', array_map('htmlspecialchars', $errores)) ?>
    </div>
  <?php endif; ?>

  <form class="form" method="post" style="max-width:none;">
    <div class="form-row">
      <label style="flex:1">
        Nombre de la categoría
        <input type="text" name="nombre" maxlength="60" required
               placeholder="ej: Gaming, Redes, Smartwatches..."
               value="<?= htmlspecialchars($nombre) ?>">
      </label>
      <label style="flex:2">
        Descripción (opcional)
        <input type="text" name="descripcion" maxlength="150"
               placeholder="¿qué tipo de productos van aquí?"
               value="<?= htmlspecialchars($descripcion) ?>">
      </label>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn">+ crear categoría</button>
    </div>
  </form>

  <div class="table-wrap" style="margin-top:22px;">
    <table class="table">
      <thead>
        <tr>
          <th>Categoría</th>
          <th>Descripción</th>
          <th>Productos</th>
          <th>Estado</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($categorias as $c):
          $inactiva = $c['estado'] === 'inactivo';
        ?>
          <tr class="<?= $inactiva ? 'inactivo' : '' ?>">
            <td><?= iconoCategoria($c['nombre']) ?> <?= htmlspecialchars($c['nombre']) ?></td>
            <td><?= htmlspecialchars($c['descripcion'] ?: '—') ?></td>
            <td><?= (int)$c['total_productos'] ?></td>
            <td>
              <span class="badge <?= $inactiva ? 'badge-inactivo' : '' ?>">
                <?= $inactiva ? 'inactiva' : 'activa' ?>
              </span>
            </td>
            <td class="col-actions">
              <a class="btn-ghost <?= $inactiva ? '' : 'btn-warn' ?>"
                 href="categorias.php?cambiar_estado=<?= $c['id_categoria'] ?>"
                 onclick="return confirm('<?= $inactiva ? '¿Reactivar' : '¿Desactivar' ?> la categoría «<?= htmlspecialchars($c['nombre'], ENT_QUOTES) ?>»?');">
                <?= $inactiva ? 'reactivar' : 'desactivar' ?>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (count($categorias) === 0): ?>
          <tr><td colspan="5" class="empty-row">todavía no hay categorías.</td></tr>
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
