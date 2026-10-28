<?php
require_once 'config.php';
require_once 'helpers.php';
requerir_login();

$active = 'productos';

// --- Filtros actuales (se conservan al cambiar de estado un producto) ---
$busqueda = trim($_GET['q'] ?? '');
$catSel   = (int) ($_GET['categoria'] ?? 0);
$orden    = $_GET['orden'] ?? '';

// Opciones de orden permitidas (lista blanca: nunca se inserta texto del usuario en el SQL)
$ordenes = [
    'precio_desc' => ['label' => 'Precio: mayor a menor', 'sql' => 'p.precio DESC, p.nombre'],
    'precio_asc'  => ['label' => 'Precio: menor a mayor', 'sql' => 'p.precio ASC, p.nombre'],
    'stock_desc'  => ['label' => 'Stock: mayor a menor',  'sql' => 'p.stock_actual DESC, p.nombre'],
    'stock_asc'   => ['label' => 'Stock: menor a mayor',  'sql' => 'p.stock_actual ASC, p.nombre'],
];
if (!isset($ordenes[$orden])) $orden = '';

$filtros = array_filter(
    ['q' => $busqueda, 'categoria' => $catSel ?: '', 'orden' => $orden],
    fn($v) => $v !== '' && $v !== null
);
$qs = http_build_query($filtros);

// --- Activar / desactivar (soft delete: nunca se borra físicamente) ---
if (isset($_GET['cambiar_estado'])) {
    $id = (int) $_GET['cambiar_estado'];
    $stmt = $pdo->prepare('SELECT estado FROM productos WHERE id_producto = :id');
    $stmt->execute(['id' => $id]);
    $actual = $stmt->fetchColumn();
    if ($actual !== false) {
        $nuevo = $actual === 'activo' ? 'inactivo' : 'activo';
        $upd = $pdo->prepare('UPDATE productos SET estado = :e WHERE id_producto = :id');
        $upd->execute(['e' => $nuevo, 'id' => $id]);
        flash_set('ok', $nuevo === 'inactivo' ? 'Producto desactivado.' : 'Producto reactivado.');
    }
    header('Location: productos.php' . ($qs ? '?' . $qs : ''));
    exit;
}

// --- Datos para el desplegable de categorías ---
$categorias = $pdo->query('SELECT id_categoria, nombre FROM categorias ORDER BY nombre')->fetchAll();

// --- Consulta con filtros y orden ---
$sql = 'SELECT p.*, c.nombre AS categoria_nombre
        FROM productos p
        JOIN categorias c ON c.id_categoria = p.id_categoria
        WHERE 1=1';
$params = [];
if ($busqueda !== '') {
    $sql .= ' AND (p.nombre LIKE :q OR p.codigo LIKE :q OR c.nombre LIKE :q)';
    $params['q'] = '%' . $busqueda . '%';
}
if ($catSel > 0) {
    $sql .= ' AND p.id_categoria = :cat';
    $params['cat'] = $catSel;
}
// Sin orden elegido: activos primero, luego por categoría y nombre
$sql .= ' ORDER BY ' . ($orden !== '' ? $ordenes[$orden]['sql'] : 'p.estado ASC, c.nombre, p.nombre');

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Productos · TecnoStock</title>
<link rel="stylesheet" href="assets/style.css">
<style>
  .filtros{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    align-items:center;
    margin:16px 0;
  }
  .filtros .search{ margin:0; flex:1 1 280px; max-width:none; }
  .filtros select{
    font-family:inherit;
    font-size:.88rem;
    font-weight:600;
    color:#465372;
    background:var(--surface);
    border:1px solid var(--line);
    border-radius:var(--radius-sm);
    padding:11px 16px;
    cursor:pointer;
  }
  .filtros select:focus{ outline:none; border-color:var(--blue); }
  .filtros .limpiar{ font-size:.83rem; color:var(--blue); font-weight:600; text-decoration:none; }
  .resultado-info{ color:#5B6B85; font-size:.83rem; margin:0 0 8px; }
</style>
</head>
<body>

<?php include 'partials/header.php'; ?>
<?php include 'partials/nav.php'; ?>

<div class="wrap">
  <?php flash_mostrar(); ?>

  <div class="panel-head">
    <h1>Productos</h1>
    <a class="btn" href="producto_form.php">+ nuevo producto</a>
  </div>

  <form class="filtros" method="get">
    <div class="search">
      <input type="text" name="q" placeholder="busca por nombre, código o categoría..." value="<?= htmlspecialchars($busqueda) ?>">
      <button type="submit" aria-label="Buscar">🔍</button>
    </div>

    <select name="categoria" onchange="this.form.submit()" aria-label="Filtrar por categoría">
      <option value="0">🏷️ Todas las categorías</option>
      <?php foreach ($categorias as $c): ?>
        <option value="<?= $c['id_categoria'] ?>" <?= $catSel === (int)$c['id_categoria'] ? 'selected' : '' ?>>
          <?= iconoCategoria($c['nombre']) ?> <?= htmlspecialchars($c['nombre']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select name="orden" onchange="this.form.submit()" aria-label="Ordenar productos">
      <option value="">↕️ Ordenar por...</option>
      <?php foreach ($ordenes as $clave => $o): ?>
        <option value="<?= $clave ?>" <?= $orden === $clave ? 'selected' : '' ?>><?= $o['label'] ?></option>
      <?php endforeach; ?>
    </select>

    <?php if ($qs): ?>
      <a class="limpiar" href="productos.php">✕ limpiar filtros</a>
    <?php endif; ?>
  </form>

  <p class="resultado-info"><?= count($productos) ?> producto(s) encontrado(s)</p>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Código</th>
          <th>Producto</th>
          <th>Categoría</th>
          <th>Precio</th>
          <th>Stock</th>
          <th>Estado</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($productos as $p):
          $inactivo  = $p['estado'] === 'inactivo';
          $sinStock  = $p['stock_actual'] == 0;
          $bajoStock = $p['stock_actual'] <= $p['stock_minimo'];
        ?>
          <tr class="<?= $inactivo ? 'inactivo' : '' ?>">
            <td><?= htmlspecialchars($p['codigo']) ?></td>
            <td><?= iconoCategoria($p['categoria_nombre']) ?> <?= htmlspecialchars($p['nombre']) ?></td>
            <td><?= htmlspecialchars($p['categoria_nombre']) ?></td>
            <td><?= money($p['precio']) ?></td>
            <td>
              <span class="badge <?= $sinStock ? 'badge-out' : ($bajoStock ? 'badge-low' : '') ?>">
                <?= (int)$p['stock_actual'] ?>
              </span>
            </td>
            <td>
              <span class="badge <?= $inactivo ? 'badge-inactivo' : '' ?>">
                <?= $inactivo ? 'inactivo' : 'activo' ?>
              </span>
            </td>
            <td class="col-actions">
              <a class="btn-ghost" href="producto_form.php?id=<?= $p['id_producto'] ?>">editar</a>
              <a class="btn-ghost <?= $inactivo ? '' : 'btn-warn' ?>"
                 href="productos.php?cambiar_estado=<?= $p['id_producto'] ?><?= $qs ? '&' . htmlspecialchars($qs) : '' ?>"
                 onclick="return confirm('<?= $inactivo ? '¿Reactivar' : '¿Desactivar' ?> «<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>»?');">
                <?= $inactivo ? 'reactivar' : 'desactivar' ?>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (count($productos) === 0): ?>
          <tr><td colspan="7" class="empty-row">no encontramos productos con esos filtros.</td></tr>
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
