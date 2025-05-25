<?php
echo "<pre>SESSION DEBUG:\n";
var_dump($_SESSION);
echo "</pre>";
// The rest of your app.php layout file starts below
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'ShineO Application') ?></title>
    <style>
        body { font-family: sans-serif; margin: 0; padding: 0; background-color: #f4f7f6; color: #333; }
        .container { width: 90%; max-width: 1200px; margin: 20px auto; padding: 20px; background-color: #fff; box-shadow: 0 0 10px rgba(0,0,0,0.1); border-radius: 5px;}
        header { background-color: #007bff; color: white; padding: 15px 0; text-align: center; margin-bottom: 20px;}
        header h1 { margin: 0; }
        nav { background-color: #333; padding: 10px 0; text-align: center; }
        nav a { color: white; margin: 0 15px; text-decoration: none; }
        nav a:hover { text-decoration: underline; }
        footer { text-align: center; padding: 20px 0; margin-top: 30px; color: #777; border-top: 1px solid #eee; }
        .error-message { color: red; background-color: #ffebee; border: 1px solid #ef9a9a; padding: 10px; border-radius: 3px; margin-bottom: 15px; text-align: center;}
        .success-message { color: green; background-color: #e8f5e9; border: 1px solid #a5d6a7; padding: 10px; border-radius: 3px; margin-bottom: 15px; text-align: center;}

    </style>
</head>
<body>
<header>
    <h1>ShineO Management</h1>
</header>

<nav>
    <a href="/">Home</a>
    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
        <a href="/dashboard">Dashboard</a>
        <?php if (isset($_SESSION['user_role_id']) && $_SESSION['user_role_id'] === 1): // Assuming 1 is Admin ?>
            <a href="/admin/users">User Management</a>
        <?php endif; ?>
        <a href="/logout">Logout (<?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?>)</a>
    <?php else: ?>
        <a href="/login">Login</a>
    <?php endif; ?>
</nav>

<div class="container">
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="<?= htmlspecialchars($_SESSION['flash_message']['type']) === 'success' ? 'success-message' : 'error-message' ?>">
            <?= htmlspecialchars($_SESSION['flash_message']['message']) ?>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <?= $content ?? '' ?> </div>

<footer>
    <p>&copy; <?= date('Y') ?> ShineO Application. All rights reserved.</p>
</footer>

</body>
</html>