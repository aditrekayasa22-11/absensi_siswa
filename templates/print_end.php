    <footer class="print-footer">
        <span>Dokumen dihasilkan otomatis oleh <?= e(config('app.name', 'PresensiKu')) ?>.</span>
        <span><?= e($user['nama'] ?? 'Admin') ?> &middot; <?= e(date('d/m/Y H:i')) ?></span>
    </footer>
</main>
<script>
    window.addEventListener('load', function () {
        window.print();
    });
</script>
<?php require BASE_PATH . '/templates/footer.php'; ?>
