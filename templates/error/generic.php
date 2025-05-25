<?php
/** @var string $pageTitle */
/** @var string $errorMessage */
?>
<h2><?= htmlspecialchars($pageTitle ?? 'An Error Occurred') ?></h2>
<p class="error-message"><?= htmlspecialchars($errorMessage ?? 'Something went wrong. Please try again.') ?></p>
<p><a href="/">Go to Homepage</a></p>