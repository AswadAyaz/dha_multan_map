<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

$error = '';

if (isAdminLoggedIn()) {
    header('Location: /admin/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrf)) {
        $error = 'Session expired. Please refresh and try again.';
    } elseif (empty($password)) {
        $error = 'Please enter the password.';
    } else {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = 'admin@dhamultanmap.pk' LIMIT 1");
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_email'] = $admin['email'];
            header('Location: /admin/dashboard.php');
            exit;
        } else {
            $error = 'Invalid password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | DHA Multan Property Map</title>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-surface">

  <div class="login-wrapper">
    <div class="login-card">
      <div class="login-header">
        <a href="/" class="login-logo">
          <span class="logo-shield">DHA</span>
          <span class="logo-text">MULTAN <em>MAPS</em></span>
        </a>
        <h2>Administrator Portal</h2>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo e($error); ?></div>
      <?php endif; ?>

      <form method="POST" action="/admin/login.php" class="login-form">
        <input type="hidden" name="csrf_token" value="<?php echo e(getCsrfToken()); ?>">

        <div class="form-group">
          <label for="password">Admin Password</label>
          <input type="password" id="password" name="password" required class="form-control" placeholder="Enter admin password" autofocus>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Sign In</button>
      </form>

      <div class="login-footer">
        <a href="/">← Back to Map</a>
      </div>
    </div>
  </div>

</body>
</html>
