    </main><!-- /.admin-main -->
</div><!-- /.admin-layout -->

<script>
(function() {
    const burger  = document.getElementById('adminBurger');
    const sidebar = document.getElementById('adminSidebar');
    if (burger && sidebar) {
        burger.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
        document.addEventListener('click', function(e) {
            if (!sidebar.contains(e.target) && !burger.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    // Confirm dangerous actions
    document.querySelectorAll('[data-confirm]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (!confirm(el.dataset.confirm)) {
                e.preventDefault();
                return false;
            }
        });
    });
})();
</script>
</body>
</html>
