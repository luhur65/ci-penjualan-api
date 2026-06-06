<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= esc($subject ?? 'Email') ?></title>

  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">

  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

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
      bottom: -1px;
      left: 0;
      right: 0;
      height: 40px;
      background: #1a1a24;
      clip-path: ellipse(60% 100% at 50% 100%);
    }

    .hero .icon-wrap {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 64px;
      height: 64px;
      background: rgba(255, 255, 255, 0.12);
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
      color: rgba(255, 255, 255, 0.55);
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
    <div class="card">

      <!-- HERO -->
      <div class="hero">
        <h1><?= esc($title ?? 'Notifikasi') ?></h1>
        <p><?= esc($subtitle ?? '') ?></p>
      </div>

      <!-- BODY -->
      <div class="body-content">

        <?php if (!empty($username)) : ?>
          <p class="greeting">
            Halo, <strong><?= esc($username) ?></strong> 👋
          </p>
        <?php endif; ?>

        <?= $this->renderSection('content') ?>

        <div class="divider"></div>

      </div>

      <!-- FOOTER -->
      <div class="footer">
        <p>
          Jika ini bukan Anda, abaikan email ini.
        </p>
        <div class="divider"></div>
        <p>
          &copy; <?= date('Y') ?> <?= esc($appName ?? 'Sistem') ?>
        </p>
      </div>

    </div>
  </div>
</body>

</html>