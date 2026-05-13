
<?php
require_once __DIR__ . '/../functions/helpers.php';
ensure_session_started();
?>

<div class="app-main">
	<div class="app-content p-4">
		<div class="container-fluid">
			<div class="row justify-content-center">
				<div class="col-12 col-md-8 col-lg-6">
					<div class="card">
						<div class="card-body">
							<div class="d-flex align-items-center gap-3">
								<div class="display-6 text-body-secondary">500</div>
								<div>
									<h5 class="mb-1">Terjadi Kesalahan</h5>
									<div class="text-body-secondary">Ada masalah pada server. Silakan coba lagi.</div>
								</div>
							</div>
							<div class="mt-4 d-flex gap-2">
								<a class="btn btn-primary" href="<?php echo site_url('index.php'); ?>">Kembali ke Dashboard</a>
								<button class="btn btn-outline-secondary" type="button" onclick="location.reload()">Muat Ulang</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

