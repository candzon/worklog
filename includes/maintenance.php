<?php
/**
 * includes/maintenance.php
 * Halaman pemberitahuan website dalam pengembangan
 */
require_once __DIR__ . '/../functions/helpers.php';
?>

<div class="app-main">
    <div class="app-content p-4">
        <div class="container-fluid">
            <div class="row justify-content-center align-items-center" style="min-height: 70vh;">
                <div class="col-12 col-md-8 col-lg-6 text-center">
                    <div class="card shadow-lg border-0" style="border-radius: 24px; overflow: hidden;">
                        <div class="card-header bg-primary py-4 border-0">
                            <i class="bi bi-tools text-white" style="font-size: 4rem;"></i>
                        </div>
                        <div class="card-body p-5">
                            <h2 class="fw-bold mb-3" style="color: #1e3a8a;">Mohon Maaf</h2>
                            <p class="lead text-muted mb-4">
                                Website saat ini sedang dalam tahap pengembangan dan peningkatan sistem demi kenyamanan Anda.
                            </p>
                            <div class="alert alert-info border-0 rounded-pill py-3">
                                <i class="bi bi-calendar-check-fill me-2"></i>
                                <strong>Siap digunakan kembali:</strong> Besok hari
                            </div>
                            <div class="mt-5">
                                <p class="small text-secondary mb-0">Terima kasih atas kesabaran Anda.</p>
                                <div class="mt-3">
                                    <a href="<?php echo site_url('index.php'); ?>" class="btn btn-outline-primary px-4 rounded-pill">
                                        <i class="bi bi-arrow-left me-2"></i> Kembali
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
