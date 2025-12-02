<?php

/**
 * Main Layout Template
 * 
 * Wrapper layout for all pages
 */

$user = Auth::user();
$flash = Session::getFlash();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= e($title ?? 'SIMRS') ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= asset('images/favicon.ico') ?>">

    <!-- CSS -->
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/responsive.css') ?>">

    <!-- CSRF Token for AJAX -->
    <meta name="csrf-token" content="<?= CSRF::getToken() ?>">
</head>

<body>
    <!-- Header -->
    <?php include __DIR__ . '/header.php'; ?>

    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle Menu">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <!-- Main Container -->
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar (Desktop) / Mobile Menu -->
            <?php if ($user): ?>
                <?php include __DIR__ . '/sidebar.php'; ?>
            <?php endif; ?>

            <!-- Main Content -->
            <main class="main-content <?= $user ? 'with-sidebar' : 'full-width' ?>" role="main">

                <!-- Flash Messages -->
                <?php if ($flash): ?>
                    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                        <?= e($flash['message']) ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Page Content -->
                <?= $content ?>

            </main>
        </div>
    </div>

    <!-- Footer -->
    <?php include __DIR__ . '/footer.php'; ?>

    <!-- JavaScript -->
    <script src="<?= asset('js/app.js') ?>"></script>
    <script src="<?= asset('js/mobile-menu.js') ?>"></script>

    <!-- Additional Scripts -->
    <?php if (isset($scripts)): ?>
        <?= $scripts ?>
    <?php endif; ?>
</body>

</html>