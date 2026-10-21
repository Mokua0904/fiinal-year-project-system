</main> <!-- Close Page Content Area -->
</div> <!-- Close Main Content Wrapper -->

<script>
    // Toggle the user dropdown menu
    function toggleUserMenu() {
        const menu = document.getElementById('userMenu');
        if (menu) {
            menu.classList.toggle('hidden');
        }
    }

    // Close the dropdown if the user clicks anywhere outside of it
    window.onclick = function(event) {
        if (!event.target.closest('.relative') && !event.target.closest('#userMenu')) {
            const menu = document.getElementById('userMenu');
            if (menu && !menu.classList.contains('hidden')) {
                menu.classList.add('hidden');
            }
        }
    }
</script>
</body>
</html>