</div> <script>
    const sidebar = document.getElementById('sidebar');
    const openBtn = document.getElementById('openSidebarBtn');
    const closeBtn = document.getElementById('closeSidebarBtn');
    const overlay = document.getElementById('sidebar-overlay');

    openBtn.addEventListener('click', () => {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('sidebar-overlay-hidden');
    });

    closeBtn.addEventListener('click', () => {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('sidebar-overlay-hidden');
    });
    
    overlay.addEventListener('click', () => {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('sidebar-overlay-hidden');
    });
</script>

</body>
</html>