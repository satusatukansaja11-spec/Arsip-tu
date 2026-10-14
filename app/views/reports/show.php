<?php
/** @var string $key @var array $def @var array $f @var array $report @var array $options @var array $describe @var int $maxRows */
$visible = [];
foreach ($report['columns'] as $i => $c) {
    if (empty($c['csv'])) {
        $visible[$i] = $c;
    }
}
$exportParams = array_merge(['jenis' => $key], $f);
$hasData = false;
foreach ($report['rows'] as $r) {
    if (!isset($r['_group'])) {
        $hasData = true;
        break;
    }
}
$colCount = count($visible);
$statusLabel = ['PNS' => 'PNS', 'PPPK' => 'PPPK', 'HONORER' => 'Honorer'];
?>
<style>
  .print-only { display: none; }
  .report-table th { white-space: nowrap; }
  .report-table .group-row td { background: #e8f0fe; }
  @media print {
    @page { size: A4 landscape; margin: 12mm; }
    body { background: #fff !important; }
    .print-only { display: block !important; }
    .card { border: 0 !important; box-shadow: none !important; }
    .report-table { font-size: 11px; }
    .report-table th, .report-table td { border: 1px solid #000 !important; padding: 3px 5px !important; }
    .report-table .group-row td { background: #eee !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .report-summary { font-size: 12px; }
  }
</style>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3 no-print">
  <div>
    <h1 class="h4 mb-0"><?= e($def['judul']) ?></h1>
    <small class="text-muted"><?= e($def['deskripsi']) ?></small>
  </div>
  <div class="ms-auto d-flex flex-wrap gap-2">
    <a class="btn btn-outline-secondary" href="<?= e(url('laporan')) ?>"><i class="bi bi-arrow-left"></i> Daftar Laporan</a>
    <?php if (Auth::can('report.print')): ?>
      <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Cetak</button>
    <?php endif; ?>
    <a class="btn btn-primary" href="<?= e(query_url('laporan/ekspor', $exportParams)) ?>"><i class="bi bi-filetype-csv"></i> Ekspor CSV</a>
  </div>
</div>

<form method="get" action="<?= e(url('laporan/tampil')) ?>" class="card border-0 shadow-sm mb-3 no-print">
  <input type="hidden" name="jenis" value="<?= e($key) ?>">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <?php foreach ($def['filter'] as $fk): ?>
        <?php if ($fk === 'status'): ?>
          <div class="col-6 col-md-3">
            <label class="form-label small text-muted mb-0" for="f-status">Status kepegawaian</label>
            <select class="form-select" id="f-status" name="status">
              <option value="">Semua</option>
              <?php foreach (Employee::STATUSES as $s): ?>
                <option value="<?= e($s) ?>" <?= $f['status'] === $s ? 'selected' : '' ?>><?= e($statusLabel[$s]) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php elseif ($fk === 'aktif'): ?>
          <div class="col-6 col-md-3">
            <label class="form-label small text-muted mb-0" for="f-aktif">Status aktif</label>
            <select class="form-select" id="f-aktif" name="aktif">
              <option value="">Aktif &amp; tidak aktif</option>
              <option value="1" <?= $f['aktif'] === '1' ? 'selected' : '' ?>>Aktif</option>
              <option value="0" <?= $f['aktif'] === '0' ? 'selected' : '' ?>>Tidak aktif</option>
            </select>
          </div>
        <?php elseif ($fk === 'kelengkapan'): ?>
          <div class="col-6 col-md-3">
            <label class="form-label small text-muted mb-0" for="f-kel">Kelengkapan</label>
            <select class="form-select" id="f-kel" name="kelengkapan">
              <option value="">Semua</option>
              <option value="lengkap" <?= $f['kelengkapan'] === 'lengkap' ? 'selected' : '' ?>>Lengkap</option>
              <option value="belum" <?= $f['kelengkapan'] === 'belum' ? 'selected' : '' ?>>Belum lengkap</option>
            </select>
          </div>
        <?php elseif ($fk === 'kategori'): ?>
          <div class="col-md-4">
            <label class="form-label small text-muted mb-0" for="f-kat">Kategori dokumen</label>
            <select class="form-select" id="f-kat" name="kategori">
              <option value="">Semua kategori</option>
              <?php foreach (($options['kategori'] ?? []) as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= $f['kategori'] === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['nama']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php elseif ($fk === 'pegawai'): ?>
          <div class="col-md-4">
            <label class="form-label small text-muted mb-0" for="f-peg">Pegawai</label>
            <select class="form-select" id="f-peg" name="pegawai">
              <option value="">Semua pegawai</option>
              <?php foreach (($options['pegawai'] ?? []) as $p): ?>
                <option value="<?= (int)$p['id'] ?>" <?= $f['pegawai'] === (string)$p['id'] ? 'selected' : '' ?>><?= e($p['nama_lengkap']) ?><?= $p['nip'] ? ' (' . e($p['nip']) . ')' : '' ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php elseif ($fk === 'pengguna' && isset($options['pengguna'])): ?>
          <div class="col-md-3">
            <label class="form-label small text-muted mb-0" for="f-user">Pengguna</label>
            <select class="form-select" id="f-user" name="pengguna">
              <option value="">Semua pengguna</option>
              <?php foreach ($options['pengguna'] as $u): ?>
                <option value="<?= (int)$u['id'] ?>" <?= $f['pengguna'] === (string)$u['id'] ? 'selected' : '' ?>><?= e($u['nama_lengkap']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php elseif ($fk === 'aksi'): ?>
          <div class="col-md-3">
            <label class="form-label small text-muted mb-0" for="f-aksi">Jenis aktivitas</label>
            <select class="form-select" id="f-aksi" name="aksi">
              <option value="">Semua aktivitas</option>
              <?php foreach (($options['aksi'] ?? []) as $code => $label): ?>
                <option value="<?= e($code) ?>" <?= $f['aksi'] === $code ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php elseif ($fk === 'dari'): ?>
          <div class="col-6 col-md-2">
            <label class="form-label small text-muted mb-0" for="f-dari"><?= e($def['periode'] ?? 'Periode') ?> dari</label>
            <input type="date" class="form-control" id="f-dari" name="dari" value="<?= e($f['dari']) ?>">
          </div>
        <?php elseif ($fk === 'sampai'): ?>
          <div class="col-6 col-md-2">
            <label class="form-label small text-muted mb-0" for="f-sampai">Sampai</label>
            <input type="date" class="form-control" id="f-sampai" name="sampai" value="<?= e($f['sampai']) ?>">
          </div>
        <?php elseif ($fk === 'tahun_dari'): ?>
          <div class="col-6 col-md-2">
            <label class="form-label small text-muted mb-0" for="f-td">Tahun dokumen dari</label>
            <input type="number" class="form-control" id="f-td" name="tahun_dari" min="1950" max="2100" value="<?= e($f['tahun_dari']) ?>">
          </div>
        <?php elseif ($fk === 'tahun_sampai'): ?>
          <div class="col-6 col-md-2">
            <label class="form-label small text-muted mb-0" for="f-ts">Sampai tahun</label>
            <input type="number" class="form-control" id="f-ts" name="tahun_sampai" min="1950" max="2100" value="<?= e($f['tahun_sampai']) ?>">
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
      <div class="col-auto d-flex gap-2">
        <button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i> Terapkan</button>
        <a class="btn btn-outline-secondary" href="<?= e(url('laporan/tampil?jenis=' . $key)) ?>">Reset</a>
      </div>
    </div>
  </div>
</form>

<!-- Kop laporan: hanya tampil saat dicetak -->
<div class="print-only text-center mb-3">
  <div style="font-size:16px;font-weight:700;text-transform:uppercase"><?= e(setting('nama_sekolah', 'MTsN 1 Alor')) ?></div>
  <?php if (setting('alamat_sekolah') !== ''): ?>
    <div style="font-size:11px"><?= e(setting('alamat_sekolah')) ?></div>
  <?php endif; ?>
  <hr style="border:0;border-top:2px solid #000;margin:6px 0 10px">
  <div style="font-size:14px;font-weight:700"><?= e($def['judul']) ?></div>
  <?php foreach ($describe as $line): ?>
    <div style="font-size:11px"><?= e($line) ?></div>
  <?php endforeach; ?>
</div>

<?php if ($report['summary']): ?>
  <div class="d-flex flex-wrap gap-2 mb-3 report-summary">
    <?php foreach ($report['summary'] as [$label, $value]): ?>
      <div class="border rounded px-3 py-2 bg-white">
        <div class="text-muted small"><?= e($label) ?></div>
        <div class="fw-bold"><?= e($value) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if ($report['truncated']): ?>
  <div class="alert alert-warning no-print">
    Menampilkan <?= number_format($maxRows, 0, ',', '.') ?> dari <?= number_format((int)$report['total'], 0, ',', '.') ?> baris.
    Persempit dengan filter atau gunakan Ekspor CSV.
  </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0 report-table">
      <thead class="table-light">
        <tr>
          <?php foreach ($visible as $c): ?>
            <th class="text-<?= $c['a'] === 'end' ? 'end' : 'start' ?>"><?= e($c['l']) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($report['rows'] as $r): ?>
          <?php if (isset($r['_group'])): ?>
            <tr class="group-row"><td colspan="<?= (int)$colCount ?>"><strong><?= e($r['_group']) ?></strong></td></tr>
          <?php else: ?>
            <tr>
              <?php foreach ($visible as $i => $c): ?>
                <?php $cell = (string)($r[$i] ?? ''); ?>
                <td class="text-<?= $c['a'] === 'end' ? 'end' : 'start' ?>"><?= e($cell === '' ? '-' : $cell) ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endif; ?>
        <?php endforeach; ?>
        <?php if (!$hasData): ?>
          <tr><td colspan="<?= (int)$colCount ?>">
            <div class="empty-state"><i class="bi bi-inbox"></i><p class="mb-0">Tidak ada data untuk filter ini.</p></div>
          </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="print-only mt-3" style="font-size:11px">
  Dicetak oleh <?= e(Auth::user()['nama_lengkap'] ?? '') ?> pada <?= e(date('d-m-Y H:i')) ?> WITA.
</div>
