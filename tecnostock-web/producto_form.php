<?php
require_once 'config.php';
require_once 'helpers.php';
requerir_login();

// Valor sugerido de stock mínimo para un producto nuevo (se puede cambiar, pero nunca puede ser 0)
const STOCK_MINIMO_DEFECTO = 1;

$active = 'productos';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$esEdicion = $id > 0;

$categorias = $pdo->query('SELECT * FROM categorias WHERE estado = "activo" ORDER BY nombre')->fetchAll();

$producto = [
    'codigo' => '', 'nombre' => '', 'descripcion' => '', 'id_categoria' => $categorias[0]['id_categoria'] ?? 0,
    'precio' => '', 'stock_actual' => '', 'stock_minimo' => STOCK_MINIMO_DEFECTO,
];
$errores = [];
$esNueva = false;        // ¿eligió "crear nueva categoría"?
$nuevaCategoria = '';

if ($esEdicion) {
    $stmt = $pdo->prepare('SELECT * FROM productos WHERE id_producto = :id');
    $stmt->execute(['id' => $id]);
    $enc = $stmt->fetch();
    if (!$enc) {
        flash_set('error', 'Ese producto no existe.');
        header('Location: productos.php');
        exit;
    }
    $producto = $enc;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoriaPost  = $_POST['id_categoria'] ?? '';
    $esNueva        = $categoriaPost === 'nueva';
    $nuevaCategoria = trim($_POST['nueva_categoria'] ?? '');

    $producto['codigo']       = trim($_POST['codigo'] ?? '');
    $producto['nombre']       = trim($_POST['nombre'] ?? '');
    $producto['descripcion']  = trim($_POST['descripcion'] ?? '');
    $producto['id_categoria'] = $esNueva ? 0 : (int) $categoriaPost;
    $producto['precio']       = (float) ($_POST['precio'] ?? 0);

    $producto['stock_minimo'] = (int) ($_POST['stock_minimo'] ?? 0);
    if (!$esEdicion) {
        // El stock actual solo se define al crear; luego cambia con movimientos
        $producto['stock_actual'] = (int) ($_POST['stock_actual'] ?? -1);
    }

    // --- Reglas de negocio ---
    if ($producto['codigo'] === '')        $errores[] = 'El código es obligatorio.';
    if ($producto['nombre'] === '')        $errores[] = 'El nombre es obligatorio.';
    if ($esNueva) {
        if ($nuevaCategoria === '')            $errores[] = 'Escribe el nombre de la nueva categoría.';
        if (mb_strlen($nuevaCategoria) > 60)   $errores[] = 'El nombre de la categoría no puede superar los 60 caracteres.';
    } elseif ($producto['id_categoria'] <= 0) {
        $errores[] = 'Elige una categoría.';
    }

    // Precio: no se admite 0 (ni negativos ni decimales que se redondeen a 0)
    if ($producto['precio'] < 1)           $errores[] = 'El precio debe ser mayor a 0 (mínimo $1).';

    // Stock mínimo: editable, pero nunca 0 (ni negativo)
    if ($producto['stock_minimo'] < 1)     $errores[] = 'El stock mínimo debe ser al menos 1 (no puede ser 0).';

    // Producto nuevo: stock inicial MAYOR al stock mínimo elegido
    if (!$esEdicion && $producto['stock_minimo'] >= 1 && $producto['stock_actual'] <= $producto['stock_minimo']) {
        $errores[] = 'El stock inicial (' . $producto['stock_actual'] . ') debe ser mayor al stock mínimo (' . $producto['stock_minimo'] . ').';
    }

    // Código no puede repetirse
    if (empty($errores)) {
        $chk = $pdo->prepare('SELECT id_producto FROM productos WHERE codigo = :codigo AND id_producto != :id');
        $chk->execute(['codigo' => $producto['codigo'], 'id' => $id]);
        if ($chk->fetch()) {
            $errores[] = 'Ese código ya está en uso por otro producto.';
        }
    }

    if (empty($errores)) {
        try {
            $pdo->beginTransaction();

            // Si pidió una categoría nueva, se crea aquí mismo (o se reutiliza si ya existía)
            if ($esNueva) {
                $buscar = $pdo->prepare('SELECT id_categoria FROM categorias WHERE LOWER(nombre) = LOWER(:n)');
                $buscar->execute(['n' => $nuevaCategoria]);
                $existente = $buscar->fetchColumn();

                if ($existente !== false) {
                    $producto['id_categoria'] = (int) $existente;
                    $pdo->prepare('UPDATE categorias SET estado = "activo" WHERE id_categoria = :id')
                        ->execute(['id' => $existente]);
                } else {
                    $pdo->prepare('INSERT INTO categorias (nombre, descripcion, estado) VALUES (:n, "", "activo")')
                        ->execute(['n' => $nuevaCategoria]);
                    $producto['id_categoria'] = (int) $pdo->lastInsertId();
                }
            }

            if ($esEdicion) {
                $sql = 'UPDATE productos SET codigo=:codigo, nombre=:nombre, descripcion=:desc,
                        id_categoria=:cat, precio=:precio, stock_minimo=:stock_min WHERE id_producto=:id';
                $params = [
                    'codigo' => $producto['codigo'], 'nombre' => $producto['nombre'], 'desc' => $producto['descripcion'],
                    'cat' => $producto['id_categoria'], 'precio' => $producto['precio'],
                    'stock_min' => $producto['stock_minimo'], 'id' => $id,
                ];
                $msg = 'Producto actualizado.';
            } else {
                $sql = 'INSERT INTO productos (codigo, nombre, descripcion, id_categoria, precio, stock_actual, stock_minimo, estado)
                        VALUES (:codigo, :nombre, :desc, :cat, :precio, :stock_actual, :stock_min, "activo")';
                $params = [
                    'codigo' => $producto['codigo'], 'nombre' => $producto['nombre'], 'desc' => $producto['descripcion'],
                    'cat' => $producto['id_categoria'], 'precio' => $producto['precio'],
                    'stock_actual' => $producto['stock_actual'], 'stock_min' => $producto['stock_minimo'],
                ];
                $msg = 'Producto creado.';
            }
            $pdo->prepare($sql)->execute($params);

            $pdo->commit();
            if ($esNueva) $msg .= ' Categoría «' . $nuevaCategoria . '» lista para usar.';
            flash_set('ok', $msg);
            header('Location: productos.php');
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errores[] = 'No se pudo guardar: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $esEdicion ? 'Editar' : 'Nuevo' ?> producto · TecnoStock</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<?php include 'partials/header.php'; ?>
<?php include 'partials/nav.php'; ?>

<div class="wrap">
  <h1><?= $esEdicion ? 'Editar producto' : 'Nuevo producto' ?></h1>

  <?php if (!empty($errores)): ?>
    <div class="flash flash-error">
      <?= implode('<br>', array_map('htmlspecialchars', $errores)) ?>
    </div>
  <?php endif; ?>

  <form class="form" method="post" id="formProducto" novalidate>
    <div class="form-row">
      <label>
        Código
        <input type="text" name="codigo" value="<?= htmlspecialchars($producto['codigo']) ?>" required>
      </label>
      <label style="flex:2">
        Nombre
        <input type="text" name="nombre" value="<?= htmlspecialchars($producto['nombre']) ?>" required>
      </label>
    </div>

    <label>
      Descripción
      <textarea name="descripcion" rows="2"><?= htmlspecialchars($producto['descripcion']) ?></textarea>
    </label>

    <label>
      Categoría
      <select name="id_categoria" id="selCategoria" required>
        <?php foreach ($categorias as $c): ?>
          <option value="<?= $c['id_categoria'] ?>"
            <?= (!$esNueva && $c['id_categoria'] == $producto['id_categoria']) ? 'selected' : '' ?>>
            <?= iconoCategoria($c['nombre']) ?> <?= htmlspecialchars($c['nombre']) ?>
          </option>
        <?php endforeach; ?>
        <option value="nueva" <?= $esNueva ? 'selected' : '' ?>>➕ Crear nueva categoría...</option>
      </select>
    </label>

    <label id="boxNuevaCategoria" style="display:none;">
      Nombre de la nueva categoría
      <input type="text" name="nueva_categoria" id="inpNuevaCategoria" maxlength="60"
             placeholder="ej: Gaming, Redes, Smartwatches..."
             value="<?= htmlspecialchars($nuevaCategoria) ?>">
    </label>

    <div class="form-row">
      <label>
        Precio
        <input type="number" step="1" min="1" name="precio" id="inpPrecio"
               placeholder="mayor a 0"
               value="<?= ($producto['precio'] !== '' && (float)$producto['precio'] >= 1) ? htmlspecialchars((string)(int)$producto['precio']) : '' ?>">
      </label>

      <?php if ($esEdicion): ?>
        <label>
          Stock actual
          <input type="number" value="<?= (int)$producto['stock_actual'] ?>" disabled>
        </label>
        <label>
          Stock mínimo
          <input type="number" step="1" min="1" name="stock_minimo" id="inpMinimo"
                 value="<?= (int)$producto['stock_minimo'] ?>">
        </label>
      <?php else: ?>
        <label>
          Stock inicial
          <input type="number" step="1" min="2" name="stock_actual" id="inpStock"
                 placeholder="mayor al mínimo"
                 value="<?= ($producto['stock_actual'] !== '') ? (int)$producto['stock_actual'] : '' ?>">
        </label>
        <label>
          Stock mínimo
          <input type="number" step="1" min="1" name="stock_minimo" id="inpMinimo"
                 value="<?= (int)$producto['stock_minimo'] ?>">
        </label>
      <?php endif; ?>
    </div>

    <?php if (!$esEdicion): ?>
      <p class="subtitle" style="margin:0;">El stock mínimo parte en <?= STOCK_MINIMO_DEFECTO ?> y puedes cambiarlo, pero no puede ser 0. El stock inicial debe ser mayor al mínimo y el precio mayor a 0.</p>
    <?php else: ?>
      <p class="subtitle" style="margin:0;">El stock actual solo cambia registrando <a href="movimiento_nuevo.php?producto=<?= $id ?>">movimientos de entrada o salida</a>.</p>
    <?php endif; ?>

    <p class="flash flash-error" id="msgError" style="display:none; margin:0;"></p>

    <div class="form-actions">
      <button type="submit" class="btn">guardar</button>
      <a class="btn-ghost" href="productos.php">cancelar</a>
    </div>
  </form>
</div>

<footer class="foot">
  <span>TecnoStock · sistema de inventario · validaciones v3</span>
</footer>

<script>
// ---- Categoría nueva: muestra el campo de texto solo cuando se elige esa opción ----
const selCat = document.getElementById('selCategoria');
const boxNueva = document.getElementById('boxNuevaCategoria');
const inpNueva = document.getElementById('inpNuevaCategoria');

function alternarNuevaCategoria() {
  const esNueva = selCat.value === 'nueva';
  boxNueva.style.display = esNueva ? 'flex' : 'none';
  if (esNueva) inpNueva.focus();
}
selCat.addEventListener('change', alternarNuevaCategoria);
alternarNuevaCategoria();

// ---- Validaciones inmediatas (se repiten igual en PHP) ----
const form      = document.getElementById('formProducto');
const inpPrecio = document.getElementById('inpPrecio');
const inpStock  = document.getElementById('inpStock');   // solo existe al crear
const inpMinimo = document.getElementById('inpMinimo');
const msgError  = document.getElementById('msgError');

form.addEventListener('submit', function (e) {
  const errores = [];

  const precio = parseFloat(inpPrecio.value);
  if (isNaN(precio) || precio < 1) {
    errores.push('El precio debe ser mayor a 0 (mínimo $1).');
  }

  // Stock mínimo: no puede ser 0 (ni vacío ni negativo)
  const minimo = parseInt(inpMinimo.value, 10);
  if (isNaN(minimo) || minimo < 1) {
    errores.push('El stock mínimo debe ser al menos 1 (no puede ser 0).');
  } else if (inpStock) { // solo al crear el producto
    const stock = parseInt(inpStock.value, 10);
    if (isNaN(stock) || stock <= minimo) {
      errores.push('El stock inicial debe ser mayor al stock mínimo (' + minimo + '): ingresa ' + (minimo + 1) + ' o más.');
    }
  }

  if (errores.length > 0) {
    e.preventDefault();
    msgError.innerHTML = errores.join('<br>');
    msgError.style.display = 'block';
  }
});
</script>

</body>
</html>
