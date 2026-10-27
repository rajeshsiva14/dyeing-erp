<?php
/**
 * Dyeing ERP — Modern Light Theme Login Screen with Enterprise Launch Animation
 * File: login/index.php
 * Strictly matches Dyeing ERP design specifications:
 * - Sharp, vivid textile background (lavender, blue rolls & water ripples visible without heavy haze)
 * - Cohesive blue & muted cyan family for feature cards
 * - Polished white login card with balanced spacing and standard 48px heights
 * - Unified white quick-access buttons with light-blue hover
 * - High contrast accessibility & smooth responsive stacking
 * - Engaging, high-end Workspace Launch animation sequence upon successful authentication
 */

use Core\Support\Url;
use Core\Security\SessionManager;

SessionManager::start();

// Handle login post
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
           || (isset($_POST['ajax']) && $_POST['ajax'] === '1') 
           || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $user = \Core\Database\Database::fetch("SELECT * FROM users WHERE username = ? OR email = ?", [$username, $username]);
    if ($user && $user['status'] === 'active') {
        if (password_verify($password, $user['password_hash'])) {
            SessionManager::setUser([
                'id'          => $user['id'],
                'company_id'  => $user['company_id'],
                'branch_id'   => $user['branch_id'],
                'username'    => $user['username'],
                'full_name'   => $user['full_name'],
                'email'       => $user['email'],
                'role'        => $user['role'],
                'designation' => $user['designation'],
                'initials'    => strtoupper(substr($user['full_name'], 0, 2)),
            ]);

            // If AJAX request, return structured success payload for smooth in-card animation
            if ($isAjax) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode([
                    'success'   => true,
                    'redirect'  => Url::to('/dashboard'),
                    'user'      => [
                        'name'        => $user['full_name'],
                        'role'        => ucfirst($user['role']),
                        'designation' => $user['designation'] ?? 'Plant Executive',
                        'initials'    => strtoupper(substr($user['full_name'], 0, 2)),
                    ]
                ]);
                exit;
            }

            header('Location: ' . Url::to('/dashboard'));
            exit;
        }
    }

    $error = 'Invalid username or password. Please verify your credentials.';
    if ($isAjax) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => $error
        ]);
        exit;
    }
}

$baseUrl = Url::base();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dyeing ERP — Colouring Possibilities. Delivering Excellence.</title>
  
  <!-- Favicon: Dyeing ERP Droplet Logo (Multi-size) -->
  <link rel="icon" type="image/png" sizes="32x32" href="<?= $baseUrl ?>/assets/img/favicon-32x32.png?v=<?= filemtime(__DIR__ . '/../assets/img/favicon-32x32.png') ?>">
  <link rel="icon" type="image/png" sizes="16x16" href="<?= $baseUrl ?>/assets/img/favicon-16x16.png?v=<?= filemtime(__DIR__ . '/../assets/img/favicon-16x16.png') ?>">
  <link rel="icon" type="image/png" sizes="64x64" href="<?= $baseUrl ?>/assets/img/favicon.png?v=<?= filemtime(__DIR__ . '/../assets/img/favicon.png') ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= $baseUrl ?>/assets/img/apple-touch-icon.png?v=<?= filemtime(__DIR__ . '/../assets/img/apple-touch-icon.png') ?>">
  <link rel="shortcut icon" href="<?= $baseUrl ?>/assets/img/favicon.ico?v=<?= filemtime(__DIR__ . '/../assets/img/favicon.ico') ?>">

  <!-- Typography: Inter Font -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Bootstrap 5 & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <script src="https://unpkg.com/lucide@latest"></script>

  <style>
    :root {
      /* Exact Professional Color Palette */
      --cz-bg-page: #F5F8FC;
      --cz-white: #FFFFFF;
      --cz-primary-blue: #2878E8;
      --cz-primary-hover: #1D65CE;
      --cz-navy: #183153;
      --cz-text-muted: #64748B;
      --cz-border: #DFE7F0;
      --cz-light-blue: #EAF3FF;
      --cz-muted-cyan: #DFF4FA;
      --cz-input-h: 48px;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      background-color: var(--cz-bg-page);
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      color: var(--cz-navy);
      min-height: 100vh;
      overflow-x: hidden;
      display: flex;
      flex-direction: column;
    }

    /* Full-screen wrapper */
    .dyeing-login-wrapper {
      min-height: 100vh;
      display: flex;
      position: relative;
      background: var(--cz-bg-page);
    }

    /* -------------------------------------------------------------
     * 1. Left Section: Sharp Textile Background & Readability Gradient
     * ------------------------------------------------------------- */
    .hero-section {
      flex: 1 1 58%;
      position: relative;
      background-image: url('<?= $baseUrl ?>/assets/img/dyeing-login-bg.jpg?v=<?= filemtime(__DIR__ . '/../assets/img/dyeing-login-bg.jpg') ?>');
      background-size: cover;
      background-position: right 18% center;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 42px 48px 32px;
      overflow: hidden;
      image-rendering: -webkit-optimize-contrast;
    }

    /* Subtle white gradient ONLY behind the left text; 
       Leaves blue & lavender fabric rolls, machinery, and water ripples 100% sharp and visible! */
    .hero-section::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 58%;
      bottom: 0;
      background: linear-gradient(90deg, rgba(245, 248, 252, 0.95) 0%, rgba(245, 248, 252, 0.78) 45%, rgba(245, 248, 252, 0.18) 80%, transparent 100%);
      z-index: 1;
      pointer-events: none;
    }

    .hero-content {
      position: relative;
      z-index: 2;
      max-width: 540px;
    }

    /* Brand Logo Header */
    .hero-brand {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 22px;
    }

    .hero-brand-logo {
      height: 48px;
      width: auto;
      object-fit: contain;
    }

    .hero-brand-tagline {
      font-size: 0.82rem;
      font-weight: 500;
      color: var(--cz-text-muted);
      margin-top: 4px;
    }

    /* Main Headline */
    .hero-headline {
      font-size: 2.75rem;
      font-weight: 800;
      letter-spacing: -0.03em;
      line-height: 1.15;
      color: var(--cz-navy);
      margin-bottom: 12px;
    }

    .hero-headline .text-blue {
      color: var(--cz-primary-blue);
      display: block;
    }

    .hero-desc {
      font-size: 0.95rem;
      color: #475569;
      max-width: 480px;
      line-height: 1.5;
      margin-bottom: 24px;
    }

    /* -------------------------------------------------------------
     * 2. Feature Tiles: Unified Blue & Muted Cyan Palette (Classy & Consistent)
     * ------------------------------------------------------------- */
    .feature-tiles-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 10px;
      max-width: 520px;
      margin-bottom: 24px;
    }

    .feature-tile {
      background: #FFFFFF;
      border: 1px solid var(--cz-border);
      border-radius: 12px;
      padding: 12px 8px;
      min-height: 84px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      gap: 8px;
      box-shadow: 0 2px 6px rgba(24, 49, 83, 0.04);
      transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
      cursor: default;
    }

    .feature-tile:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 14px rgba(40, 120, 232, 0.12);
      border-color: #BFDBFE;
      background: #F8FAFD;
    }

    /* Standardised Icon Box in unified blue / cyan family */
    .tile-icon-box {
      width: 36px;
      height: 36px;
      border-radius: 9px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--cz-light-blue);
      color: var(--cz-primary-blue);
      transition: all 0.2s ease;
    }

    .feature-tile:hover .tile-icon-box {
      background: var(--cz-primary-blue);
      color: #FFFFFF;
    }

    .feature-tile-label {
      font-size: 0.74rem;
      font-weight: 600;
      color: var(--cz-navy);
      line-height: 1.25;
    }

    /* Alternate subtle cyan icon boxes for rhythm */
    .tile-alt .tile-icon-box {
      background: var(--cz-muted-cyan);
      color: #0284C7;
    }
    .tile-alt:hover .tile-icon-box {
      background: var(--cz-primary-blue);
      color: #FFFFFF;
    }

    /* -------------------------------------------------------------
     * Bottom Info Panel & Tagline
     * ------------------------------------------------------------- */
    .bottom-info-panel {
      background: #FFFFFF;
      border: 1px solid var(--cz-border);
      border-radius: 12px;
      padding: 10px 22px;
      display: inline-flex;
      align-items: center;
      gap: 24px;
      box-shadow: 0 2px 8px rgba(24, 49, 83, 0.04);
      margin-bottom: 18px;
      max-width: 520px;
    }

    .info-stat {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .info-stat-icon {
      font-size: 1.25rem;
      color: var(--cz-primary-blue);
    }

    .info-stat-val {
      font-size: 0.90rem;
      font-weight: 800;
      color: var(--cz-navy);
      line-height: 1.2;
    }

    .info-stat-lbl {
      font-size: 0.70rem;
      font-weight: 500;
      color: var(--cz-text-muted);
      line-height: 1.1;
    }

    .hero-footer-tagline {
      font-size: 0.82rem;
      font-weight: 600;
      color: #334155; /* Sharp high-contrast */
      display: flex;
      align-items: center;
      gap: 6px;
      position: relative;
      z-index: 2;
    }

    /* -------------------------------------------------------------
     * 3. Right Section: Polished White Login Card & Success Overlay
     * ------------------------------------------------------------- */
    .login-section {
      flex: 0 0 490px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 36px 30px;
      position: relative;
      background: var(--cz-bg-page);
      z-index: 5;
    }

    /* Language Selector Top Right */
    .top-lang-bar {
      position: absolute;
      top: 24px;
      right: 36px;
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 0.82rem;
      font-weight: 600;
      color: var(--cz-text-muted);
      cursor: pointer;
      padding: 4px 10px;
      border-radius: 6px;
      transition: background 0.15s ease;
    }

    .top-lang-bar:hover {
      background: #EAF0FA;
      color: var(--cz-navy);
    }

    /* Main White Login Card (Matching Image 1 Reference Exactly) */
    .login-card {
      width: 100%;
      max-width: 440px;
      background: #FFFFFF;
      border: 1.5px solid rgba(220, 235, 252, 0.95);
      border-radius: 26px;
      box-shadow: 0 16px 45px rgba(24, 49, 83, 0.08), 0 2px 6px rgba(0, 0, 0, 0.02);
      padding: 38px 32px 28px;
      position: relative;
      overflow: hidden;
      animation: cardFadeIn 0.35s ease-out;
    }

    /* Top-Right Corner Wave Artwork Matching Image 1 */
    .card-wave-corner {
      position: absolute;
      top: 0;
      right: 0;
      width: 165px;
      height: 100px;
      pointer-events: none;
      z-index: 1;
      border-top-right-radius: 26px;
      overflow: hidden;
    }

    .card-wave-corner svg {
      width: 100%;
      height: 100%;
      display: block;
    }

    @keyframes cardFadeIn {
      from {
        opacity: 0;
        transform: translateY(12px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Card Shake Animation on error */
    .shake-card {
      animation: shakeAnim 0.4s ease-in-out;
    }

    @keyframes shakeAnim {
      0%, 100% { transform: translateX(0); }
      20%, 60% { transform: translateX(-8px); }
      40%, 80% { transform: translateX(8px); }
    }

    /* Balanced Card Header */
    .card-brand-header {
      text-align: center;
      margin-bottom: 22px;
      position: relative;
      z-index: 2;
    }

    .card-brand-logo {
      width: 56px;
      height: 56px;
      margin-bottom: 8px;
      object-fit: contain;
    }

    .card-brand-title {
      font-size: 1.65rem;
      font-weight: 800;
      color: #0F172A;
      letter-spacing: -0.02em;
      margin-bottom: 4px;
      line-height: 1.15;
    }

    .card-brand-title span {
      color: var(--cz-primary-blue);
    }

    .card-brand-subtitle {
      font-size: 0.78rem;
      font-weight: 500;
      color: var(--cz-text-muted);
      margin-bottom: 12px;
    }

    /* Decorative Split Bar */
    .decorative-split-bar {
      display: flex;
      justify-content: center;
      gap: 5px;
      margin: 0 auto;
      width: 44px;
    }

    .decorative-split-bar .bar-1 {
      height: 3.5px;
      width: 22px;
      background: var(--cz-primary-blue);
      border-radius: 2px;
    }

    .decorative-split-bar .bar-2 {
      height: 3.5px;
      width: 17px;
      background: #93C5FD;
      border-radius: 2px;
    }

    /* Standardised Inputs and Alignment */
    .form-group-custom {
      margin-bottom: 16px;
      position: relative;
      z-index: 2;
    }

    .custom-label {
      font-size: 0.82rem;
      font-weight: 700;
      color: #1E293B;
      margin-bottom: 6px;
      display: block;
    }

    .custom-input-group {
      position: relative;
      display: flex;
      align-items: center;
    }

    .input-icon-left {
      position: absolute;
      left: 14px;
      color: #94A3B8;
      font-size: 1.1rem;
      pointer-events: none;
      display: flex;
      align-items: center;
    }

    .custom-input {
      width: 100%;
      height: 50px;
      padding-left: 44px;
      padding-right: 44px;
      border: 1.5px solid #DFE7F0;
      border-radius: 12px;
      font-size: 0.88rem;
      color: #1E293B;
      background: #FFFFFF;
      transition: all 0.15s ease;
    }

    .custom-input::placeholder {
      color: #94A3B8;
      font-size: 0.88rem;
    }

    .custom-input:focus {
      outline: none;
      border-color: var(--cz-primary-blue);
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .password-toggle-btn {
      position: absolute;
      right: 12px;
      background: none;
      border: none;
      color: #94A3B8;
      cursor: pointer;
      padding: 6px;
      font-size: 1.05rem;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: color 0.15s ease;
      border-radius: 6px;
    }

    .password-toggle-btn:focus-visible {
      outline: 2px solid var(--cz-primary-blue);
    }

    .password-toggle-btn:hover {
      color: #1E293B;
    }

    /* Remember me & Forgot Password */
    .login-options-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20px;
      font-size: 0.82rem;
      position: relative;
      z-index: 2;
    }

    .form-check-input {
      border-color: #CBD5E1;
      cursor: pointer;
    }

    .form-check-input:checked {
      background-color: var(--cz-primary-blue);
      border-color: var(--cz-primary-blue);
    }

    .form-check-label {
      cursor: pointer;
      color: #1E293B;
      font-weight: 500;
      user-select: none;
    }

    .forgot-link {
      color: var(--cz-primary-blue);
      text-decoration: none;
      font-weight: 600;
      font-size: 0.82rem;
      transition: color 0.15s ease;
    }

    .forgot-link:hover {
      color: var(--cz-primary-hover);
      text-decoration: underline;
    }

    /* Submit Button (Gradient matching Image 1) */
    .btn-submit-login {
      width: 100%;
      height: 50px;
      background: linear-gradient(180deg, #2D7AF6 0%, #1E68E2 100%);
      color: #FFFFFF;
      border: none;
      border-radius: 12px;
      font-size: 0.95rem;
      font-weight: 700;
      letter-spacing: -0.01em;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      box-shadow: 0 4px 14px rgba(37, 99, 235, 0.32);
      cursor: pointer;
      transition: all 0.18s ease;
      position: relative;
      z-index: 2;
    }

    .btn-submit-login:hover {
      background: linear-gradient(180deg, #2563EB 0%, #1A56C4 100%);
      box-shadow: 0 6px 18px rgba(37, 99, 235, 0.40);
      transform: translateY(-1px);
      color: #FFFFFF;
    }

    .btn-submit-login:active {
      transform: translateY(0);
    }

    .btn-submit-login:focus-visible {
      outline: 3px solid rgba(40, 120, 232, 0.35);
    }

    /* OR Divider */
    .divider-or {
      display: flex;
      align-items: center;
      text-align: center;
      margin: 18px 0 16px;
      color: #94A3B8;
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 0.05em;
      position: relative;
      z-index: 2;
    }

    .divider-or::before,
    .divider-or::after {
      content: '';
      flex: 1;
      border-bottom: 1px solid var(--cz-border);
    }

    .divider-or span {
      padding: 0 10px;
    }

    /* -------------------------------------------------------------
     * 4. Quick Access: 2-Line Cards with Chevron Matching Image 1
     * ------------------------------------------------------------- */
    .quick-access-header {
      margin-bottom: 12px;
      text-align: center;
      position: relative;
      z-index: 2;
    }

    .quick-access-title {
      font-size: 0.84rem;
      font-weight: 700;
      color: #1E293B;
      letter-spacing: -0.01em;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .quick-access-sub {
      font-size: 0.72rem;
      font-weight: 500;
      color: var(--cz-text-muted);
      margin-top: 2px;
    }

    .quick-access-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 10px;
      margin-bottom: 20px;
      position: relative;
      z-index: 2;
    }

    /* Rich 2-Line Cards Matching Image 1 */
    .quick-card-btn {
      background: #FFFFFF;
      border: 1px solid #DFE7F0;
      border-radius: 12px;
      padding: 10px 12px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
      width: 100%;
      height: 56px;
    }

    .quick-card-btn:hover {
      background: #F0F7FF;
      border-color: #BFDBFE;
      box-shadow: 0 4px 12px rgba(37, 99, 235, 0.08);
      transform: translateY(-1px);
    }

    .quick-card-btn:hover .quick-card-arrow {
      transform: translateX(2px);
      color: #2563EB;
    }

    .quick-card-icon-wrap {
      color: #2563EB;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .quick-card-name {
      font-size: 0.80rem;
      font-weight: 700;
      color: #1E293B;
      line-height: 1.2;
    }

    .quick-card-role {
      font-size: 0.68rem;
      font-weight: 500;
      color: #64748B;
      line-height: 1.2;
      margin-top: 2px;
    }

    .quick-card-arrow {
      font-size: 0.82rem;
      color: #2563EB;
      font-weight: 700;
      transition: transform 0.15s ease;
      flex-shrink: 0;
    }

    .icon-circle-bg {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      background: #EAF3FF;
    }

    /* Card Footer */
    .login-card-footer {
      text-align: center;
      font-size: 0.74rem;
      color: var(--cz-text-muted);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      position: relative;
      z-index: 2;
    }

    /* -------------------------------------------------------------
     * 5. High-End Enterprise Login Success Animation Overlay
     * ------------------------------------------------------------- */
    .login-success-overlay {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(14px);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 30px 24px;
      z-index: 25;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .login-success-overlay.active {
      opacity: 1;
      pointer-events: auto;
    }

    .success-content {
      width: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      animation: successPopIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    @keyframes successPopIn {
      0% {
        opacity: 0;
        transform: scale(0.92) translateY(12px);
      }
      100% {
        opacity: 1;
        transform: scale(1) translateY(0);
      }
    }

    /* Glowing Animated SVG Checkmark */
    .success-emblem-wrap {
      width: 72px;
      height: 72px;
      border-radius: 50%;
      background: #EAF7F0;
      border: 2px solid #A7F3D0;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 14px;
      box-shadow: 0 6px 20px rgba(16, 185, 129, 0.22);
    }

    .success-checkmark {
      width: 44px;
      height: 44px;
      stroke-width: 3.5;
      stroke: #10B981;
      stroke-miterlimit: 10;
    }

    .checkmark-circle {
      stroke-dasharray: 166;
      stroke-dashoffset: 166;
      stroke-width: 3;
      stroke-miterlimit: 10;
      stroke: #10B981;
      animation: strokeCircle 0.5s cubic-bezier(0.65, 0, 0.45, 1) forwards;
    }

    .checkmark-check {
      transform-origin: 50% 50%;
      stroke-dasharray: 48;
      stroke-dashoffset: 48;
      animation: strokeCheck 0.35s cubic-bezier(0.65, 0, 0.45, 1) 0.45s forwards;
    }

    @keyframes strokeCircle {
      100% {
        stroke-dashoffset: 0;
      }
    }

    @keyframes strokeCheck {
      100% {
        stroke-dashoffset: 0;
      }
    }

    .success-badge {
      display: inline-block;
      font-size: 0.68rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      color: #059669;
      background: #E7F8F1;
      border: 1px solid #A7F3D0;
      padding: 3px 12px;
      border-radius: 20px;
      margin-bottom: 8px;
    }

    .success-welcome {
      font-size: 1.35rem;
      font-weight: 800;
      color: var(--cz-navy);
      letter-spacing: -0.02em;
      margin-bottom: 2px;
    }

    .success-user {
      font-size: 1.05rem;
      font-weight: 700;
      color: var(--cz-primary-blue);
      margin-bottom: 2px;
    }

    .success-role {
      font-size: 0.78rem;
      color: var(--cz-text-muted);
      margin-bottom: 18px;
    }

    /* Live Status Steps Ticker with attractive words */
    .success-ticker-box {
      width: 100%;
      background: #F8FAFD;
      border: 1px solid var(--cz-border);
      border-radius: 9px;
      padding: 9px 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 9px;
      margin-bottom: 14px;
    }

    .success-spinner-dot {
      width: 8px;
      height: 8px;
      background: var(--cz-primary-blue);
      border-radius: 50%;
      animation: pulseDot 0.8s infinite alternate ease-in-out;
    }

    @keyframes pulseDot {
      0% { opacity: 0.3; transform: scale(0.7); }
      100% { opacity: 1; transform: scale(1.3); }
    }

    .success-ticker-text {
      font-size: 0.76rem;
      font-weight: 600;
      color: #1E293B;
      letter-spacing: -0.01em;
    }

    /* Animated Progress Glow Bar */
    .success-progress-bar-wrap {
      width: 100%;
      height: 6px;
      background: #E2E8F0;
      border-radius: 10px;
      overflow: hidden;
      margin-bottom: 8px;
    }

    .success-progress-bar {
      height: 100%;
      width: 0%;
      background: linear-gradient(90deg, #2878E8, #00C9A7);
      border-radius: 10px;
      transition: width 0.28s ease;
      box-shadow: 0 0 10px rgba(40, 120, 232, 0.45);
    }

    .success-hint {
      font-size: 0.70rem;
      color: #94A3B8;
      font-weight: 500;
    }

    /* -------------------------------------------------------------
     * 6. Responsive & Accessibility Stacking
     * ------------------------------------------------------------- */
    @media (max-width: 1024px) {
      .hero-section {
        padding: 32px 30px;
      }
      .hero-headline {
        font-size: 2.3rem;
      }
      .feature-tiles-grid {
        grid-template-columns: repeat(2, 1fr);
        max-width: 380px;
      }
      .login-section {
        flex: 0 0 420px;
      }
    }

    @media (max-width: 900px) {
      .dyeing-login-wrapper {
        flex-direction: column-reverse;
      }
      .hero-section {
        flex: 1 1 auto;
        padding: 32px 20px;
        background-position: center bottom;
      }
      .hero-section::before {
        width: 100%;
        background: linear-gradient(180deg, rgba(245, 248, 252, 0.95) 0%, rgba(245, 248, 252, 0.85) 60%, rgba(245, 248, 252, 0.40) 100%);
      }
      .login-section {
        flex: 1 1 auto;
        padding: 32px 20px 20px;
      }
      .login-card {
        max-width: 420px;
        margin: 0 auto;
      }
      .top-lang-bar {
        position: static;
        margin-bottom: 12px;
        align-self: flex-end;
      }
    }
  </style>
</head>
<body>

<div class="dyeing-login-wrapper">

  <!-- ============================================================== -->
  <!-- LEFT HERO SECTION: BRANDING, HEADLINE, TILES & TEXTILE HERO    -->
  <!-- ============================================================== -->
  <div class="hero-section">
    <div class="hero-content">

      <!-- Product Brand Header (User's Exact Logo) -->
      <div class="hero-brand">
        <div>
          <img src="<?= $baseUrl ?>/assets/img/dyeing-erp-logo.png?v=<?= filemtime(__DIR__ . '/../assets/img/dyeing-erp-logo.png') ?>" alt="Dyeing ERP" class="hero-brand-logo">
          <div class="hero-brand-tagline">Colouring Possibilities. Delivering Excellence.</div>
        </div>
      </div>

      <!-- Main Headline -->
      <div class="hero-headline">
        From Fabric to
        <span class="text-blue">Perfect Shades</span>
      </div>

      <!-- Description -->
      <p class="hero-desc">
        Complete dyeing and processing management for modern textile manufacturing.
      </p>

      <!-- 8 Compact Feature Tiles: Cohesive Blue & Muted Cyan Family -->
      <div class="feature-tiles-grid">
        <!-- 1. Lab Dip & Recipe -->
        <div class="feature-tile">
          <div class="tile-icon-box">
            <i data-lucide="flask-conical"><i class="bi bi-eyedropper"></i></i>
          </div>
          <div class="feature-tile-label">Lab Dip &amp; Recipe</div>
        </div>

        <!-- 2. Batch Planning -->
        <div class="feature-tile tile-alt">
          <div class="tile-icon-box">
            <i data-lucide="layers"><i class="bi bi-stack"></i></i>
          </div>
          <div class="feature-tile-label">Batch Planning</div>
        </div>

        <!-- 3. Machine Management -->
        <div class="feature-tile">
          <div class="tile-icon-box">
            <i data-lucide="factory"><i class="bi bi-cpu"></i></i>
          </div>
          <div class="feature-tile-label">Machine Management</div>
        </div>

        <!-- 4. Dyeing Production -->
        <div class="feature-tile tile-alt">
          <div class="tile-icon-box">
            <i data-lucide="droplet"><i class="bi bi-droplet-half"></i></i>
          </div>
          <div class="feature-tile-label">Dyeing Production</div>
        </div>

        <!-- 5. Quality Inspection -->
        <div class="feature-tile">
          <div class="tile-icon-box">
            <i data-lucide="shield-check"><i class="bi bi-shield-check"></i></i>
          </div>
          <div class="feature-tile-label">Quality Inspection</div>
        </div>

        <!-- 6. Finishing Process -->
        <div class="feature-tile tile-alt">
          <div class="tile-icon-box">
            <i data-lucide="wind"><i class="bi bi-magic"></i></i>
          </div>
          <div class="feature-tile-label">Finishing Process</div>
        </div>

        <!-- 7. Roll Packing & Stock -->
        <div class="feature-tile">
          <div class="tile-icon-box">
            <i data-lucide="package"><i class="bi bi-box-seam"></i></i>
          </div>
          <div class="feature-tile-label">Roll Packing &amp; Stock</div>
        </div>

        <!-- 8. Real-time Reports -->
        <div class="feature-tile tile-alt">
          <div class="tile-icon-box">
            <i data-lucide="bar-chart-3"><i class="bi bi-bar-chart-line"></i></i>
          </div>
          <div class="feature-tile-label">Real-time Reports</div>
        </div>
      </div>

      <!-- Bottom Information Panel -->
      <div class="bottom-info-panel">
        <div class="info-stat">
          <i data-lucide="settings" class="info-stat-icon"><i class="bi bi-gear-fill"></i></i>
          <div>
            <div class="info-stat-val">100%</div>
            <div class="info-stat-lbl">Process Control</div>
          </div>
        </div>

        <div class="vr opacity-25"></div>

        <div class="info-stat">
          <i data-lucide="shield-check" class="info-stat-icon"><i class="bi bi-shield-fill-check"></i></i>
          <div>
            <div class="info-stat-val">Better</div>
            <div class="info-stat-lbl">Quality</div>
          </div>
        </div>

        <div class="vr opacity-25"></div>

        <div class="info-stat">
          <i data-lucide="trending-up" class="info-stat-icon"><i class="bi bi-graph-up-arrow"></i></i>
          <div>
            <div class="info-stat-val">Higher</div>
            <div class="info-stat-lbl">Productivity</div>
          </div>
        </div>
      </div>

    </div>

    <!-- Bottom Footer Tagline -->
    <div class="hero-footer-tagline">
      <span>🍃</span> Smarter Dyeing. Sustainable Textiles.
    </div>
  </div>

  <!-- ============================================================== -->
  <!-- RIGHT SECTION: TOPBAR LANGUAGE & POLISHED LOGIN CARD          -->
  <!-- ============================================================== -->
  <div class="login-section">

    <!-- Language Selector Top Right -->
    <div class="top-lang-bar" title="Language selection">
      <i data-lucide="globe" style="width: 16px; height: 16px;"><i class="bi bi-globe2"></i></i>
      <span>English</span>
      <i class="bi bi-chevron-down" style="font-size: 0.65rem;"></i>
    </div>

    <!-- Main White Login Card -->
    <div class="login-card" id="loginCard">

      <!-- ============================================================ -->
      <!-- Top-Right Decorative Flowing Wave Ribbon Matching Image 1    -->
      <!-- ============================================================ -->
      <div class="card-wave-corner" aria-hidden="true">
        <svg viewBox="0 0 170 110" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
          <path d="M40 0C70 45 110 25 170 65V0H40Z" fill="url(#waveG1)" opacity="0.35"/>
          <path d="M0 0C50 55 100 20 170 90V0H0Z" fill="url(#waveG2)" opacity="0.22"/>
          <path d="M90 0C110 30 140 18 170 42V0H90Z" fill="url(#waveG3)" opacity="0.28"/>
          <defs>
            <linearGradient id="waveG1" x1="40" y1="0" x2="170" y2="65" gradientUnits="userSpaceOnUse">
              <stop stop-color="#3B82F6"/>
              <stop offset="1" stop-color="#93C5FD" stop-opacity="0.2"/>
            </linearGradient>
            <linearGradient id="waveG2" x1="0" y1="0" x2="170" y2="90" gradientUnits="userSpaceOnUse">
              <stop stop-color="#2563EB"/>
              <stop offset="1" stop-color="#DBEAFE" stop-opacity="0.1"/>
            </linearGradient>
            <linearGradient id="waveG3" x1="90" y1="0" x2="170" y2="42" gradientUnits="userSpaceOnUse">
              <stop stop-color="#60A5FA"/>
              <stop offset="1" stop-color="#BAE6FD" stop-opacity="0.15"/>
            </linearGradient>
          </defs>
        </svg>
      </div>

      <!-- ============================================================ -->
      <!-- Stunning Enterprise Login Success Overlay (Smooth Transition)-->
      <!-- ============================================================ -->
      <div class="login-success-overlay" id="loginSuccessOverlay">
        <div class="success-content">
          <!-- Animated Checkmark Emblem -->
          <div class="success-emblem-wrap">
            <svg class="success-checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
              <circle class="checkmark-circle" cx="26" cy="26" r="24" fill="none"/>
              <path class="checkmark-check" fill="none" d="M14 27l8 8 16-16"/>
            </svg>
          </div>

          <div class="success-badge">AUTHENTICATION VERIFIED</div>
          <h2 class="success-welcome">Welcome Back</h2>
          <div class="success-user" id="successUserTitle">M. Senthilkumar</div>
          <div class="success-role" id="successUserSubtitle">Plant Administrator · Tirupur Mill (TN 33)</div>

          <!-- Dynamic Live Status Steps Ticker with attractive words -->
          <div class="success-ticker-box">
            <div class="success-spinner-dot"></div>
            <span class="success-ticker-text" id="successTickerText">Authenticating enterprise security token...</span>
          </div>

          <!-- Animated Progress Glow Bar -->
          <div class="success-progress-bar-wrap">
            <div class="success-progress-bar" id="successProgressBar"></div>
          </div>
          <div class="success-hint">Launching Wet Processing Workspace...</div>
        </div>
      </div>

      <!-- Centered Card Brand -->
      <div class="card-brand-header">
        <img src="<?= $baseUrl ?>/assets/img/dyeing-mark.png?v=<?= filemtime(__DIR__ . '/../assets/img/dyeing-mark.png') ?>" alt="Dyeing ERP" class="card-brand-logo">
        <h1 class="card-brand-title">Dyeing <span>ERP</span></h1>
        <div class="card-brand-subtitle">Dyeing &amp; Processing Management System</div>
        <div class="decorative-split-bar">
          <div class="bar-1"></div>
          <div class="bar-2"></div>
        </div>
      </div>

      <!-- Authentication Error Feedback -->
      <div class="alert alert-danger py-2 px-3 small d-flex align-items-center gap-2 mb-3 rounded-3 <?= empty($error) ? 'd-none' : '' ?>" id="loginErrorAlert" role="alert">
        <i class="bi bi-exclamation-triangle-fill text-danger"></i>
        <span id="loginErrorMsg"><?= htmlspecialchars($error) ?></span>
      </div>

      <!-- Login Form: Action is empty so it always posts to current URL with NO 301 redirects -->
      <form method="POST" action="" id="loginForm">
        
        <!-- Username / Email -->
        <div class="form-group-custom">
          <label class="custom-label" for="usernameInput">Username or Email</label>
          <div class="custom-input-group">
            <span class="input-icon-left"><i data-lucide="user" style="width: 17px; height: 17px;"><i class="bi bi-person"></i></i></span>
            <input type="text" 
                   id="usernameInput" 
                   name="username" 
                   class="custom-input" 
                   placeholder="Enter your username or email" 
                   value="" 
                   required 
                   autofocus>
          </div>
        </div>

        <!-- Password -->
        <div class="form-group-custom">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <label class="custom-label mb-0" for="passwordInput">Password</label>
            <a href="javascript:void(0)" class="forgot-link" onclick="alert('Please contact your Plant Administrator to reset your password.')">Forgot Password?</a>
          </div>
          <div class="custom-input-group">
            <span class="input-icon-left"><i data-lucide="lock" style="width: 17px; height: 17px;"><i class="bi bi-lock"></i></i></span>
            <input type="password" 
                   id="passwordInput" 
                   name="password" 
                   class="custom-input" 
                   placeholder="Enter your password" 
                   value="" 
                   required>
            <button type="button" class="password-toggle-btn" id="togglePasswordBtn" aria-label="Toggle password visibility">
              <i data-lucide="eye" id="toggleEyeIcon" style="width: 18px; height: 18px;"><i class="bi bi-eye"></i></i>
            </button>
          </div>
        </div>

        <!-- Remember Me Checkbox -->
        <div class="login-options-row">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="rememberMeCheckbox" name="remember_me" checked>
            <label class="form-check-label" for="rememberMeCheckbox">
              Remember me
            </label>
          </div>
        </div>

        <!-- Primary Submit Button -->
        <button type="submit" class="btn-submit-login" id="submitBtn">
          <span>Sign In to Workspace</span>
          <i data-lucide="arrow-right" style="width: 18px; height: 18px;"><i class="bi bi-arrow-right"></i></i>
        </button>
      </form>

      <!-- OR Divider -->
      <div class="divider-or">
        <span>OR</span>
      </div>

      <!-- Quick Access Department Head Matching Image 1 Exactly -->
      <div class="quick-access-header">
        <div class="quick-access-title">
          <i data-lucide="users" style="width: 17px; height: 17px; color: #2563EB;"><i class="bi bi-people"></i></i>
          <span>Quick Access (Department Head)</span>
        </div>
        <div class="quick-access-sub">Select your role to continue</div>
      </div>

      <div class="quick-access-grid">
        <!-- Super Admin -->
        <button type="button" class="quick-card-btn" onclick="quickFill('admin')" title="Sign in as Super Admin">
          <div class="d-flex align-items-center gap-2">
            <span class="quick-card-icon-wrap"><i data-lucide="crown" style="width: 18px; height: 18px;"><i class="bi bi-person-badge"></i></i></span>
            <div class="text-start">
              <div class="quick-card-name">Super Admin</div>
              <div class="quick-card-role">Full Access</div>
            </div>
          </div>
          <i class="bi bi-chevron-right quick-card-arrow"></i>
        </button>

        <!-- Dyeing Master -->
        <button type="button" class="quick-card-btn" onclick="quickFill('dyeing_mgr')" title="Sign in as Dyeing Master">
          <div class="d-flex align-items-center gap-2">
            <span class="quick-card-icon-wrap"><i data-lucide="flask-conical" style="width: 18px; height: 18px;"><i class="bi bi-eyedropper"></i></i></span>
            <div class="text-start">
              <div class="quick-card-name">Dyeing Master</div>
              <div class="quick-card-role">Production</div>
            </div>
          </div>
          <i class="bi bi-chevron-right quick-card-arrow"></i>
        </button>

        <!-- QA Lead -->
        <button type="button" class="quick-card-btn" onclick="quickFill('qc_lead')" title="Sign in as QA Lead">
          <div class="d-flex align-items-center gap-2">
            <span class="quick-card-icon-wrap icon-circle-bg"><i data-lucide="shield-check" style="width: 17px; height: 17px;"><i class="bi bi-shield-check"></i></i></span>
            <div class="text-start">
              <div class="quick-card-name">QA Lead</div>
              <div class="quick-card-role">Quality Control</div>
            </div>
          </div>
          <i class="bi bi-chevron-right quick-card-arrow"></i>
        </button>

        <!-- Finance CFO -->
        <button type="button" class="quick-card-btn" onclick="quickFill('finance_mgr')" title="Sign in as Finance CFO">
          <div class="d-flex align-items-center gap-2">
            <span class="quick-card-icon-wrap"><i data-lucide="bar-chart-2" style="width: 18px; height: 18px;"><i class="bi bi-graph-up"></i></i></span>
            <div class="text-start">
              <div class="quick-card-name">Finance CFO</div>
              <div class="quick-card-role">Finance &amp; Reports</div>
            </div>
          </div>
          <i class="bi bi-chevron-right quick-card-arrow"></i>
        </button>
      </div>

      <!-- Footer text with factory icon -->
      <div class="login-card-footer">
        <i data-lucide="factory" style="width: 15px; height: 15px; color: #64748B;"><i class="bi bi-buildings"></i></i>
        <span>Integrated Manufacturing ERP for Textile Industry</span>
      </div>

    </div>

  </div>

</div>

<!-- Interactive Scripts -->
<script>
  // Initialize Lucide Icons
  if (window.lucide) {
    lucide.createIcons();
  }

  // Show / Hide Password toggle
  const toggleBtn = document.getElementById('togglePasswordBtn');
  const passwordInput = document.getElementById('passwordInput');
  const toggleIcon = document.getElementById('toggleEyeIcon');

  if (toggleBtn && passwordInput) {
    toggleBtn.addEventListener('click', function() {
      const isPassword = passwordInput.getAttribute('type') === 'password';
      passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
      
      // Update icon
      if (isPassword) {
        toggleIcon.setAttribute('data-lucide', 'eye-off');
      } else {
        toggleIcon.setAttribute('data-lucide', 'eye');
      }
      if (window.lucide) {
        lucide.createIcons();
      }
    });
  }

  // Login Card Elements
  const loginForm = document.getElementById('loginForm');
  const loginCard = document.getElementById('loginCard');
  const submitBtn = document.getElementById('submitBtn');
  const errorAlert = document.getElementById('loginErrorAlert');
  const errorMsg = document.getElementById('loginErrorMsg');
  const successOverlay = document.getElementById('loginSuccessOverlay');
  const userTitle = document.getElementById('successUserTitle');
  const userSubtitle = document.getElementById('successUserSubtitle');
  const tickerText = document.getElementById('successTickerText');
  const progressBar = document.getElementById('successProgressBar');

  // Trigger login asynchronously with attractive enterprise animation
  function executeLogin(username, password) {
    // Hide previous error
    if (errorAlert) errorAlert.classList.add('d-none');

    // Button loading state
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Verifying credentials...';
    submitBtn.style.pointerEvents = 'none';
    submitBtn.style.opacity = '0.9';

    const formData = new FormData();
    formData.append('username', username);
    formData.append('password', password);
    formData.append('ajax', '1');

    // Use current URL with trailing slash guaranteed
    const postUrl = window.location.pathname.endsWith('/') ? window.location.pathname : window.location.pathname + '/';

    fetch(postUrl, {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    })
    .then(async response => {
      const data = await response.json();
      if (response.ok && data.success) {
        // Successful login: Launch Enterprise Animation Sequence
        triggerSuccessSequence(data);
      } else {
        // Failed login
        triggerErrorSequence(data.message || 'Invalid username or password. Please verify your credentials.');
      }
    })
    .catch(err => {
      // Fallback: If network or JSON parsing fails, submit standard form
      console.warn('AJAX login fallback:', err);
      document.getElementById('usernameInput').value = username;
      document.getElementById('passwordInput').value = password;
      loginForm.submit();
    });
  }

  // Trigger Success Sequence with high-end words & animated progress steps
  function triggerSuccessSequence(data) {
    if (data.user) {
      if (userTitle) userTitle.textContent = data.user.name;
      if (userSubtitle) userSubtitle.textContent = data.user.designation + ' · Tirupur Mill (TN 33)';
    }

    // Show Success Overlay
    if (successOverlay) {
      successOverlay.classList.add('active');
    }

    // Dynamic steps with attractive words & progress bar
    const steps = [
      { progress: '25%', text: 'Authenticating enterprise security handshake...' },
      { progress: '55%', text: 'Establishing encrypted session to Tirupur Mill...' },
      { progress: '85%', text: 'Syncing soft-flow vessels, batch planning & QC telemetry...' },
      { progress: '100%', text: 'Workspace ready. Launching Dyeing Dashboard...' }
    ];

    let stepIdx = 0;
    const interval = setInterval(() => {
      stepIdx++;
      if (stepIdx < steps.length) {
        if (progressBar) progressBar.style.width = steps[stepIdx].progress;
        if (tickerText) tickerText.textContent = steps[stepIdx].text;
      } else {
        clearInterval(interval);
        setTimeout(() => {
          window.location.href = data.redirect || '<?= $baseUrl ?>/dyeing/dashboard';
        }, 250);
      }
    }, 280);

    // Initial step trigger
    if (progressBar) progressBar.style.width = steps[0].progress;
    if (tickerText) tickerText.textContent = steps[0].text;
  }

  // Trigger Error Animation on Card
  function triggerErrorSequence(msg) {
    if (errorAlert && errorMsg) {
      errorMsg.textContent = msg;
      errorAlert.classList.remove('d-none');
    }

    if (loginCard) {
      loginCard.classList.remove('shake-card');
      void loginCard.offsetWidth; // re-flow
      loginCard.classList.add('shake-card');
      setTimeout(() => loginCard.classList.remove('shake-card'), 500);
    }

    submitBtn.innerHTML = '<span>Sign In to Workspace</span> <i data-lucide="arrow-right" style="width: 18px; height: 18px;"><i class="bi bi-arrow-right"></i></i>';
    submitBtn.style.pointerEvents = 'auto';
    submitBtn.style.opacity = '1';

    if (window.lucide) {
      lucide.createIcons();
    }
  }

  // Standard Form Submit Handler
  if (loginForm) {
    loginForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const u = document.getElementById('usernameInput').value.trim();
      const p = document.getElementById('passwordInput').value;
      executeLogin(u, p);
    });
  }

  // Quick Access Department Head Click Handler
  function quickFill(username) {
    const userInput = document.getElementById('usernameInput');
    const passInput = document.getElementById('passwordInput');
    if (userInput) userInput.value = username;
    if (passInput) passInput.value = 'password123';
    executeLogin(username, 'password123');
  }
</script>

</body>
</html>
