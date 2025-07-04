<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'ShineO Application') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body { font-family: sans-serif; background-color: #f8f9fa; }
        .app-container { width: 90%; max-width: 1200px; margin: 20px auto; padding: 0px; }
        header.app-header { background-color: #007bff; color: white; padding: 1rem 0; text-align: center; margin-bottom: 20px;}
        header.app-header h1 { margin: 0; }
        footer.app-footer { text-align: center; padding: 20px 0; margin-top: 30px; color: #6c757d; border-top: 1px solid #dee2e6; }
        .alert-success { color: #0f5132; background-color: #d1e7dd; border-color: #badbcc; }
        .alert-danger { color: #842029; background-color: #f8d7da; border-color: #f5c2c7; }
        .alert { padding: 1rem; margin-bottom: 1rem; border: 1px solid transparent; border-radius: .25rem; }
    </style>
</head>
<body>
<header class="app-header">
    <h1>ShineO Management</h1>
</header>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="/">ShineO</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="/">Home</a>
                </li>

                <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                    <?php
                    // Determine the correct primary "Dashboard" or landing page URL based on role
                    $dashboardUrl = '/dashboard'; // Generic fallback
                    if (isset($_SESSION['user_role_id'])) {
                        if ($_SESSION['user_role_id'] === 1) { $dashboardUrl = '/admin/users'; }      // Admin lands on User list
                        elseif ($_SESSION['user_role_id'] === 2) { $dashboardUrl = '/staff/dashboard'; }    // Staff to their dashboard
                        elseif ($_SESSION['user_role_id'] === 3) { $dashboardUrl = '/supervisor/dashboard';}// Supervisor to their dashboard
                        elseif ($_SESSION['user_role_id'] === 4) { $dashboardUrl = '/payroll/dashboard'; } // Payroll to their dashboard
                    }
                    ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= htmlspecialchars($dashboardUrl) ?>">My Dashboard</a>
                    </li>

                    <?php // Admin Menu Dropdown
                    if (isset($_SESSION['user_role_id']) && $_SESSION['user_role_id'] === 1): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="adminMenuDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Admin Panel
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="adminMenuDropdown">
                                <li><a class="dropdown-item" href="/admin/users">User Management</a></li>
                                <li><a class="dropdown-item" href="/admin/companies">Company Management</a></li>
                                <li><a class="dropdown-item" href="/admin/sites">Site Management</a></li>

                            </ul>
                        </li>
                    <?php endif; ?>

                    <?php // Supervisor Menu Dropdown
                    if (isset($_SESSION['user_role_id']) && $_SESSION['user_role_id'] === 3): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="supervisorTimesheetDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Timesheet Actions
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="supervisorTimesheetDropdown">
                                <li><a class="dropdown-item" href="/supervisor/timesheets/pending">Pending Approvals</a></li>
                                <li><a class="dropdown-item" href="/supervisor/timesheets/disputed">Disputed Timesheets</a></li>
                                <li><a class="dropdown-item" href="/supervisor/team-timesheets">All Team Timesheets</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="supervisorPaysheetDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Paysheets
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="supervisorPaysheetDropdown">
                                <li><a class="dropdown-item" href="/supervisor/paysheets">My Generated Paysheets</a></li>
                                <li><a class="dropdown-item" href="/supervisor/paysheets/create">Generate New Paysheet</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/supervisor/paysheets/under-review">Paysheets Under Review</a></li>
                            </ul>
                        </li>

                    <?php endif; ?>

                    <?php // Staff Menu (Could be a dropdown if they get more top-level actions)
                    if (isset($_SESSION['user_role_id']) && $_SESSION['user_role_id'] === 2): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/staff/timesheets/create">Submit Timesheet</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/staff/timesheets">My Timesheets</a>
                        </li>

                    <?php endif; ?>

                    <?php // Payroll Team Menu Dropdown
                    if (isset($_SESSION['user_role_id']) && $_SESSION['user_role_id'] === 4): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="payrollMenuDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Payroll Actions
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="payrollMenuDropdown">
                                <li><a class="dropdown-item" href="/payroll/paysheets/pending-review">Review Submitted Paysheets</a></li>

                            </ul>
                        </li>
                    <?php endif; ?>

                <?php endif; // End of logged_in check for main menu items ?>
            </ul>

            <ul class="navbar-nav"> {/* Right-aligned items */}
                <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/logout">Logout (<?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?>)</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/login">Login</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="app-container container mt-4">
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert <?= htmlspecialchars($_SESSION['flash_message']['type']) === 'success' ? 'alert-success' : 'alert-danger' ?>" role="alert">
            <?= htmlspecialchars($_SESSION['flash_message']['message']) ?>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <?= $content ?? '' ?>
</main>

<footer class="app-footer">
    <p>&copy; <?= date('Y') ?> ShineO Application. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>