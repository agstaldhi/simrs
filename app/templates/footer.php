<?php

/**
 * Footer Template
 */

$year = date('Y');
$hospitalName = config('app.app_name', 'SIMRS');
?>

<footer class="footer" role="contentinfo">
    <div class="footer-container">
        <div class="footer-content">
            <p class="footer-text">
                &copy; <?= $year ?> <?= e($hospitalName) ?>. All rights reserved.
            </p>
            <p class="footer-version">
                Version <?= config('app.app_version', '1.0.0') ?>
            </p>
        </div>
    </div>
</footer>