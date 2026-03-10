<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
  <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
  <title><?= isset($page_title) ? $page_title . ' — KABAGA Academy' : 'KABAGA Academy | Lung Center of the Philippines' ?></title>

  <!-- Tabler CSS -->
  <link href="<?= base_url('assets/tabler/css/tabler.min.css'); ?>" rel="stylesheet"/>
  <link href="<?= base_url('assets/tabler/css/tabler-flags.min.css'); ?>" rel="stylesheet"/>
  <link href="<?= base_url('assets/tabler/css/tabler-payments.min.css'); ?>" rel="stylesheet"/>
  <link href="<?= base_url('assets/tabler/css/tabler-vendors.min.css'); ?>" rel="stylesheet"/>
  <link href="<?= base_url('assets/tabler/css/demo.min.css'); ?>" rel="stylesheet"/>

  <!-- Google Fonts: DM Sans for brand feel -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">

  <!-- Animate.css -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

  <style>
    /* ============================================================
       KABAGA ACADEMY — Global Design System
       Brand: Lung Center of the Philippines
       Palette: #6dabcf (primary), #1a3a5c (deep navy), #f0f6fa (bg)
    ============================================================ */

    :root {
      --ka-primary:       #6dabcf;
      --ka-primary-dark:  #5a9ec1;
      --ka-primary-deep:  #4a8eb0;
      --ka-navy:          #1a3a5c;
      --ka-navy-mid:      #254d75;
      --ka-accent:        #e8f4fd;
      --ka-success:       #2fb344;
      --ka-warning:       #f59f00;
      --ka-danger:        #d63939;
      --ka-text:          #1e293b;
      --ka-text-muted:    #64748b;
      --ka-border:        #e2e8f0;
      --ka-bg:            #f8fafc;
      --ka-card-bg:       #ffffff;
      --ka-sidebar-bg:    #1a3a5c;
      --ka-sidebar-hover: #254d75;
      --ka-sidebar-active:#6dabcf;
      --ka-header-height: 64px;
      --ka-sidebar-width: 260px;
      --tblr-font-sans-serif: 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      --tblr-primary:     #6dabcf;
      --tblr-primary-rgb: 109, 171, 207;
    }

    *, *::before, *::after { box-sizing: border-box; }

    html { scroll-behavior: smooth; }

    body {
      font-family: var(--tblr-font-sans-serif);
      background-color: var(--ka-bg);
      color: var(--ka-text);
      font-size: 14px;
      line-height: 1.6;
      -webkit-font-smoothing: antialiased;
    }

    /* ── Scrollbar ── */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
    ::-webkit-scrollbar-thumb:hover { background: var(--ka-primary); }

    /* ── Page Shell ── */
    .page { min-height: 100vh; display: flex; flex-direction: column; }

    /* ── Cards & Surfaces ── */
    .card {
      border: 1px solid var(--ka-border);
      border-radius: 12px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.04);
      transition: box-shadow 0.2s ease, transform 0.2s ease;
      background: var(--ka-card-bg);
    }
    .card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
    .card-header {
      border-bottom: 1px solid var(--ka-border);
      padding: 1rem 1.25rem;
      background: transparent;
      border-radius: 12px 12px 0 0 !important;
    }
    .card-title {
      font-weight: 600;
      font-size: 0.9375rem;
      color: var(--ka-text);
      margin-bottom: 0;
    }

    /* ── Buttons ── */
    .btn-primary, .btn-ka {
      background: var(--ka-primary);
      border-color: var(--ka-primary);
      color: #fff;
      font-weight: 600;
      border-radius: 8px;
      transition: all 0.2s ease;
      letter-spacing: 0.01em;
    }
    .btn-primary:hover, .btn-ka:hover {
      background: var(--ka-primary-dark);
      border-color: var(--ka-primary-dark);
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(109,171,207,0.35);
      color: #fff;
    }
    .btn-ka-navy {
      background: var(--ka-navy);
      border-color: var(--ka-navy);
      color: #fff;
      font-weight: 600;
      border-radius: 8px;
      transition: all 0.2s ease;
    }
    .btn-ka-navy:hover {
      background: var(--ka-navy-mid);
      border-color: var(--ka-navy-mid);
      color: #fff;
      transform: translateY(-1px);
    }

    /* ── Badges ── */
    .badge-ka { background: var(--ka-accent); color: var(--ka-primary-deep); font-weight: 600; border-radius: 6px; }
    .badge-ka-navy { background: var(--ka-navy); color: #fff; font-weight: 600; border-radius: 6px; }

    /* ── Forms ── */
    .form-control, .form-select {
      border: 1.5px solid var(--ka-border);
      border-radius: 8px;
      font-size: 0.875rem;
      padding: 0.5rem 0.75rem;
      transition: border-color 0.2s, box-shadow 0.2s;
      background: var(--ka-card-bg);
    }
    .form-control:focus, .form-select:focus {
      border-color: var(--ka-primary);
      box-shadow: 0 0 0 3px rgba(109,171,207,0.15);
      outline: none;
    }
    .form-label { font-weight: 500; font-size: 0.8125rem; color: var(--ka-text); margin-bottom: 0.375rem; }

    /* ── Course Cards (LMS specific) ── */
    .course-card {
      border-radius: 12px;
      overflow: hidden;
      border: 1px solid var(--ka-border);
      background: var(--ka-card-bg);
      transition: all 0.25s ease;
      cursor: pointer;
    }
    .course-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 32px rgba(0,0,0,0.1);
    }
    .course-card-thumb {
      position: relative;
      overflow: hidden;
      height: 160px;
      background: linear-gradient(135deg, var(--ka-navy), var(--ka-primary));
    }
    .course-card-thumb img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease; }
    .course-card:hover .course-card-thumb img { transform: scale(1.05); }
    .course-progress-bar {
      height: 4px;
      background: var(--ka-border);
      border-radius: 2px;
      overflow: hidden;
    }
    .course-progress-fill {
      height: 100%;
      background: linear-gradient(90deg, var(--ka-primary), var(--ka-primary-deep));
      border-radius: 2px;
      transition: width 0.6s ease;
    }

    /* ── Avatar ── */
    .avatar-ka {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--ka-primary), var(--ka-navy));
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-weight: 700;
      font-size: 0.8125rem;
      letter-spacing: 0.02em;
      flex-shrink: 0;
    }

    /* ── Stat Cards ── */
    .stat-card {
      border-radius: 12px;
      padding: 1.25rem;
      border: 1px solid var(--ka-border);
      background: var(--ka-card-bg);
      position: relative;
      overflow: hidden;
    }
    .stat-card::after {
      content: '';
      position: absolute;
      top: 0; right: 0;
      width: 80px; height: 80px;
      background: radial-gradient(circle, rgba(109,171,207,0.12) 0%, transparent 70%);
      border-radius: 0 12px 0 80px;
    }
    .stat-value { font-size: 1.75rem; font-weight: 700; color: var(--ka-text); line-height: 1.2; }
    .stat-label { font-size: 0.8125rem; color: var(--ka-text-muted); font-weight: 500; margin-top: 2px; }
    .stat-trend { font-size: 0.75rem; font-weight: 600; margin-top: 0.5rem; }
    .stat-trend.up { color: var(--ka-success); }
    .stat-trend.down { color: var(--ka-danger); }

    /* ── Page Header ── */
    .page-header-ka {
      background: var(--ka-card-bg);
      border-bottom: 1px solid var(--ka-border);
      padding: 1rem 1.5rem;
      margin-bottom: 1.5rem;
    }
    .page-title-ka {
      font-size: 1.25rem;
      font-weight: 700;
      color: var(--ka-text);
      margin: 0;
    }
    .page-subtitle-ka {
      font-size: 0.8125rem;
      color: var(--ka-text-muted);
      margin: 0;
    }

    /* ── Tables ── */
    .table-ka thead th {
      background: var(--ka-bg);
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      color: var(--ka-text-muted);
      border-bottom: 1px solid var(--ka-border);
      padding: 0.75rem 1rem;
    }
    .table-ka tbody tr {
      border-bottom: 1px solid var(--ka-border);
      transition: background 0.15s;
    }
    .table-ka tbody tr:hover { background: var(--ka-accent); }
    .table-ka tbody td { padding: 0.875rem 1rem; vertical-align: middle; }

    /* ── Alerts ── */
    .alert { border-radius: 10px; border: none; font-size: 0.875rem; }
    .alert-success { background: #ecfdf5; color: #065f46; }
    .alert-danger { background: #fef2f2; color: #991b1b; }
    .alert-warning { background: #fffbeb; color: #92400e; }
    .alert-info { background: var(--ka-accent); color: var(--ka-navy); }

    /* ── Tooltips & Dropdowns ── */
    .dropdown-menu {
      border: 1px solid var(--ka-border);
      border-radius: 10px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.1);
      padding: 0.5rem;
      min-width: 200px;
    }
    .dropdown-item {
      border-radius: 7px;
      font-size: 0.875rem;
      padding: 0.5rem 0.75rem;
      font-weight: 500;
      color: var(--ka-text);
      transition: background 0.15s;
    }
    .dropdown-item:hover { background: var(--ka-accent); color: var(--ka-primary-deep); }
    .dropdown-divider { border-color: var(--ka-border); margin: 0.375rem 0; }

    /* ── Modals ── */
    .modal-content { border-radius: 16px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.15); }
    .modal-header { border-bottom: 1px solid var(--ka-border); padding: 1.25rem 1.5rem; }
    .modal-footer { border-top: 1px solid var(--ka-border); padding: 1rem 1.5rem; }
    .modal-title { font-weight: 700; font-size: 1.0625rem; }

    /* ── Tabs ── */
    .nav-tabs-ka { border-bottom: 2px solid var(--ka-border); gap: 0; }
    .nav-tabs-ka .nav-link {
      border: none;
      border-bottom: 2px solid transparent;
      margin-bottom: -2px;
      font-weight: 500;
      color: var(--ka-text-muted);
      padding: 0.75rem 1rem;
      border-radius: 0;
      font-size: 0.875rem;
      transition: all 0.2s;
    }
    .nav-tabs-ka .nav-link:hover { color: var(--ka-primary); background: transparent; }
    .nav-tabs-ka .nav-link.active { color: var(--ka-primary-deep); border-bottom-color: var(--ka-primary); font-weight: 600; }

    /* ── Loading Skeleton ── */
    .skeleton {
      background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
      background-size: 200% 100%;
      animation: skeleton-shine 1.4s ease infinite;
      border-radius: 6px;
    }
    @keyframes skeleton-shine {
      0% { background-position: 200% 0; }
      100% { background-position: -200% 0; }
    }

    /* ── LCP — Lung Center Brand ── */
    .lcp-brand-dot {
      width: 8px; height: 8px;
      border-radius: 50%;
      background: var(--ka-primary);
      display: inline-block;
      margin-right: 4px;
    }

    /* ── Responsive adjustments ── */
    @media (max-width: 768px) {
      .stat-value { font-size: 1.375rem; }
      .page-title-ka { font-size: 1.0625rem; }
    }
  </style>
</head>
<body>
  <div class="page">