<?php
// admin/includes/admin_footer.php - Admin Footer
?>
        </main>
        
        <footer style="background: #ffffff; padding: 1rem 1.5rem; border-top: 1px solid var(--border-color); font-size: 0.8125rem; color: var(--text-muted); display: flex; justify-content: space-between; align-items: center;">
            <div>&copy; <?= date('Y') ?> <strong>MY ADVOCATE</strong> Administrator Console</div>
            <div>Database: <code><?= defined('DB_NAME') ? DB_NAME : 'u305984835_myadvocate' ?></code> &bull; v2.0.0</div>
        </footer>
    </div>
</div>

<script src="../assets/js/main.js?v=<?= APP_VERSION ?>"></script>
</body>
</html>
