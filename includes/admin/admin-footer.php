<?php
/**
 * includes/admin/admin-footer.php
 * Pied de page commun pour toutes les pages du back-office.
 */
declare(strict_types=1);
?>
            </div> <!-- .admin-content -->
        </main> <!-- .admin-main -->
    </div> <!-- .admin-layout -->

    <?php if (!empty($extraScripts) && is_array($extraScripts)): ?>
        <?php foreach ($extraScripts as $script): ?>
            <?php $src = (strpos($script, 'http') === 0 || strpos($script, '//') === 0) ? $script : (SITE_URL . $script); ?>
            <script src="<?= e($src) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>