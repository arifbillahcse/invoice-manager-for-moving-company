<?php
// Required variables before including this file:
//   $pageTitle  (string) - page title shown in <title>
//   $activePage (string) - key matching one of the nav items below

$navItems = [
    'dashboard'   => ['href' => 'dashboard.php',   'icon' => '📊', 'label' => 'Dashboard'],
    'companies'   => ['href' => 'companies.php',   'icon' => '🏢', 'label' => 'Companies'],
    'drivers'     => ['href' => 'drivers.php',     'icon' => '🚗', 'label' => 'Drivers'],
    'inv-company' => ['href' => 'inv-company.php', 'icon' => '🏢', 'label' => 'Invoice / Company'],
    'inv-driver'  => ['href' => 'inv-driver.php',  'icon' => '🚗', 'label' => 'Invoice / Driver'],
];
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Invoice Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-wrapper">

    <div class="header">
        <div>
            <h1>📋 Invoice Management System</h1>
            <p>Manage companies, drivers, and generate invoices</p>
        </div>
        <div class="header-actions">
            <a class="btn btn-success" href="api/export.php">📥 Export CSV</a>
        </div>
    </div>

    <div class="nav-tabs">
        <?php foreach ($navItems as $key => $item): ?>
        <a class="nav-tab<?php echo ($activePage === $key) ? ' active' : ''; ?>" href="<?php echo $item['href']; ?>">
            <?php echo $item['icon']; ?> <?php echo $item['label']; ?>
        </a>
        <?php endforeach; ?>
    </div>
