<?php
// Footer partial — closes main and includes scripts
?>
	
	<!-- /.app-wrapper -->

	<footer class="app-footer">
		<strong>&copy; <?= date('Y') ?> Worklog</strong>
	</footer>

	<!-- Core scripts -->
	<script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
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

</body>
</html>
