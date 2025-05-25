<?php
// This line helps with intellisense in some editors for variables passed from View::render()
/** @var string $actionUrl */
/** @var string $emailValue */ // For pre-filling email if needed
/** @var string|null $loginError */ // To display login errors directly if not using flash messages
?>

<style>
    .login-container { background-color: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); max-width: 400px; margin: 20px auto; }
    .form-group { margin-bottom: 15px; }
    label { display: block; margin-bottom: 5px; }
    input[type="email"], input[type="password"] { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 3px; }
    input[type="submit"] { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 3px; cursor: pointer; width:100%; }
    input[type="submit"]:hover { background-color: #0056b3; }
    /* .error-message is already in layout, but can be specific here too */
</style>

<div class="login-container">
    <h2>ShineO Login</h2>

    <?php if (isset($loginError) && !empty($loginError)): ?>
        <p class="error-message"><?= htmlspecialchars($loginError) ?></p>
    <?php endif; ?>

    <form method="POST" action="<?= htmlspecialchars($actionUrl ?? '/login-process') ?>">
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($emailValue ?? 'admin@example.com') ?>" required>
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div class="form-group">
            <input type="submit" value="Login">
        </div>
    </form>
</div>