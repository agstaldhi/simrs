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
        </div>
    </div>
</footer>