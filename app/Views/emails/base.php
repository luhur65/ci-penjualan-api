<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= esc($subject ?? 'Email') ?></title>

  <style>
    body {
      margin: 0;
      padding: 0;
      background: #0f0f13;
      font-family: Arial, sans-serif;
    }

    .wrapper {
      max-width: 600px;
      margin: auto;
      padding: 20px;
    }

    .card {
      background: #1a1a24;
      border-radius: 12px;
      overflow: hidden;
      border: 1px solid #2e2e40;
    }

    .header {
      background: linear-gradient(135deg, #1e1b4b, #312e81);
      padding: 30px;
      text-align: center;
      color: #fff;
    }

    .header h1 {
      margin: 0;
      font-size: 24px;
    }

    .body {
      padding: 30px;
      color: #cfcfe6;
      font-size: 14px;
      line-height: 1.6;
    }

    .footer {
      padding: 20px;
      text-align: center;
      font-size: 12px;
      color: #666;
    }

    .btn {
      display: inline-block;
      padding: 12px 20px;
      background: #6366f1;
      color: #fff !important;
      text-decoration: none;
      border-radius: 6px;
      margin-top: 20px;
    }
  </style>
</head>

<body>
  <div class="wrapper">
    <div class="card">

      <!-- HEADER -->
      <div class="header">
        <h1><?= esc($title ?? 'Notifikasi') ?></h1>
      </div>

      <!-- BODY -->
      <div class="body">
        <?= $this->renderSection('content') ?>
      </div>

      <!-- FOOTER -->
      <div class="footer">
        © <?= date('Y') ?> <?= esc($appName ?? 'Sistem') ?><br>
        Jika ini bukan Anda, abaikan email ini.
      </div>

    </div>
  </div>
</body>

</html>