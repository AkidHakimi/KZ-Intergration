<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard — VeriTrust</title>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700&family=Barlow:wght@400;500&display=swap" rel="stylesheet" />
    <!-- QR Code generator -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <!-- jsPDF for PDF generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --orange:       #E8650A;
            --orange-light: #FFF0E6;
            --orange-mid:   #F9D5BB;
            --orange-dark:  #C4540A;
            --ink:          #1A1A1A;
            --ink-mid:      #555555;
            --ink-light:    #888888;
            --rule:         #E0E0E0;
            --rule-strong:  #C8C8C8;
            --surface:      #F5F5F5;
            --white:        #FFFFFF;
            --green:        #1A7A4A;
            --green-bg:     #EFF9F4;
            --red:          #C0392B;
            --red-bg:       #FDF0EF;
            --blue:         #1A4F9A;
            --blue-bg:      #EEF3FC;
        }
        body {
            font-family: 'Barlow', sans-serif;
            background: var(--surface); color: var(--ink);
            min-height: 100vh; display: flex; flex-direction: column;
        }

        /* Top accent */
        .top-stripe { height: 4px; background: var(--orange); }

        /* Site nav */
        .site-nav {
            background: var(--white); border-bottom: 1px solid var(--rule);
            padding: 0 32px; height: 56px;
            display: flex; align-items: center; gap: 0;
        }
        .nav-brand {
            display: flex; align-items: center; gap: 10px;
            text-decoration: none; margin-right: 32px;
        }
        .logo-mark {
            width: 30px; height: 30px;
            display: flex; align-items: center; justify-content: center;
        }
        .brand-name {
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 600; font-size: 17px; letter-spacing: 2px;
            color: var(--ink); text-transform: uppercase;
        }
        .nav-link {
            font-size: 12px; color: var(--ink-mid); text-decoration: none;
            padding: 0 14px; height: 56px; display: flex; align-items: center;
            border-bottom: 2px solid transparent; font-weight: 500;
            letter-spacing: 0.3px; transition: all 0.1s; text-transform: uppercase;
        }
        .nav-link:hover { color: var(--orange); }
        .nav-link.active { color: var(--orange); border-bottom-color: var(--orange); }

        .nav-right {
            margin-left: auto; display: flex; align-items: center; gap: 16px;
        }
        .role-pill {
            font-size: 10px; font-weight: 600; text-transform: uppercase;
            letter-spacing: 0.8px; padding: 3px 10px; border-radius: 2px;
        }
        .role-pill.issuer   { background: var(--blue-bg);  color: var(--blue); }
        .role-pill.holder   { background: var(--green-bg); color: var(--green); }
        .role-pill.verifier { background: var(--orange-light); color: var(--orange-dark); }

        .pqc-pill {
            font-size: 10px; font-weight: 600; text-transform: uppercase;
            letter-spacing: 0.5px; padding: 3px 10px; border-radius: 2px;
            border: 1px solid var(--orange-mid); color: var(--orange-dark);
            background: var(--orange-light);
        }
        .pqc-pill.off {
            border-color: var(--rule); color: var(--ink-light); background: var(--surface);
        }

        .nav-user {
            display: flex; align-items: center; gap: 12px;
            padding-left: 16px; border-left: 1px solid var(--rule);
        }
        .nav-username { font-size: 13px; color: var(--ink-mid); font-weight: 500; }
        .nav-logout {
            font-size: 12px; color: var(--red); text-decoration: none;
            font-weight: 500; text-transform: uppercase; letter-spacing: 0.3px;
        }
        .nav-logout:hover { color: #9B1E1E; }

        /* Page structure */
        .page-wrap {
            max-width: 1100px; margin: 0 auto;
            padding: 28px 32px 48px; flex: 1; width: 100%;
        }

        /* Page header row */
        .page-header {
            display: flex; align-items: flex-end; justify-content: space-between;
            margin-bottom: 24px; padding-bottom: 16px;
            border-bottom: 1px solid var(--rule);
        }
        .page-title {
            font-family: 'Barlow Condensed', sans-serif; font-weight: 700;
            font-size: 24px; letter-spacing: 1px; text-transform: uppercase; color: var(--ink);
        }
        .page-title span { color: var(--orange); }

        /* Flash message */
        .flash {
            padding: 12px 16px; font-size: 13px; margin-bottom: 20px;
            border-left: 3px solid;
        }
        .flash.success { background: var(--green-bg); color: var(--green); border-color: var(--green); }
        .flash.error   { background: var(--red-bg);   color: var(--red);   border-color: var(--red); }
        .flash.warning { background: #FFF8E6;           color: #7A5A00;      border-color: #E0A800; }

        /* Section heading */
        .section-head {
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 12px;
        }
        .section-title {
            font-family: 'Barlow Condensed', sans-serif; font-weight: 700;
            font-size: 15px; text-transform: uppercase; letter-spacing: 1px; color: var(--ink);
        }
        .count-badge {
            font-size: 11px; font-weight: 600; color: var(--ink-light);
            background: var(--surface); border: 1px solid var(--rule);
            padding: 1px 8px; border-radius: 2px;
        }

        /* Cards */
        .card {
            background: var(--white); border: 1px solid var(--rule);
            margin-bottom: 20px;
        }
        .card-head {
            padding: 16px 20px; border-bottom: 1px solid var(--rule);
            background: #FAFAFA; display: flex; align-items: center; justify-content: space-between;
        }
        .card-head-title {
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 700; font-size: 14px; text-transform: uppercase;
            letter-spacing: 0.8px; color: var(--ink);
        }
        .card-body { padding: 20px; }

        /* DID identity card */
        .identity-bar {
            background: var(--white); border: 1px solid var(--rule);
            border-left: 4px solid var(--orange);
            padding: 14px 20px; margin-bottom: 20px;
            display: flex; align-items: center; gap: 20px;
        }
        .identity-label {
            font-size: 10px; text-transform: uppercase; letter-spacing: 0.8px;
            color: var(--ink-light); font-weight: 500; flex-shrink: 0;
        }
        .identity-did {
            font-family: 'Courier New', monospace; font-size: 12px;
            color: var(--orange-dark); flex: 1; word-break: break-all;
        }
        .btn-copy-did {
            background: var(--white); border: 1px solid var(--rule);
            color: var(--ink-mid); font-size: 11px; font-weight: 500;
            padding: 5px 12px; cursor: pointer; flex-shrink: 0;
            text-transform: uppercase; letter-spacing: 0.3px; border-radius: 2px;
            transition: all 0.1s;
        }
        .btn-copy-did:hover { border-color: var(--orange); color: var(--orange); }

        /* Form fields */
        .field { margin-bottom: 14px; }
        .field-label {
            display: block; font-size: 11px; font-weight: 500;
            text-transform: uppercase; letter-spacing: 0.7px;
            color: var(--ink-mid); margin-bottom: 5px;
        }
        .field input, .field select, .field textarea {
            width: 100%; border: 1px solid var(--rule);
            background: var(--white); padding: 9px 12px;
            font-size: 13px; font-family: 'Barlow', sans-serif;
            color: var(--ink); outline: none; border-radius: 2px;
            transition: border-color 0.12s;
        }
        .field input:focus, .field select:focus, .field textarea:focus {
            border-color: var(--orange); box-shadow: 0 0 0 3px rgba(232,101,10,0.07);
        }
        .field textarea { resize: vertical; min-height: 80px; }
        .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

        /* Auto-serial notice */
        .auto-serial-notice {
            background: var(--orange-light); border: 1px solid var(--orange-mid);
            padding: 10px 14px; display: flex; gap: 10px; align-items: flex-start;
            margin-bottom: 14px;
        }
        .auto-serial-notice p { font-size: 12px; color: #7A3800; line-height: 1.5; }
        .auto-serial-notice strong { color: var(--orange-dark); }

        /* Buttons */
        .btn-primary {
            background: var(--orange); color: var(--white); border: none;
            padding: 10px 20px; font-family: 'Barlow Condensed', sans-serif;
            font-size: 13px; font-weight: 700; letter-spacing: 1.2px;
            text-transform: uppercase; cursor: pointer; border-radius: 2px;
            transition: background 0.12s;
        }
        .btn-primary:hover { background: var(--orange-dark); }
        .btn-primary:disabled { background: #CCCCCC; cursor: not-allowed; }

        .btn-secondary {
            background: var(--white); color: var(--ink-mid); border: 1px solid var(--rule);
            padding: 9px 18px; font-family: 'Barlow Condensed', sans-serif;
            font-size: 12px; font-weight: 600; letter-spacing: 0.8px;
            text-transform: uppercase; cursor: pointer; border-radius: 2px;
            transition: all 0.12s;
        }
        .btn-secondary:hover { border-color: var(--orange); color: var(--orange); }

        /* Table */
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table thead tr {
            background: #F7F7F7; border-bottom: 2px solid var(--rule);
        }
        .data-table th {
            padding: 10px 14px; text-align: left;
            font-size: 10px; font-weight: 600; text-transform: uppercase;
            letter-spacing: 0.8px; color: var(--ink-mid);
        }
        .data-table td {
            padding: 11px 14px; font-size: 13px; color: var(--ink);
            border-bottom: 1px solid var(--rule);
        }
        .data-table tbody tr:last-child td { border-bottom: none; }
        .data-table tbody tr:hover td { background: #FAFAFA; }

        /* Status badges */
        .status-badge {
            display: inline-block; font-size: 10px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.5px;
            padding: 2px 8px; border-radius: 2px;
        }
        .status-issued   { background: var(--blue-bg);    color: var(--blue); }
        .status-verified { background: var(--green-bg);   color: var(--green); }
        .status-rejected { background: var(--red-bg);     color: var(--red); }
        .status-revoked  { background: #FEF3E6;           color: #A04000; }

        .cert-serial {
            font-family: 'Courier New', monospace; font-size: 13px;
            font-weight: 600; color: var(--orange-dark); letter-spacing: 1px;
        }

        /* Action links */
        .action-link {
            font-size: 11px; color: var(--orange); text-decoration: none;
            font-weight: 500; text-transform: uppercase; letter-spacing: 0.3px;
            cursor: pointer; background: none; border: none; padding: 0;
        }
        .action-link:hover { text-decoration: underline; }
        .action-link.danger { color: var(--red); }
        .action-link.muted  { color: var(--ink-mid); }

        /* JSON-LD viewer */
        .jsonld-viewer {
            display: none; padding: 14px 16px;
            background: #F7F7F7; border-top: 1px solid var(--rule);
        }
        .jsonld-viewer pre {
            font-family: 'Courier New', monospace; font-size: 11px;
            color: #4A2600; line-height: 1.7; white-space: pre-wrap;
            word-break: break-all; max-height: 300px; overflow-y: auto;
        }

        /* Holder credential cards */
        .cred-card {
            background: var(--white); border: 1px solid var(--rule);
            margin-bottom: 16px;
        }
        .cred-card-head {
            padding: 14px 20px; border-bottom: 1px solid var(--rule);
            display: flex; justify-content: space-between; align-items: flex-start;
        }
        .cred-type {
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 700; font-size: 16px; letter-spacing: 0.5px;
            text-transform: uppercase; color: var(--orange-dark);
        }
        .cred-meta { font-size: 11px; color: var(--ink-light); margin-top: 2px; }

        .cred-serial-bar {
            background: var(--orange-light); border-bottom: 1px solid var(--orange-mid);
            padding: 10px 20px; display: flex; align-items: center; justify-content: space-between;
        }
        .serial-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.7px; color: var(--orange-dark); font-weight: 600; }
        .serial-value { font-family: 'Courier New', monospace; font-size: 18px; font-weight: 700; color: var(--orange-dark); letter-spacing: 2px; }

        .cred-fields { padding: 16px 20px; }
        .field-row-display {
            display: flex; gap: 16px; padding: 6px 0;
            border-bottom: 1px solid #F0F0F0; font-size: 13px;
        }
        .field-row-display:last-child { border-bottom: none; }
        .frd-key { color: var(--ink-light); width: 160px; flex-shrink: 0; font-size: 12px; }
        .frd-val { color: var(--ink); }

        .cred-actions {
            padding: 12px 20px; border-top: 1px solid var(--rule);
            display: flex; gap: 20px; align-items: center;
            background: #FAFAFA;
        }

        /* Verifier section */
        .verify-form-wrap {
            max-width: 480px;
        }
        .verify-input {
            width: 100%; border: 2px solid var(--rule);
            background: var(--white); padding: 12px 16px;
            font-size: 20px; font-family: 'Courier New', monospace;
            color: var(--ink); outline: none; border-radius: 2px;
            letter-spacing: 3px; text-align: center;
            transition: border-color 0.12s;
        }
        .verify-input:focus { border-color: var(--orange); }
        .verify-hint {
            font-size: 12px; color: var(--ink-light); margin-top: 6px; text-align: center;
        }
        .btn-verify {
            width: 100%; background: var(--orange); color: var(--white); border: none;
            padding: 13px; margin-top: 14px;
            font-family: 'Barlow Condensed', sans-serif; font-size: 16px;
            font-weight: 700; letter-spacing: 2px; text-transform: uppercase;
            cursor: pointer; border-radius: 2px; transition: background 0.12s;
        }
        .btn-verify:hover { background: var(--orange-dark); }

        /* Verification result */
        .verify-result {
            background: var(--white); border: 1px solid var(--rule);
        }
        .verify-result-head {
            padding: 16px 20px; display: flex; align-items: center; gap: 14px;
        }
        .verify-result-head.pass { border-left: 5px solid var(--green); background: var(--green-bg); }
        .verify-result-head.fail { border-left: 5px solid var(--red);   background: var(--red-bg); }
        .verify-result-head.revoked { border-left: 5px solid #E07800; background: #FEF3E6; }
        .verify-result-icon { font-size: 24px; }
        .verify-result-text h3 {
            font-family: 'Barlow Condensed', sans-serif; font-weight: 700;
            font-size: 18px; letter-spacing: 0.5px; text-transform: uppercase;
        }
        .verify-result-text p { font-size: 12px; margin-top: 2px; }
        .verify-result-head.pass h3 { color: var(--green); }
        .verify-result-head.pass p  { color: #2E8060; }
        .verify-result-head.fail h3 { color: var(--red); }
        .verify-result-head.fail p  { color: #9B2020; }
        .verify-result-head.revoked h3 { color: #A04000; }
        .verify-result-head.revoked p  { color: #805000; }

        .verify-checks {
            padding: 16px 20px; border-bottom: 1px solid var(--rule);
            display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
        }
        .check-item {
            display: flex; justify-content: space-between; align-items: center;
            background: #F7F7F7; padding: 10px 14px;
        }
        .check-item-label { font-size: 12px; color: var(--ink-mid); }
        .check-pass { font-size: 11px; font-weight: 600; color: var(--green); text-transform: uppercase; }
        .check-fail { font-size: 11px; font-weight: 600; color: var(--red);   text-transform: uppercase; }

        .verify-details {
            padding: 16px 20px;
        }
        .details-title {
            font-size: 10px; text-transform: uppercase; letter-spacing: 0.8px;
            color: var(--ink-light); font-weight: 600; margin-bottom: 10px;
        }

        /* Empty states */
        .empty-state {
            background: var(--white); border: 1px solid var(--rule);
            padding: 48px; text-align: center;
        }
        .empty-state p { font-size: 14px; color: var(--ink-light); }

        /* Footer */
        .site-footer {
            background: var(--white); border-top: 1px solid var(--rule);
            padding: 14px 32px; display: flex; justify-content: space-between;
        }
        .site-footer span { font-size: 11px; color: var(--ink-light); }

        /* Hidden qr generator */
        #qr-gen { position: absolute; left: -9999px; top: 0; width: 200px; background: #fff; padding: 8px; }
    </style>
</head>
<body>
<?php
$role      = $_SESSION['role'] ?? 'holder';
$did       = $_SESSION['did']  ?? ('did:kazsign:' . hash('sha256', $_SESSION['username'] ?? ''));
$username  = $_SESSION['username'] ?? 'user';
$hasKey    = !empty($_SESSION['private_key']);

$credentialFields = [
    'AcademicCredential' => [
        ['key' => 'name',             'label' => 'Full Name',             'placeholder' => 'e.g. RAJA HAZEERA NAJWA'],
        ['key' => 'awardNameEnglish', 'label' => 'Award Name (English)',  'placeholder' => 'e.g. BACHELOR OF EDUCATION WITH HONOURS'],
        ['key' => 'awardNameMalay',   'label' => 'Award Name (Malay)',    'placeholder' => 'e.g. SARJANA MUDA PENDIDIKAN DENGAN KEPUJIAN', 'type' => 'textarea'],
        ['key' => 'senateDate',       'label' => 'Senate Date',           'placeholder' => 'e.g. 28 AUGUST 2024'],
        ['key' => 'convocationYear',  'label' => 'Convocation Year',      'placeholder' => 'e.g. 2024'],
    ],
];
?>

<div class="top-stripe"></div>

<nav class="site-nav">
    <a href="<?= $base ?>/" class="nav-brand">
        <svg viewBox="0 0 100 110" xmlns="http://www.w3.org/2000/svg" style="width:32px;height:32px;flex-shrink:0;">
            <path d="M50 5 L92 22 L92 58 C92 80 72 100 50 107 C28 100 8 80 8 58 L8 22 Z" fill="#C4540A"/>
            <path d="M50 5 L92 22 L92 58 C92 80 72 100 50 107 C28 100 8 80 8 58 L8 22 Z" fill="#E8650A" opacity="0.9"/>
            <path d="M50 12 L85 26 L85 58 C85 76 68 94 50 100 C32 94 15 76 15 58 L15 26 Z" fill="#F07A25" opacity="0.5"/>
            <path d="M30 55 L44 70 L72 38" stroke="white" stroke-width="9" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            <circle cx="30" cy="55" r="4" fill="white" opacity="0.9"/>
            <circle cx="72" cy="38" r="4" fill="white" opacity="0.9"/>
            <line x1="72" y1="38" x2="85" y2="28" stroke="white" stroke-width="2.5" stroke-linecap="round" opacity="0.85"/>
            <line x1="85" y1="28" x2="85" y2="20" stroke="white" stroke-width="2.5" stroke-linecap="round" opacity="0.85"/>
            <line x1="85" y1="28" x2="93" y2="28" stroke="white" stroke-width="2.5" stroke-linecap="round" opacity="0.85"/>
            <circle cx="85" cy="20" r="3" fill="white" opacity="0.85"/>
            <circle cx="93" cy="28" r="3" fill="white" opacity="0.85"/>
        </svg>
        <span class="brand-name">Veri<span style="color:var(--orange);">Trust</span></span>
    </a>

    <a href="<?= $base ?>/" class="nav-link active">Dashboard</a>
    <?php if ($role === 'issuer'): ?>
    <a href="<?= $base ?>/documents/upload" class="nav-link">Documents</a>
    <?php endif; ?>
    <a href="<?= $base ?>/trust-registry" class="nav-link" target="_blank">Trust Registry</a>

    <div class="nav-right">
        <span class="role-pill <?= $role ?>"><?= ucfirst($role) ?></span>
        <?php if ($hasKey): ?>
            <span class="pqc-pill">PQC Signing On</span>
        <?php else: ?>
            <span class="pqc-pill off">Verify Only</span>
        <?php endif; ?>
        <div class="nav-user">
            <span class="nav-username"><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></span>
            <a href="<?= $base ?>/logout" class="nav-logout">Logout</a>
        </div>
    </div>
</nav>

<div class="page-wrap">

    <?php if (!empty($flash)): ?>
        <div class="flash <?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>">
            <?= $flash['message'] ?>
        </div>
    <?php endif; ?>

    <div class="page-header">
        <h1 class="page-title">
            <?php if ($role === 'issuer'): ?>Issuer <span>Dashboard</span>
            <?php elseif ($role === 'verifier'): ?>Verifier <span>Dashboard</span>
            <?php else: ?>My <span>Credentials</span>
            <?php endif; ?>
        </h1>
        <span style="font-size:12px;color:var(--ink-light);">Signed in as <strong style="color:var(--ink);"><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></strong></span>
    </div>

    <!-- DID Identity Bar -->
    <div class="identity-bar">
        <span class="identity-label">Your DID</span>
        <span class="identity-did" id="user-did"><?= htmlspecialchars($did, ENT_QUOTES, 'UTF-8') ?></span>
        <button class="btn-copy-did" onclick="copyText(document.getElementById('user-did').textContent, this)">Copy</button>
    </div>

    <?php if ($role === 'issuer'): ?>
    <!-- ══════════════════ ISSUER VIEW ══════════════════════════════════════ -->

    <!-- Issue credential form -->
    <div class="card" style="margin-bottom:28px;">
        <div class="card-head">
            <span class="card-head-title">Issue Verifiable Credential</span>
            <?php if (!$hasKey): ?>
                <span style="font-size:11px;color:var(--red);font-weight:500;">
                    Private key required — re-login with your key to sign
                </span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (empty($holders)): ?>
                <p style="font-size:13px;color:var(--ink-light);">No holders registered yet. Ask recipients to create Holder accounts.</p>
            <?php else: ?>
            <form action="<?= $base ?>/credentials/issue" method="POST" id="issue-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="subject_data" id="subject_data_hidden" />

                <div class="field-row" style="margin-bottom:14px;">
                    <div class="field">
                        <label class="field-label">Recipient (Holder)</label>
                        <select name="holder_id" required>
                            <option value="">— Select holder —</option>
                            <?php foreach ($holders as $h): ?>
                                <option value="<?= (int)$h['id'] ?>">
                                    <?= htmlspecialchars($h['full_name'] ?? $h['username'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?>
                                    (<?= htmlspecialchars($h['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label class="field-label">Credential Type</label>
                        <select name="credential_type" id="credential_type" onchange="switchFields(this.value)">
                            <option value="AcademicCredential">Academic Credential</option>
                        </select>
                    </div>
                </div>

                <div class="auto-serial-notice">
                    <span style="font-size:16px;flex-shrink:0;"></span>
                    <p>
                        <strong>Certificate Serial No. is auto-generated by the system</strong> at issuance time
                        (e.g. <code style="font-family:monospace;color:var(--orange-dark);">88903405</code>).
                        Holders and verifiers use this serial to look up credentials.
                    </p>
                </div>

                <?php foreach ($credentialFields as $credType => $fields): ?>
                <div id="fields-<?= $credType ?>" class="<?= $credType !== 'AcademicCredential' ? 'hidden' : '' ?>">
                    <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.8px;color:var(--ink-light);font-weight:600;margin-bottom:12px;padding-top:4px;border-top:1px solid var(--rule);padding-top:12px;">
                        Credential Subject Fields
                    </div>
                    <div class="field-row">
                    <?php foreach ($fields as $i => $f):
                        if ($i > 0 && $i % 2 === 0) echo '</div><div class="field-row">';
                    ?>
                        <div class="field">
                            <label class="field-label"><?= htmlspecialchars($f['label'], ENT_QUOTES, 'UTF-8') ?></label>
                            <?php if (($f['type'] ?? 'text') === 'textarea'): ?>
                                <textarea data-field-key="<?= $f['key'] ?>" data-cred-type="<?= $credType ?>"
                                          placeholder="<?= htmlspecialchars($f['placeholder'], ENT_QUOTES, 'UTF-8') ?>"
                                          class="subject-field" rows="3"></textarea>
                            <?php else: ?>
                                <input type="text" data-field-key="<?= $f['key'] ?>" data-cred-type="<?= $credType ?>"
                                       placeholder="<?= htmlspecialchars($f['placeholder'], ENT_QUOTES, 'UTF-8') ?>"
                                       class="subject-field" />
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--rule);">
                    <button type="submit" class="btn-primary" <?= !$hasKey ? 'disabled' : '' ?>>
                        Issue &amp; Sign Credential
                    </button>
                    <?php if (!$hasKey): ?>
                        <span style="font-size:12px;color:var(--red);margin-left:14px;">Private key not loaded</span>
                    <?php endif; ?>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Issued credentials table -->
    <div class="section-head">
        <span class="section-title">Issued Credentials</span>
        <span class="count-badge"><?= count($credentials) ?></span>
    </div>

    <?php if (empty($credentials)): ?>
        <div class="empty-state"><p>No credentials issued yet.</p></div>
    <?php else: ?>
    <div class="card" style="overflow:hidden;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Holder</th>
                    <th>Credential Type</th>
                    <th>Cert. Serial No.</th>
                    <th>Status</th>
                    <th>Issued</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($credentials as $c):
                $jld        = json_decode($c['jsonld'] ?? '{}', true) ?: [];
                $type       = implode(', ', array_filter($jld['type'] ?? [], fn($t) => $t !== 'VerifiableCredential'));
                $holderName = $c['holder_name'] ?? 'Unknown';
                $s          = $c['status'] ?? 'issued';
                $credId     = $c['credential_id'] ?? '';
                $subject    = $jld['credentialSubject'] ?? [];
                $certSerial = $subject['certificateSerial'] ?? '—';
            ?>
            <tr>
                <td style="color:var(--ink-light);font-size:12px;"><?= (int)$c['id'] ?></td>
                <td><?= htmlspecialchars($holderName, ENT_QUOTES, 'UTF-8') ?></td>
                <td style="color:var(--ink-mid);font-size:12px;"><?= htmlspecialchars($type ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td><span class="cert-serial"><?= htmlspecialchars($certSerial, ENT_QUOTES, 'UTF-8') ?></span></td>
                <td><span class="status-badge status-<?= $s ?>"><?= ucfirst($s) ?></span></td>
                <td style="color:var(--ink-mid);font-size:12px;"><?= htmlspecialchars(substr($c['issued_at'] ?? '', 0, 10), ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <div style="display:flex;gap:14px;flex-wrap:wrap;align-items:center;">
                        <button class="action-link" onclick="toggleJsonLd(<?= (int)$c['id'] ?>, this)">View JSON-LD</button>
                        <button class="action-link muted" onclick='generateCredentialPDF(<?= htmlspecialchars(json_encode([
                            'id' => $c['id'], 'credId' => $credId, 'certSerial' => $certSerial,
                            'holderName' => $holderName, 'type' => $type,
                            'issuedAt' => substr($c['issued_at'] ?? '', 0, 10),
                            'issuerName' => $jld['issuer']['name'] ?? '',
                            'issuerDid' => $jld['issuer']['id'] ?? '',
                            'holderDid' => $subject['id'] ?? '',
                            'subject' => $subject, 'status' => $s,
                        ]), ENT_QUOTES, 'UTF-8') ?>)'>PDF</button>
                        <?php if ($s !== 'revoked'): ?>
                        <form method="POST" action="<?= $base ?>/credentials/revoke" style="display:inline"
                              onsubmit="return confirm('Revoke this credential? This cannot be undone.')">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>" />
                            <input type="hidden" name="credential_db_id" value="<?= (int)$c['id'] ?>" />
                            <button type="submit" class="action-link danger">Revoke</button>
                        </form>
                        <?php else: ?>
                            <span style="font-size:11px;color:var(--ink-light);text-transform:uppercase;">Revoked</span>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <!-- JSON-LD viewer row -->
            <tr id="jsonld-<?= (int)$c['id'] ?>" style="display:none;">
                <td colspan="7" style="padding:0;">
                    <div class="jsonld-viewer" style="display:block;">
                        <pre><?= htmlspecialchars(json_encode($jld, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?></pre>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php elseif ($role === 'holder'): ?>
    <!-- ══════════════════ HOLDER VIEW ══════════════════════════════════════ -->

    <div class="section-head">
        <span class="section-title">My Credentials</span>
        <span class="count-badge"><?= count($credentials) ?></span>
    </div>

    <?php if (empty($credentials)): ?>
        <div class="empty-state">
            <p>No credentials issued to you yet. Your issuer will add credentials to your account.</p>
        </div>
    <?php else: ?>
        <?php foreach ($credentials as $c):
            $jld        = json_decode($c['jsonld'] ?? '{}', true) ?: [];
            $subject    = $jld['credentialSubject'] ?? [];
            $types      = array_filter($jld['type'] ?? [], fn($t) => $t !== 'VerifiableCredential');
            $type       = implode(', ', $types);
            $s          = $c['status'] ?? 'issued';
            $issuerName = $c['issuer_name'] ?? 'Unknown';
            $issuerDid  = $jld['issuer']['id'] ?? '—';
            $credId     = $c['credential_id'] ?? '';
            $certSerial = $subject['certificateSerial'] ?? '—';
        ?>
        <div class="cred-card">
            <div class="cred-card-head">
                <div>
                    <div class="cred-type"><?= htmlspecialchars($type ?: 'Verifiable Credential', ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="cred-meta">
                        Issued by <strong style="color:var(--ink-mid);"><?= htmlspecialchars($issuerName, ENT_QUOTES, 'UTF-8') ?></strong>
                        &nbsp;&middot;&nbsp;
                        <?= htmlspecialchars(substr($c['issued_at'] ?? '', 0, 10), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>
                <span class="status-badge status-<?= $s ?>"><?= $s === 'revoked' ? 'Revoked' : ucfirst($s) ?></span>
            </div>

            <div class="cred-serial-bar">
                <div>
                    <div class="serial-label">Certificate Serial Number</div>
                    <div class="serial-value"><?= htmlspecialchars($certSerial, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <button class="btn-copy-did" onclick="copyText('<?= htmlspecialchars($certSerial, ENT_QUOTES, 'UTF-8') ?>', this)" style="font-size:11px;">Copy Serial</button>
            </div>

            <div class="cred-fields">
                <?php foreach ($subject as $key => $val):
                    if ($key === 'id' || $key === 'certificateSerial') continue;
                    $label = ucfirst(preg_replace('/([A-Z])/', ' $1', $key));
                ?>
                <div class="field-row-display">
                    <span class="frd-key"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="frd-val"><?= htmlspecialchars(is_array($val) ? json_encode($val) : (string)$val, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="cred-actions">
                <button class="action-link" onclick="toggleJsonLd(<?= (int)$c['id'] ?>, this)">View JSON-LD</button>
                <button class="action-link muted" onclick='generateCredentialPDF(<?= htmlspecialchars(json_encode([
                    'id' => $c['id'], 'credId' => $credId, 'certSerial' => $certSerial,
                    'holderName' => $subject['name'] ?? '',
                    'type' => $type, 'issuedAt' => substr($c['issued_at'] ?? '', 0, 10),
                    'issuerName' => $issuerName, 'issuerDid' => $issuerDid,
                    'holderDid' => $subject['id'] ?? '',
                    'subject' => $subject, 'status' => $s,
                ]), ENT_QUOTES, 'UTF-8') ?>)'>Download Certificate PDF</button>
            </div>

            <div id="jsonld-<?= (int)$c['id'] ?>" style="display:none;">
                <div class="jsonld-viewer" style="display:block;">
                    <pre><?= htmlspecialchars(json_encode($jld, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?></pre>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php elseif ($role === 'verifier'): ?>
    <!-- ══════════════════ VERIFIER VIEW ════════════════════════════════════ -->

    <div class="field-row" style="gap:32px;align-items:flex-start;">
        <!-- Left: form -->
        <div style="flex:0 0 380px;">
            <div class="card">
                <div class="card-head">
                    <span class="card-head-title">Verify Credential</span>
                </div>
                <div class="card-body">
                    <form action="<?= $base ?>/credentials/verify" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>" />

                        <p style="font-size:13px;color:var(--ink-mid);margin-bottom:16px;line-height:1.6;">
                            Enter the <strong style="color:var(--ink);">Certificate Serial Number</strong> printed
                            on the holder's certificate (8-digit number).
                        </p>

                        <input type="text" name="certificate_serial" class="verify-input"
                               required inputmode="numeric" pattern="[0-9]*"
                               placeholder="00000000" autocomplete="off" />
                        <p class="verify-hint">e.g. 88903405</p>

                        <button type="submit" class="btn-verify">Verify Credential</button>
                    </form>
                </div>
            </div>

            <!-- How to use -->
            <div style="padding:16px;background:var(--white);border:1px solid var(--rule);margin-top:12px;">
                <p style="font-size:10px;text-transform:uppercase;letter-spacing:0.8px;color:var(--ink-light);font-weight:600;margin-bottom:8px;">How Verification Works</p>
                <ol style="font-size:12px;color:var(--ink-mid);line-height:1.8;padding-left:16px;">
                    <li>Obtain the 8-digit serial from the holder's certificate</li>
                    <li>Enter it above and click Verify</li>
                    <li>The system checks the SHA-256 hash integrity</li>
                    <li>The system validates the KAZ-SIGN-128 PQC signature</li>
                    <li>Results show authenticity &amp; current status</li>
                </ol>
            </div>
        </div>

        <!-- Right: result -->
        <?php if (!empty($result)):
            $cred    = $result['credential'] ?? [];
            $jld     = $result['jsonld'] ?? [];
            $subj    = $jld['credentialSubject'] ?? [];
            $types   = array_filter($jld['type'] ?? [], fn($t) => $t !== 'VerifiableCredential');
            $revoked = $result['revoked'] ?? false;
            $verified = $result['verified'] ?? false;
            $headClass = $revoked ? 'revoked' : ($verified ? 'pass' : 'fail');
        ?>
        <div style="flex:1;">
            <div class="verify-result">
                <div class="verify-result-head <?= $headClass ?>">
                    <span class="verify-result-icon"><?= $revoked ? '' : ($verified ? '' : '') ?></span>
                    <div class="verify-result-text">
                        <h3><?= $revoked ? 'Credential Revoked' : ($verified ? 'Credential Valid' : 'Verification Failed') ?></h3>
                        <p>
                            <?= $revoked ? 'This credential was cancelled by the issuer.'
                              : ($verified ? 'Signature and integrity checks passed.'
                              : 'One or more verification checks failed.') ?>
                        </p>
                    </div>
                </div>

                <?php if (!$revoked): ?>
                <div class="verify-checks">
                    <div class="check-item">
                        <span class="check-item-label">SHA-256 Hash Integrity</span>
                        <span class="<?= ($result['hash_intact'] ?? false) ? 'check-pass' : 'check-fail' ?>">
                            <?= ($result['hash_intact'] ?? false) ? 'Intact' : 'Modified' ?>
                        </span>
                    </div>
                    <div class="check-item">
                        <span class="check-item-label">KAZ-SIGN PQC Signature</span>
                        <span class="<?= ($result['signature_valid'] ?? false) ? 'check-pass' : 'check-fail' ?>">
                            <?= ($result['signature_valid'] ?? false) ? 'Valid' : 'Invalid' ?>
                        </span>
                    </div>
                </div>
                <?php endif; ?>

                <div class="verify-details">
                    <p class="details-title">Credential Details</p>
                    <?php
                    $detailRows = [
                        ['Cert. Serial No.', $subj['certificateSerial'] ?? '—'],
                        ['Credential Type',  implode(', ', $types) ?: '—'],
                        ['Issued By',        $jld['issuer']['name'] ?? '—'],
                        ['Issuer DID',       $jld['issuer']['id'] ?? '—'],
                        ['Issue Date',       substr($cred['issued_at'] ?? '', 0, 10)],
                        ['Status',           ucfirst($cred['status'] ?? '—')],
                    ];
                    foreach ($subj as $k => $v) {
                        if (in_array($k, ['id', 'certificateSerial'])) continue;
                        $detailRows[] = [ucfirst(preg_replace('/([A-Z])/', ' $1', $k)), is_array($v) ? json_encode($v) : (string)$v];
                    }
                    foreach ($detailRows as [$k, $v]): ?>
                    <div class="field-row-display">
                        <span class="frd-key"><?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="frd-val" style="font-size:<?= strlen($v) > 60 ? '10px' : '13px' ?>;font-family:<?= strlen($v) > 40 ? 'monospace' : 'inherit' ?>;">
                            <?= htmlspecialchars($v, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <?php endforeach; ?>

                    <div style="margin-top:14px;padding-top:12px;border-top:1px solid var(--rule);">
                        <button class="action-link" onclick="toggleJsonLd('verify-result', this)">&#9654; View Full JSON-LD</button>
                    </div>
                    <div id="jsonld-verify-result" style="display:none;margin-top:10px;">
                        <pre style="font-family:monospace;font-size:10px;color:#4A2600;line-height:1.7;white-space:pre-wrap;word-break:break-all;max-height:280px;overflow-y:auto;background:#F7F7F7;padding:14px;"><?= htmlspecialchars(json_encode($jld, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?></pre>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div style="flex:1;display:flex;align-items:center;justify-content:center;min-height:240px;">
            <p style="font-size:14px;color:var(--ink-light);text-align:center;">
                Enter a certificate serial number on the left to verify a credential.
            </p>
        </div>
        <?php endif; ?>
    </div>

    <?php endif; ?>

</div>

<!-- Hidden QR container -->
<div id="qr-gen"></div>

<footer class="site-footer">
    <span>VeriTrust &mdash; Post-Quantum Digital Credentials</span>
    <span>KAZ-SIGN-128 Algorithm &nbsp;&middot;&nbsp; <?= date('Y') ?></span>
</footer>

<script>
function switchFields(type) {
    document.querySelectorAll('[id^="fields-"]').forEach(el => el.classList.add('hidden'));
    const t = document.getElementById('fields-' + type);
    if (t) t.classList.remove('hidden');
}

document.getElementById('issue-form')?.addEventListener('submit', function() {
    const credType  = document.getElementById('credential_type').value;
    const container = document.getElementById('fields-' + credType);
    const obj = {};
    if (container) {
        container.querySelectorAll('.subject-field').forEach(el => {
            const key = el.dataset.fieldKey;
            const val = el.value.trim();
            if (key && val !== '') obj[key] = val;
        });
    }
    document.getElementById('subject_data_hidden').value = JSON.stringify(obj);
});

function toggleJsonLd(id, btn) {
    const el = document.getElementById('jsonld-' + id);
    const hidden = el.style.display === 'none';
    el.style.display = hidden ? 'block' : 'none';
    if (btn) btn.textContent = btn.textContent
        .replace('View','Hide').replace('▶','▼')
        .replace('Hide JSON-LD', 'View JSON-LD')
        .replace('▼', hidden ? '▼' : '▶');
    if (btn && !btn.textContent.includes('JSON')) {
        btn.textContent = hidden ? '▼ Hide Full JSON-LD' : '▶ View Full JSON-LD';
    }
}

function copyText(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.textContent;
        btn.textContent = 'Copied';
        setTimeout(() => btn.textContent = orig, 2000);
    });
}

async function generateCredentialPDF(data) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ unit: 'mm', format: 'a4' });
    const pageW = 210, margin = 18, colW = pageW - margin * 2;
    let y = 0;

    /* Orange header stripe */
    doc.setFillColor(232, 101, 10);
    doc.rect(0, 0, pageW, 16, 'F');
    doc.setFillColor(255, 255, 255);
    doc.rect(0, 16, pageW, 28, 'F');

    doc.setTextColor(255, 255, 255);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(11);
    doc.text('KAZ-SIGN SYSTEM', margin, 10.5);
    doc.setFontSize(7);
    doc.setFont('helvetica', 'normal');
    doc.text('POST-QUANTUM DIGITAL CREDENTIAL AUTHORITY', pageW - margin, 10.5, { align: 'right' });

    /* Title area */
    doc.setTextColor(30, 30, 30);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(16);
    doc.text((data.type || 'Verifiable Credential').toUpperCase(), margin, 28);

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(120, 120, 120);
    doc.text('ISSUED UNDER KAZ-SIGN-128 POST-QUANTUM CRYPTOGRAPHY', margin, 33);

    /* Status */
    const sc = data.status === 'verified' ? [26,122,74]
             : data.status === 'revoked'  ? [160,64,0]
             : [26,79,154];
    doc.setFillColor(...sc);
    doc.roundedRect(pageW - margin - 28, 20, 28, 9, 1, 1, 'F');
    doc.setTextColor(255,255,255);
    doc.setFontSize(7);
    doc.setFont('helvetica', 'bold');
    doc.text(data.status.toUpperCase(), pageW - margin - 14, 25.5, { align: 'center' });

    y = 44;

    /* Horizontal rule */
    doc.setDrawColor(230, 230, 230);
    doc.setLineWidth(0.3);
    doc.line(margin, y, pageW - margin, y);
    y += 8;

    /* Certificate Serial (prominent) */
    doc.setFillColor(255, 240, 230);
    doc.rect(margin, y, colW, 20, 'F');
    doc.setDrawColor(232, 101, 10);
    doc.setLineWidth(0.5);
    doc.line(margin, y, margin, y + 20);
    doc.setTextColor(120, 60, 0);
    doc.setFontSize(8);
    doc.setFont('helvetica', 'normal');
    doc.text('CERTIFICATE SERIAL NUMBER', margin + 8, y + 7);
    doc.setFont('courier', 'bold');
    doc.setFontSize(18);
    doc.setTextColor(196, 84, 10);
    doc.text(data.certSerial || '—', margin + 8, y + 16);
    y += 26;

    /* QR Code */
    const qrDiv = document.getElementById('qr-gen');
    qrDiv.innerHTML = '';
    await new Promise(resolve => {
        new QRCode(qrDiv, {
            text: data.certSerial || data.credId,
            width: 180, height: 180,
            colorDark: '#E8650A', colorLight: '#ffffff',
        });
        setTimeout(resolve, 300);
    });
    const qrCanvas = qrDiv.querySelector('canvas');
    const qrSize = 38;
    if (qrCanvas) {
        doc.addImage(qrCanvas.toDataURL('image/png'), 'PNG', pageW - margin - qrSize, y - 4, qrSize, qrSize);
        doc.setFontSize(6);
        doc.setTextColor(150, 150, 150);
        doc.text('Scan to verify', pageW - margin - qrSize / 2, y + qrSize - 1, { align: 'center' });
    }

    /* Details table */
    const detailW = colW - qrSize - 8;
    const fields = [];
    if (data.holderName) fields.push(['Holder Name', data.holderName]);
    if (data.issuerName) fields.push(['Issued By', data.issuerName]);
    fields.push(['Issue Date', data.issuedAt]);
    if (data.subject) {
        Object.entries(data.subject).forEach(([k,v]) => {
            if (['id','certificateSerial','name'].includes(k) || !v) return;
            const label = k.replace(/([A-Z])/g, ' $1').replace(/^./, s => s.toUpperCase());
            fields.push([label, String(v)]);
        });
    }

    doc.setFont('helvetica', 'normal');
    fields.forEach(([lbl, val]) => {
        doc.setFontSize(7.5);
        doc.setTextColor(120, 120, 120);
        doc.text(lbl.toUpperCase(), margin, y);
        y += 5;
        doc.setFontSize(10);
        doc.setTextColor(26, 26, 26);
        doc.setFont('helvetica', 'bold');
        const lines = doc.splitTextToSize(val, detailW);
        doc.text(lines, margin, y);
        y += lines.length * 5 + 4;
        doc.setFont('helvetica', 'normal');
    });

    y = Math.max(y, 44 + 26 + qrSize + 8);
    y += 4;

    /* DID section */
    doc.setDrawColor(230,230,230);
    doc.line(margin, y, pageW - margin, y);
    y += 8;
    doc.setFontSize(9); doc.setFont('helvetica', 'bold'); doc.setTextColor(26,26,26);
    doc.text('Decentralized Identifiers', margin, y); y += 6;
    [['Issuer DID', data.issuerDid], ['Holder DID', data.holderDid]].forEach(([lbl, did]) => {
        if (!did || did === '—') return;
        doc.setFontSize(7.5); doc.setTextColor(120,120,120); doc.text(lbl, margin, y); y += 4;
        doc.setFontSize(7.5); doc.setTextColor(196,84,10); doc.setFont('courier','normal');
        const lines = doc.splitTextToSize(did, colW);
        doc.text(lines, margin, y); y += lines.length * 4.5 + 3;
        doc.setFont('helvetica','normal');
    });

    y += 2;
    /* PQC notice */
    doc.setFillColor(255,248,235);
    doc.setDrawColor(224,168,0);
    doc.setLineWidth(0.3);
    doc.rect(margin, y, colW, 14, 'FD');
    doc.setFontSize(7.5); doc.setFont('helvetica','bold'); doc.setTextColor(90,60,0);
    doc.text('Post-Quantum Cryptography (PQC) — KAZ-SIGN-128', margin + 4, y + 6);
    doc.setFont('helvetica','normal'); doc.setFontSize(7); doc.setTextColor(120,80,0);
    doc.text('This credential is signed using KAZ-SIGN-128, a quantum-resistant digital signature algorithm.', margin + 4, y + 11);
    y += 20;

    /* Footer */
    doc.setFillColor(245,245,245);
    doc.rect(0, 280, pageW, 17, 'F');
    doc.setDrawColor(220,220,220);
    doc.line(0, 280, pageW, 280);
    doc.setFontSize(7); doc.setFont('helvetica','normal'); doc.setTextColor(150,150,150);
    doc.text('KAZ-SIGN Digital Credential  |  ' + (data.certSerial || data.credId), margin, 287);
    doc.text('Generated: ' + new Date().toISOString().slice(0,19).replace('T',' '), pageW - margin, 287, { align: 'right' });
    doc.text('Verify at: ' + window.location.origin + '  |  Algorithm: KAZ-SIGN-128 PQC', pageW / 2, 293, { align: 'center' });

    doc.save('KAZ-SIGN-Certificate-' + (data.certSerial || data.holderName || 'cred').replace(/\s+/g,'-') + '.pdf');
}
</script>
</body>
</html>