<?php // includes/footer.php ?>
</main>

<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <span class="logo-mark">P</span>
            <span class="logo-text">atapied</span>
            <p>Chaussures urbaines ergonomiques<br>conçues en France.</p>
        </div>
        <div class="footer-links">
            <h4><?= t('nav_catalog') ?></h4>
            <a href="/index.php?page=catalog">Ville</a>
            <a href="/index.php?page=catalog&cat=sport">Sport</a>
            <a href="/index.php?page=catalog&cat=randonnee">Randonnée</a>
            <a href="/index.php?page=catalog&cat=enfants">Enfants</a>
        </div>
        <div class="footer-links">
            <h4>Mon compte</h4>
            <a href="/index.php?page=account"><?= t('nav_account') ?></a>
            <a href="/index.php?page=orders"><?= t('nav_orders') ?></a>
            <a href="/index.php?page=cart"><?= t('nav_cart') ?></a>
        </div>
        <div class="footer-links">
            <h4>Projet</h4>
            <a href="/index.php?page=report"><?= t('nav_report') ?></a>
            <span class="footer-tech">Hébergé sur Debian Linux<br>Stack : Apache · PHP · MariaDB</span>
        </div>
    </div>
    <div class="footer-bottom">
        <span><?= t('footer_rights') ?></span>
        <span><?= t('footer_made_by') ?> — BUT R&T 2026</span>
    </div>
</footer>

<script src="/assets/js/main.js"></script>
</body>
</html>
