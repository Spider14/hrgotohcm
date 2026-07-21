<?php
/**
 * @var array $jobs
 * @var array $company
 * @var string $pageTitle
 */
$appUrl = \App\Helpers\Security::escape($_ENV['APP_URL'] ?? '');
$brand = \App\Helpers\Security::escape($company['short_name'] . ' Careers');
$tagline = \App\Helpers\Security::escape($company['tagline']);

function timeAgo(string $date): string {
    $now = new DateTime;
    $dt = new DateTime($date);
    $diff = $now->getTimestamp() - $dt->getTimestamp();
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' minute' . (floor($diff / 60) > 1 ? 's' : '') . ' ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hour' . (floor($diff / 3600) > 1 ? 's' : '') . ' ago';
    if ($diff < 604800) return floor($diff / 86400) . ' day' . (floor($diff / 86400) > 1 ? 's' : '') . ' ago';
    if ($diff < 2592000) return floor($diff / 604800) . ' week' . (floor($diff / 604800) > 1 ? 's' : '') . ' ago';
    if ($diff < 31536000) return floor($diff / 2592000) . ' month' . (floor($diff / 2592000) > 1 ? 's' : '') . ' ago';
    return floor($diff / 31536000) . ' year' . (floor($diff / 31536000) > 1 ? 's' : '') . ' ago';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo \App\Helpers\Security::escape($pageTitle); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root {
      --primary-navy: #0F1E36;
      --accent-blue: #3B82F6;
      --accent-green: #22C55E;
      --text-main: #1E293B;
      --text-muted: #64748B;
      --bg-light: #F8FAFC;
      --card-border: rgba(15, 30, 54, 0.12);
      --max-width: 1200px;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--bg-light);
      color: var(--text-main);
      line-height: 1.5;
      -webkit-font-smoothing: antialiased;
    }

    .navbar {
      background: var(--primary-navy);
      border-bottom: 4px solid var(--primary-navy);
      padding: 16px 24px;
      position: sticky;
      top: 0;
      z-index: 100;
    }

    .nav-container {
      max-width: var(--max-width);
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .brand { display: flex; flex-direction: column; }

    .brand-name {
      color: #ffffff;
      font-size: 1.5rem;
      font-weight: 800;
      letter-spacing: -0.5px;
    }

    .brand-tagline {
      color: rgba(255,255,255,0.7);
      font-size: 0.55rem;
      font-weight: 600;
      letter-spacing: 1px;
    }

    .nav-links { display: flex; align-items: center; gap: 16px; }

    .nav-back-btn {
      color: rgba(255,255,255,0.85);
      text-decoration: none;
      font-size: 0.875rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: color 0.2s;
    }

    .nav-back-btn:hover { color: #ffffff; }

    .nav-login-btn {
      color: rgba(255,255,255,0.85);
      text-decoration: none;
      font-size: 0.8rem;
      font-weight: 600;
      padding: 6px 14px;
      border: 1px solid rgba(255,255,255,0.2);
      border-radius: 8px;
      transition: all 0.2s;
    }

    .nav-login-btn:hover { background: rgba(255,255,255,0.1); color: #ffffff; }

    .hero-section {
      background: linear-gradient(135deg, #E0F2FE 0%, #DCFCE7 100%);
      padding: 60px 24px;
      text-align: center;
      position: relative;
      overflow: hidden;
      border-bottom: 1px solid var(--card-border);
    }

    .hero-section::before, .hero-section::after {
      content: '';
      position: absolute;
      width: 300px;
      height: 300px;
      border: 3px dashed rgba(15, 30, 54, 0.03);
      border-radius: 50%;
      pointer-events: none;
    }
    .hero-section::before { top: -100px; left: -50px; }
    .hero-section::after { bottom: -100px; right: -50px; }

    .hero-content {
      max-width: 700px;
      margin: 0 auto;
      position: relative;
      z-index: 2;
    }

    .hero-title {
      color: var(--primary-navy);
      font-size: 2.5rem;
      font-weight: 800;
      letter-spacing: -1px;
      margin-bottom: 12px;
    }

    .hero-subtitle {
      color: #475569;
      font-size: 1.125rem;
      font-weight: 400;
      margin-bottom: 32px;
    }

    .filter-panel {
      background: #ffffff;
      padding: 16px;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(15, 30, 54, 0.06);
      border: 1px solid var(--card-border);
      display: grid;
      grid-template-columns: 2fr 1fr 1fr auto;
      gap: 12px;
      align-items: center;
    }

    .input-group { position: relative; width: 100%; }

    .filter-input, .filter-select {
      width: 100%;
      padding: 12px 16px;
      border: 1px solid rgba(15, 30, 54, 0.15);
      border-radius: 8px;
      font-size: 0.9rem;
      font-family: inherit;
      color: var(--text-main);
      background-color: #ffffff;
      outline: none;
      transition: border-color 0.2s;
    }

    .filter-input:focus, .filter-select:focus { border-color: var(--accent-blue); }

    .btn-search {
      background-color: var(--primary-navy);
      color: #ffffff;
      border: none;
      padding: 12px 28px;
      border-radius: 8px;
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      transition: background-color 0.2s;
    }

    .btn-search:hover { background-color: #1a3256; }

    .main-layout {
      max-width: var(--max-width);
      margin: 40px auto;
      padding: 0 24px;
      display: grid;
      grid-template-columns: 3fr 1fr;
      gap: 32px;
    }

    .jobs-feed-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .jobs-count {
      font-size: 0.875rem;
      color: var(--text-muted);
      font-weight: 500;
    }

    .jobs-count strong { color: var(--text-main); }

    .listings-container {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .job-card {
      background: #ffffff;
      border: 1px solid #d1d5db;
      border-radius: 12px;
      padding: 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      transition: transform 0.2s, box-shadow 0.2s;
      text-decoration: none;
      color: inherit;
      cursor: pointer;
      box-shadow: 0 2px 8px rgba(15, 30, 54, 0.06);
    }

    .job-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 28px -8px rgba(15, 30, 54, 0.15);
      border-color: var(--accent-blue);
    }

    .job-details-primary {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .job-tags { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }

    .tag {
      font-size: 0.7rem;
      font-weight: 600;
      padding: 4px 10px;
      border-radius: 6px;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }

    .tag-dept { background: #EEF2F6; color: var(--text-muted); }
    .tag-type { background: rgba(34, 197, 94, 0.1); color: var(--accent-green); }
    .tag-loc { background: rgba(59, 130, 246, 0.1); color: var(--accent-blue); }
    .tag-deadline { background: rgba(245, 158, 11, 0.1); color: #d97706; }

    .job-title {
      color: var(--primary-navy);
      font-size: 1.25rem;
      font-weight: 700;
      letter-spacing: -0.2px;
    }

    .job-meta-secondary {
      display: flex;
      gap: 16px;
      font-size: 0.825rem;
      color: var(--text-muted);
      flex-wrap: wrap;
    }

    .meta-item { display: flex; align-items: center; gap: 4px; }

    .job-action-zone { display: flex; align-items: center; }

    .btn-apply-outline {
      border: 1px solid rgba(15, 30, 54, 0.2);
      color: var(--primary-navy);
      padding: 10px 20px;
      border-radius: 8px;
      font-size: 0.875rem;
      font-weight: 600;
      transition: all 0.2s;
      background: transparent;
      cursor: pointer;
    }

    .job-card:hover .btn-apply-outline {
      background-color: var(--primary-navy);
      color: #ffffff;
      border-color: var(--primary-navy);
    }

    .sidebar { display: flex; flex-direction: column; gap: 24px; }

    .widget-box {
      background: #ffffff;
      border: 1px solid var(--card-border);
      border-radius: 12px;
      padding: 20px;
    }

    .widget-title {
      font-size: 0.95rem;
      font-weight: 700;
      color: var(--primary-navy);
      margin-bottom: 12px;
      letter-spacing: -0.1px;
    }

    .widget-text {
      font-size: 0.825rem;
      color: var(--text-muted);
      margin-bottom: 16px;
      line-height: 1.4;
    }

    .btn-widget-action {
      display: block;
      width: 100%;
      text-align: center;
      background-color: #EEF2F6;
      color: var(--primary-navy);
      text-decoration: none;
      padding: 10px;
      border-radius: 6px;
      font-size: 0.825rem;
      font-weight: 600;
      transition: background-color 0.2s;
    }

    .btn-widget-action:hover { background-color: #E2E8F0; }

    .empty-state {
      background: #ffffff;
      border: 1px solid var(--card-border);
      border-radius: 12px;
      padding: 60px 40px;
      text-align: center;
    }

    .empty-state .empty-icon { font-size: 2.5rem; margin-bottom: 12px; }

    .footer {
      background: var(--primary-navy);
      text-align: center;
      padding: 28px 24px;
      margin-top: 60px;
    }

    .footer-copy {
      color: rgba(255,255,255,0.85);
      font-size: 0.85rem;
      font-weight: 500;
    }

    .footer-powered {
      color: rgba(255,255,255,0.6);
      font-size: 0.65rem;
      font-weight: 600;
      letter-spacing: 1px;
      margin-top: 4px;
    }

    @media (max-width: 992px) {
      .main-layout { grid-template-columns: 1fr; }
      .sidebar { order: 2; }
    }

    @media (max-width: 768px) {
      .hero-title { font-size: 1.85rem; }
      .filter-panel { grid-template-columns: 1fr; }
      .job-card { flex-direction: column; align-items: flex-start; gap: 16px; }
      .job-action-zone { width: 100%; }
      .btn-apply-outline { width: 100%; text-align: center; }
    }
  </style>
</head>
<body>

  <nav class="navbar">
    <div class="nav-container">
      <div class="brand">
        <span class="brand-name"><?php echo $brand; ?></span>
        <span class="brand-tagline">POWERED BY NORGENCE</span>
      </div>
      <div class="nav-links">
        <a href="<?php echo $appUrl; ?>" class="nav-back-btn">&larr; Portal Dashboard</a>
        <a href="<?php echo $appUrl; ?>" class="nav-login-btn">Staff Login</a>
      </div>
    </div>
  </nav>

  <header class="hero-section">
    <div class="hero-content">
      <h1 class="hero-title">Build the Future of Capital Management</h1>
      <p class="hero-subtitle"><?php echo $tagline ?: 'Explore rewarding dynamic open opportunities within our scaling ecosystem divisions.'; ?></p>

      <div class="filter-panel">
        <div class="input-group">
          <input type="text" class="filter-input" placeholder="Search keywords (e.g., Engineer)...">
        </div>
        <div class="input-group">
          <select class="filter-select">
            <option value="">All Departments</option>
            <option value="human-capital">Human Capital</option>
            <option value="engineering">Engineering</option>
            <option value="finance">Finance & Payroll</option>
          </select>
        </div>
        <div class="input-group">
          <select class="filter-select">
            <option value="">All Locations</option>
            <option value="accra">Accra, GH</option>
            <option value="remote">Remote Workers</option>
            <option value="tamale">Tamale, GH</option>
          </select>
        </div>
        <button class="btn-search">Find Openings</button>
      </div>
    </div>
  </header>

  <main class="main-layout">

    <section class="listings-feed-wrapper">
      <div class="jobs-feed-header">
        <h2 class="jobs-count">Showing <strong><?php echo count($jobs); ?></strong> Available Opportunit<?php echo count($jobs) === 1 ? 'y' : 'ies'; ?></h2>
      </div>

      <div class="listings-container">
        <?php if (empty($jobs)): ?>
          <div class="empty-state">
            <div class="empty-icon">🔍</div>
            <h4 class="fw-bold mb-2" style="color:var(--primary-navy);">No Open Positions</h4>
            <p style="font-size:0.875rem;color:var(--text-muted);">There are no active job openings at this time. Please check back later or submit a general application.</p>
          </div>
        <?php else: ?>
          <?php foreach ($jobs as $j): ?>
          <div class="job-card" onclick="location.href='/apply/submit?job_id=<?php echo (int)$j['id']; ?>'">
            <div class="job-details-primary">
              <div class="job-tags">
                <span class="tag tag-dept"><?php echo \App\Helpers\Security::escape($j['department'] ?? 'General'); ?></span>
                <span class="tag tag-type"><?php echo \App\Helpers\Security::escape($j['type'] ?? 'Full-time'); ?></span>
                <?php if (!empty($j['location'])): ?>
                <span class="tag tag-loc"><?php echo \App\Helpers\Security::escape($j['location']); ?></span>
                <?php endif; ?>
                <?php if ($j['deadline']): ?>
                <span class="tag tag-deadline">Closes <?php echo date('d M', strtotime($j['deadline'])); ?></span>
                <?php endif; ?>
              </div>
              <h3 class="job-title"><?php echo \App\Helpers\Security::escape($j['title']); ?></h3>
              <div class="job-meta-secondary">
                <?php if (!empty($j['department'])): ?>
                <span class="meta-item">📁 Category: <?php echo \App\Helpers\Security::escape($j['department']); ?></span>
                <?php endif; ?>
                <?php if (!empty($j['salary_range'])): ?>
                <span class="meta-item">💰 <?php echo \App\Helpers\Security::escape($j['salary_range']); ?></span>
                <?php endif; ?>
                <?php if (!empty($j['created_at'])): ?>
                <span class="meta-item">🕒 Posted <?php echo timeAgo($j['created_at']); ?></span>
                <?php endif; ?>
              </div>
            </div>
            <div class="job-action-zone">
              <span class="btn-apply-outline">Apply Now</span>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

    <aside class="sidebar">

      <div class="widget-box">
        <h4 class="widget-title">Spontaneous Application</h4>
        <p class="widget-text">Don't see an explicit open vacancy matching your profile? Send your CV directly to our general talent acquisition pool.</p>
        <a href="#" class="btn-widget-action">Submit General Application</a>
      </div>

      <div class="widget-box">
        <h4 class="widget-title">Our Benefits</h4>
        <p class="widget-text">We provide comprehensive health insurance, agile remote flexibility parameters, continuous professional education stipends, and performance allocations.</p>
      </div>

    </aside>

  </main>

  <footer class="footer">
    <div class="footer-copy">&copy; <?php echo date('Y'); ?> HRGoTo HCM</div>
    <div class="footer-powered">Powered By Norgence</div>
  </footer>

</body>
</html>
