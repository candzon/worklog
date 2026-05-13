<?php
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';
// Ensure user is logged in for non-public pages
require_login();
?>
	
	<!-- /.app-wrapper -->

	<footer class="app-footer">
		<strong>&copy; 2026 TIM IT Production</strong>
	</footer>

	<!-- Core scripts -->
	<script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/rrule@2/dist/es5/rrule.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/rrule@5.11.3/main.global.min.js"></script>
	<script src="<?php echo asset_url('js/adminlte.js'); ?>"></script>

	<script>
		document.addEventListener('DOMContentLoaded', function () {
			const sidebarWrapper = document.querySelector('.sidebar-wrapper');
			const isMobile = window.innerWidth <= 992;
			if (sidebarWrapper && window.OverlayScrollbars && !isMobile) {
				OverlayScrollbars(sidebarWrapper, { scrollbars: { theme: 'os-theme-light' } });
			}
		});
	</script>

<?php
// Render any queued SweetAlert flash message (prints a <script> tag)
if (function_exists('render_flash_swal')) {
    render_flash_swal();
}
?>

</body>
</html>
