<?php

namespace App\Libraries;

class EmailSender
{
  protected $email;
  protected $from = 'dharmataspusat@gmail.com';
  protected $fromName = 'Admin Sistem';
  protected $subject = 'Test Email';
  protected $link = '/reset-password';
  protected $type = 'html';

  public function __construct()
  {
    // Memuat service email CI4
    $this->email = \Config\Services::email();
  }

  // Fungsi untuk mengirim email dengan HTML
  public function sendEmail($to, $subject = null, $link = null, $token = null, $username = null)
  {
    try {

      // Pengaturan default dari pengirim email
      $this->email->setFrom($this->from, $this->fromName);
      $this->email->setTo($to);
      $this->email->setSubject($subject ?? $this->subject);
      $this->email->setMessage($this->generateHTML($link ?? $this->link, $subject ?? $this->subject, $token, $username)); // Menggunakan fungsi generateHTML untuk menambah styling

      // Mengatur jenis email untuk HTML
      $this->email->setMailType($this->type);

      if ($this->email->send()) {
        return true; // Email berhasil dikirim
      } else {
        return false; // Jika email gagal dikirim
      }
    } catch (\Throwable $th) {
      log_message('error', 'Email failed: ' . $th->getMessage());
      return false; // Menangani exception dan mengembalikan false jika gagal
    }
  }

  /**
   * Cara pakai:
   * generateHTML($link, $subject, $token, $username)
   *
   * Link reset akan otomatis dibuat di dalam fungsi:
   * https://myapp.com/reset-password?token=abc123token&user=namauser
   */

  private function generateHTML($link, $subject, $token, $username)
  {
      return '
      <!DOCTYPE html>
      <html lang="id">
      <head>
          <meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <title>' . htmlspecialchars($subject) . '</title>
          <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
          <style>
              * { box-sizing: border-box; margin: 0; padding: 0; }

              body {
                  background-color: #0f0f13;
                  font-family: "DM Sans", sans-serif;
                  padding: 40px 16px;
              }

              .wrapper {
                  max-width: 560px;
                  margin: 0 auto;
              }

              /* ── TOP LOGO AREA ── */
              .logo-area {
                  text-align: center;
                  margin-bottom: 24px;
              }
              .logo-area .badge {
                  display: inline-block;
                  background: linear-gradient(135deg, #e8c97e, #c9993a);
                  color: #0f0f13;
                  font-family: "DM Serif Display", serif;
                  font-size: 18px;
                  letter-spacing: 2px;
                  padding: 8px 22px;
                  border-radius: 4px;
              }

              /* ── CARD ── */
              .card {
                  background: #1a1a24;
                  border: 1px solid #2e2e40;
                  border-radius: 16px;
                  overflow: hidden;
              }

              /* ── HERO BANNER ── */
              .hero {
                  background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #1e1b4b 100%);
                  padding: 48px 40px 40px;
                  text-align: center;
                  position: relative;
              }
              .hero::after {
                  content: "";
                  position: absolute;
                  bottom: -1px; left: 0; right: 0;
                  height: 40px;
                  background: #1a1a24;
                  clip-path: ellipse(60% 100% at 50% 100%);
              }
              .hero .icon-wrap {
                  display: inline-flex;
                  align-items: center;
                  justify-content: center;
                  width: 64px; height: 64px;
                  background: rgba(255,255,255,0.12);
                  border-radius: 50%;
                  margin-bottom: 20px;
                  font-size: 28px;
              }
              .hero h1 {
                  font-family: "DM Serif Display", serif;
                  font-size: 28px;
                  color: #ffffff;
                  line-height: 1.2;
              }
              .hero p {
                  margin-top: 8px;
                  font-size: 14px;
                  color: rgba(255,255,255,0.55);
                  letter-spacing: 0.5px;
              }

              /* ── BODY CONTENT ── */
              .body-content {
                  padding: 40px 40px 32px;
              }
              .greeting {
                  font-size: 15px;
                  color: #a0a0b8;
                  margin-bottom: 16px;
              }
              .greeting strong {
                  color: #e0e0f0;
              }
              .message {
                  font-size: 15px;
                  line-height: 1.7;
                  color: #7e7e9a;
              }

              /* ── CTA BUTTON ── */
              .cta-wrap {
                  margin: 32px 0;
                  text-align: center;
              }
              .btn-reset {
                  display: inline-block;
                  background: linear-gradient(135deg, #6366f1, #818cf8);
                  color: #ffffff !important;
                  text-decoration: none;
                  font-size: 15px;
                  font-weight: 600;
                  letter-spacing: 0.5px;
                  padding: 14px 36px;
                  border-radius: 50px;
                  box-shadow: 0 8px 24px rgba(99, 102, 241, 0.4);
                  transition: opacity .2s;
              }

              /* ── TOKEN BOX ── */
              .token-box {
                  background: #12121a;
                  border: 1px dashed #2e2e50;
                  border-radius: 10px;
                  padding: 14px 20px;
                  margin-top: 20px;
                  font-size: 12px;
                  color: #555570;
              }
              .token-box span {
                  display: block;
                  font-size: 11px;
                  color: #40405a;
                  margin-bottom: 4px;
                  text-transform: uppercase;
                  letter-spacing: 1px;
              }
              .token-box code {
                  color: #7c7caa;
                  word-break: break-all;
                  font-size: 12px;
              }

              /* ── EXPIRY WARNING ── */
              .expiry {
                  display: flex;
                  align-items: center;
                  gap: 10px;
                  background: rgba(234, 179, 8, 0.07);
                  border-left: 3px solid #eab308;
                  border-radius: 0 8px 8px 0;
                  padding: 12px 16px;
                  margin-top: 24px;
                  font-size: 13px;
                  color: #a08820;
              }

              /* ── DIVIDER ── */
              .divider {
                  height: 1px;
                  background: linear-gradient(to right, transparent, #2e2e40, transparent);
                  margin: 28px 0;
              }

              /* ── FOOTER ── */
              .footer {
                  padding: 0 40px 36px;
                  font-size: 12px;
                  color: #44445a;
                  line-height: 1.7;
                  text-align: center;
              }
              .footer a {
                  color: #6366f1;
                  text-decoration: none;
              }
          </style>
      </head>
      <body>
      <div class="wrapper">

          <!-- Logo -->
          <!-- <div class="logo-area">
              <span class="badge">MY APP</span>
          </div> -->

          <div class="card">

              <!-- Hero -->
              <div class="hero">
                  <!-- <div class="icon-wrap">🔐</div> -->
                  <h1>Reset Password</h1>
                  <p>Permintaan reset password diterima</p>
              </div>

              <!-- Body -->
              <div class="body-content">

                  <p class="greeting">Halo, <strong>' . htmlspecialchars($username) . '</strong> 👋</p>

                  <p class="message">
                      Kami menerima permintaan untuk mereset password akun Anda.
                      Klik tombol di bawah untuk membuat password baru. Link ini hanya berlaku selama <strong style="color:#e0e0f0">30 menit</strong>.
                  </p>

                  <!-- CTA -->
                  <div class="cta-wrap">
                      <a href="' . $link . '" class="btn-reset">Buat Password Baru &rarr;</a>
                  </div>

                  <!-- Expiry notice -->
                  <div class="expiry">
                      ⏱ &nbsp;Link akan kedaluwarsa dalam <strong>&nbsp;30 menit</strong>&nbsp; sejak email ini dikirim.
                  </div>

                  <!-- Token box (opsional, bisa dihapus) -->
                  <div class="token-box">
                      <span>Token Reset</span>
                      <code>' . htmlspecialchars($token) . '</code>
                  </div>

                  <div class="divider"></div>

                  <p class="message" style="font-size:13px">
                      Jika tombol tidak berfungsi, salin URL berikut ke browser Anda:<br>
                      <a href="' . $link . '" style="color:#6366f1;font-size:12px;word-break:break-all;">' . $link . '</a>
                  </p>

              </div>

              <!-- Footer -->
              <div class="footer">
                  <p>Jika Anda <strong>tidak meminta</strong> reset password, abaikan email ini.<br>
                  Password Anda tidak akan berubah.</p>
                  <div class="divider"></div>
                  <p>&copy; ' . date('Y') . ' ' . $this->fromName . ' &bull; <a href="#">Kebijakan Privasi</a> &bull; <a href="#">Hubungi Kami</a></p>
              </div>

          </div>
      </div>
      </body>
      </html>';
  }
}
