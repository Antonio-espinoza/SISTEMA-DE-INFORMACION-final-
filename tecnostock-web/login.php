<?php
require_once 'config.php';
require_once 'helpers.php';

if (usuario_actual()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $clave  = $_POST['clave'] ?? '';

    if ($correo === '' || $clave === '') {
        $error = 'Ingresa tu correo y contraseña.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE correo = :correo AND estado = "activo"');
        $stmt->execute(['correo' => $correo]);
        $u = $stmt->fetch();

        if ($u && password_verify($clave, $u['clave'])) {
            $_SESSION['usuario'] = [
                'id_usuario' => $u['id_usuario'],
                'nombre'     => $u['nombre'],
                'correo'     => $u['correo'],
            ];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Correo o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Iniciar sesión · TecnoStock</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="login-page">
  <div class="login-card">
    <a class="logo" href="#">
      <span class="logo-mark">TS</span>
      TecnoStock
    </a>
    <p class="tag">Sistema de inventario</p>

    <?php if ($error): ?>
      <div class="flash flash-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form class="form" method="post" style="border:none; padding:0; margin:0;">
      <label>
        Correo
        <input type="email" name="correo" required autofocus value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>">
      </label>
      <label>
        Contraseña
        <input type="password" name="clave" required>
      </label>
      <button type="submit" class="btn btn-full">iniciar sesión</button>
    </form>

    <p class="hint">Usuario de prueba: admin@tecnostock.cl / admin123</p>
  </div>
</div>

</body>
</html>
