<?php
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$brand = \App\Helpers\Security::escape($company['short_name'] . ' Careers');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo \App\Helpers\Security::escape($pageTitle); ?></title>
  <link rel="icon" type="image/svg+xml" href="<?php echo $appUrl; ?>/assets/favicons/favicon.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary-navy: #0F1E36;
      --accent-blue: #3B82F6;
      --accent-green: #22C55E;
      --text-main: #1E293B;
      --text-muted: #64748B;
      --bg-light: #F8FAFC;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--bg-light);
      color: var(--text-main);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      -webkit-font-smoothing: antialiased;
    }
    .navbar {
      background: var(--primary-navy);
      border-bottom: 4px solid var(--primary-navy);
      padding: 16px 24px;
    }
    .nav-container { max-width: 900px; margin: 0 auto; display: flex; align-items: center; }
    .brand-name { color: #fff; font-size: 1.5rem; font-weight: 800; letter-spacing: -0.5px; }
    .brand-tagline { color: rgba(255,255,255,0.7); font-size: 0.55rem; font-weight: 600; letter-spacing: 1px; }
    .main { flex: 1; display: flex; align-items: center; justify-content: center; padding: 60px 20px; }
    .success-card {
      background: #fff;
      border: 1px solid #d1d5db;
      border-radius: 20px;
      padding: 60px 40px 50px;
      max-width: 540px;
      width: 100%;
      text-align: center;
      box-shadow: 0 8px 28px rgba(15,30,54,0.08);
    }
    .check-icon { width: 80px; height: 80px; background: var(--accent-green); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; font-size: 2rem; color: #fff; }
    .success-card h2 { font-weight: 800; font-size: 1.6rem; color: var(--primary-navy); }
    .success-card p { color: var(--text-muted); font-size: 0.95rem; max-width: 380px; margin: 8px auto 28px; }
    .ref-box { background: var(--bg-light); border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px 24px; display: inline-flex; align-items: center; gap: 10px; }
    .ref-box label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; font-weight: 600; }
    .ref-box .ref-value { font-weight: 800; font-size: 1.25rem; color: var(--primary-navy); letter-spacing: 1px; }
    .ref-box .copy-btn { background: none; border: 1px solid #d1d5db; border-radius: 8px; padding: 4px 14px; font-size: 0.75rem; font-weight: 600; color: var(--accent-blue); cursor: pointer; transition: all 0.2s; }
    .ref-box .copy-btn:hover { background: var(--accent-blue); color: #fff; border-color: var(--accent-blue); }
    .btn-done { background: var(--primary-navy); color: #fff; font-weight: 700; font-size: 0.9rem; padding: 14px 44px; border-radius: 12px; text-decoration: none; display: inline-block; transition: all 0.2s; margin-top: 28px; }
    .btn-done:hover { background: #1a3256; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(15,30,54,0.15); }
    .footer { background: var(--primary-navy); text-align: center; padding: 28px 24px; }
    .footer-copy { color: rgba(255,255,255,0.85); font-size: 0.85rem; font-weight: 500; }
    .footer-powered { color: rgba(255,255,255,0.6); font-size: 0.65rem; font-weight: 600; letter-spacing: 1px; margin-top: 4px; }
  </style>
</head>
<body>

  <nav class="navbar">
    <div class="nav-container">
      <div>
        <span class="brand-name"><?php echo $brand; ?></span>
        <div class="brand-tagline">POWERED BY NORGENCE</div>
      </div>
    </div>
  </nav>

  <div class="main">
    <div class="success-card">
      <div class="check-icon"><i class="fas fa-check"></i></div>
      <h2>Application Submitted!</h2>
      <p>Thank you for applying. Your application has been received successfully and will be reviewed.</p>
      <div class="ref-box">
        <div>
          <label>Reference Number</label>
          <div class="ref-value"><?php echo \App\Helpers\Security::escape($refNum); ?></div>
        </div>
        <button class="copy-btn" onclick="copyRef()"><i class="fas fa-copy me-1"></i>Copy</button>
      </div>
      <br>
      <a href="/apply" class="btn-done"><i class="fas fa-arrow-left me-2"></i>Back to Jobs</a>
    </div>
  </div>

  <footer class="footer">
    <div class="footer-copy">&copy; <?php echo date('Y'); ?> HRGoTo HCM</div>
    <div class="footer-powered">Powered By Norgence</div>
  </footer>

<script>
function copyRef() {
  const ref = '<?php echo \App\Helpers\Security::escape($refNum); ?>';
  navigator.clipboard.writeText(ref).then(() => {
    const btn = document.querySelector('.copy-btn');
    btn.innerHTML = '<i class="fas fa-check me-1"></i>Copied';
    btn.style.borderColor = '#22c55e';
    btn.style.color = '#22c55e';
    setTimeout(() => {
      btn.innerHTML = '<i class="fas fa-copy me-1"></i>Copy';
      btn.style.borderColor = '#d1d5db';
      btn.style.color = '#3b82f6';
    }, 2000);
  });
}
</script>
</body>
</html>
