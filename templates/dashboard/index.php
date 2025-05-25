<?php
/** @var string $userName */
/** @var int $userId */
/** @var int $userRoleId */
?>
<h1>Welcome to your Dashboard, <?= htmlspecialchars($userName) ?>!</h1>
<p>This is your protected dashboard area.</p>
<p>Your User ID is: <?= htmlspecialchars($userId) ?></p>
<p>Your Role ID is: <?= htmlspecialchars($userRoleId) ?></p>