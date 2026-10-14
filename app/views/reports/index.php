<?php /** @var array $reports */ ?>
<div class="mb-3">
  <h1 class="h4 mb-0">Laporan</h1>
  <small class="text-muted">Pilih laporan, atur filter, lalu cetak atau ekspor ke CSV.</small>
</div>

<?php if (!$reports): ?>
  <div class="empty-state"><i class="bi bi-file-earmark-text"></i><p class="mb-0">Tidak ada laporan yang tersedia untuk akun Anda.</p></div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($reports as $key => $def): ?>
      <div class="col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body d-flex flex-column">
            <h2 class="h6"><i class="bi <?= e($def['ikon']) ?> text-primary"></i> <?= e($def['judul']) ?></h2>
            <p class="text-muted small flex-grow-1"><?= e($def['deskripsi']) ?></p>
            <a class="btn btn-primary btn-sm align-self-start" href="<?= e(url('laporan/tampil?jenis=' . $key)) ?>">Buka Laporan</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
