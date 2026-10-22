<?php
/** @var string|null $pageScript */
?>
<script src="<?= asset('assets/js/app.js') ?>"></script>
<?php if (!empty($pageScript)): ?>
    <?= $pageScript ?>
<?php endif; ?>
</body>
</html>
