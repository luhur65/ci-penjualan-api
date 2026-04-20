<?= $this->extend('emails/layout_dark') ?>

<?= $this->section('content') ?>

<p class="message">
  Kami menerima permintaan untuk mereset password akun Anda.
  Klik tombol di bawah untuk membuat password baru. Link ini hanya berlaku selama <strong style="color:#e0e0f0">30 menit</strong>.
</p>

<!-- CTA -->
<div class="cta-wrap">
  <a href="<?= esc($link) ?>" class="btn-reset">Buat Password Baru &rarr;</a>
</div>

<!-- Expiry notice -->
<div class="expiry">
  ⏱ &nbsp;Link akan kedaluwarsa dalam <strong>&nbsp;30 menit</strong>&nbsp; sejak email ini dikirim.
</div>

<!-- Token box (opsional, bisa dihapus) -->
<div class="token-box">
  <span>Token Reset</span>
  <code><?= htmlspecialchars($token) ?></code>
</div>

<div class="divider"></div>

<p class="message" style="font-size:13px">
  Jika tombol tidak berfungsi, salin URL berikut ke browser Anda:<br>
  <a href="<?= esc($link) ?>" style="color:#6366f1;font-size:12px;word-break:break-all;"><?= esc($link) ?></a>
</p>

<?= $this->endSection() ?>