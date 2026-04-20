<?= $this->extend('emails/layout_dark') ?>

<?= $this->section('content') ?>

<p class="message">
  File laporan yang Anda minta sudah selesai dibuat.
</p>

<div class="cta-wrap">
  <a href="<?= esc($downloadUrl) ?>" class="btn">Download File</a>
</div>

<p class="message" style="font-size:13px">
  Jika tombol tidak berfungsi:<br>
  <a href="<?= esc($downloadUrl) ?>"><?= esc($downloadUrl) ?></a>
</p>

<?= $this->endSection() ?>