<footer class="site-footer">
    <p>
        For life-threatening emergencies, you can also dial <strong>911</strong> nationwide.
    </p>
    <p class="site-footer__meta">
        &copy; <?= date('Y') ?> Cebu Emergency Hotlines &middot; <a href="<?= BASE_URL ?>/about.php">About this service</a>
    </p>
</footer>
<?php foreach ($pageScripts ?? [] as $src): ?>
<script src="<?= BASE_URL . $src ?>?v=<?= filemtime(__DIR__ . '/' . ltrim($src, '/')) ?>"></script>
<?php endforeach; ?>
</body>
</html>
