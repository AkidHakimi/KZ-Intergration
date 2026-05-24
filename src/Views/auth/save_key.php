<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Save Private Key — VeriTrust</title>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700&family=Barlow:wght@400;500&display=swap" rel="stylesheet" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --orange:       #E8650A;
            --orange-light: #FFF0E6;
            --orange-dark:  #C4540A;
            --ink:          #1A1A1A;
            --ink-mid:      #555555;
            --ink-light:    #888888;
            --rule:         #E0E0E0;
            --surface:      #FAFAFA;
            --white:        #FFFFFF;
        }
        body {
            font-family: 'Barlow', sans-serif; background: var(--surface); color: var(--ink);
            min-height: 100vh; display: flex; flex-direction: column;
        }
        .top-stripe { height: 4px; background: var(--orange); }
        .site-header {
            background: var(--white); border-bottom: 1px solid var(--rule);
            padding: 0 48px; height: 62px; display: flex; align-items: center; gap: 12px;
        }
        .logo-lockup { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .logo-lockup svg { width: 38px; height: 38px; flex-shrink: 0; }
        .logo-wordmark {
            font-family: 'Barlow Condensed', sans-serif; font-weight: 700;
            font-size: 22px; letter-spacing: 1px; color: var(--ink);
            text-transform: uppercase; line-height: 1;
        }
        .logo-wordmark em { font-style: normal; color: var(--orange); }
        .step-indicator {
            margin-left: auto; font-size: 11px; color: var(--ink-light);
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .page-content {
            flex: 1; display: flex; align-items: center;
            justify-content: center; padding: 48px 24px;
        }
        .content-wrap { width: 100%; max-width: 560px; }
        .alert-banner {
            background: #FFF8E6; border: 1px solid #F0C060;
            border-left: 4px solid #E0A800;
            padding: 16px 20px; display: flex; gap: 14px; margin-bottom: 24px;
        }
        .alert-icon { font-size: 20px; flex-shrink: 0; }
        .alert-body h3 {
            font-family: 'Barlow Condensed', sans-serif; font-weight: 700;
            font-size: 16px; letter-spacing: 0.5px; color: #5A3E00;
            margin-bottom: 4px; text-transform: uppercase;
        }
        .alert-body p { font-size: 12px; color: #7A5A00; line-height: 1.6; }
        .key-card {
            background: var(--white); border: 1px solid var(--rule);
            border-top: 3px solid var(--orange); padding: 32px; margin-bottom: 24px;
        }
        .key-card-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 14px;
        }
        .key-card-title {
            font-family: 'Barlow Condensed', sans-serif; font-weight: 700;
            font-size: 16px; letter-spacing: 1px; text-transform: uppercase; color: var(--ink);
        }
        .btn-copy {
            background: var(--white); border: 1px solid var(--rule);
            color: var(--ink-mid); font-family: 'Barlow Condensed', sans-serif;
            font-size: 12px; font-weight: 600; letter-spacing: 0.5px;
            text-transform: uppercase; padding: 6px 14px; cursor: pointer;
            transition: all 0.12s; border-radius: 2px;
        }
        .btn-copy:hover { border-color: var(--orange); color: var(--orange); }
        .btn-copy.copied { border-color: #1A7A4A; color: #1A7A4A; }
        .key-display {
            width: 100%; background: #F7F7F7; border: 1px solid var(--rule);
            padding: 16px; font-family: 'Courier New', monospace;
            font-size: 11px; line-height: 1.8; color: var(--orange-dark);
            word-break: break-all; white-space: pre-wrap; resize: none; outline: none;
        }
        .key-tip { font-size: 11px; color: var(--ink-light); margin-top: 8px; }
        .confirm-card {
            background: var(--white); border: 1px solid var(--rule); padding: 28px;
        }
        .confirm-check-row {
            display: flex; gap: 12px; align-items: flex-start; margin-bottom: 20px;
        }
        .confirm-check-row input[type="checkbox"] {
            width: 18px; height: 18px; margin-top: 2px; flex-shrink: 0;
            accent-color: var(--orange); cursor: pointer;
        }
        .confirm-check-row label {
            font-size: 13px; color: var(--ink-mid); line-height: 1.6; cursor: pointer;
        }
        .confirm-check-row label strong { color: var(--ink); font-weight: 500; }
        .btn-continue {
            width: 100%; background: var(--orange); color: var(--white);
            border: none; padding: 13px;
            font-family: 'Barlow Condensed', sans-serif; font-size: 15px;
            font-weight: 700; letter-spacing: 2px; text-transform: uppercase;
            cursor: pointer; transition: background 0.15s; border-radius: 2px;
        }
        .btn-continue:hover { background: var(--orange-dark); }
        .site-footer {
            background: var(--white); border-top: 1px solid var(--rule);
            padding: 14px 48px; display: flex; justify-content: space-between;
        }
        .site-footer span { font-size: 11px; color: var(--ink-light); }
    </style>
</head>
<body>
<div class="top-stripe"></div>

<header class="site-header">
    <a class="logo-lockup" href="<?= $base ?>/">
        <svg viewBox="0 0 100 110" xmlns="http://www.w3.org/2000/svg">
            <path d="M50 5 L92 22 L92 58 C92 80 72 100 50 107 C28 100 8 80 8 58 L8 22 Z" fill="#C4540A"/>
            <path d="M50 5 L92 22 L92 58 C92 80 72 100 50 107 C28 100 8 80 8 58 L8 22 Z" fill="#E8650A" opacity="0.9"/>
            <path d="M50 12 L85 26 L85 58 C85 76 68 94 50 100 C32 94 15 76 15 58 L15 26 Z" fill="#F07A25" opacity="0.5"/>
            <path d="M50 107 C28 100 8 80 8 58 L8 70 C8 88 28 104 50 110 Z" fill="#993D00" opacity="0.4"/>
            <path d="M30 55 L44 70 L72 38" stroke="white" stroke-width="9" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            <circle cx="30" cy="55" r="4" fill="white" opacity="0.9"/>
            <circle cx="72" cy="38" r="4" fill="white" opacity="0.9"/>
            <line x1="72" y1="38" x2="85" y2="28" stroke="white" stroke-width="2.5" stroke-linecap="round" opacity="0.85"/>
            <line x1="85" y1="28" x2="85" y2="20" stroke="white" stroke-width="2.5" stroke-linecap="round" opacity="0.85"/>
            <line x1="85" y1="28" x2="93" y2="28" stroke="white" stroke-width="2.5" stroke-linecap="round" opacity="0.85"/>
            <circle cx="85" cy="20" r="3" fill="white" opacity="0.85"/>
            <circle cx="93" cy="28" r="3" fill="white" opacity="0.85"/>
        </svg>
        <span class="logo-wordmark">Veri<em>Trust</em></span>
    </a>
    <span class="step-indicator">Step 2 of 2 &mdash; Save Your Key</span>
</header>

<div class="page-content">
    <div class="content-wrap">
        <div class="alert-banner">
            <span class="alert-icon">⚠</span>
            <div class="alert-body">
                <h3>Action Required — Save This Key Now</h3>
                <p>Your private key is displayed below <strong>one time only</strong>. It is never stored on this server.
                If you lose it, you will need to register a new account to sign credentials again.</p>
            </div>
        </div>

        <div class="key-card">
            <div class="key-card-header">
                <span class="key-card-title">Your Private Key (KAZ-SIGN-128)</span>
                <button class="btn-copy" id="copy-btn" onclick="copyKey()">Copy Key</button>
            </div>
            <textarea id="key-display" class="key-display" rows="6" readonly
                      onclick="this.select()"><?= htmlspecialchars($private_key ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            <p class="key-tip">&#8594; Click the key to select all. Save to a password manager or encrypted file immediately.</p>
        </div>

        <div class="confirm-card">
            <form action="<?= $base ?>/key/confirm" method="POST">
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>" />
                <div class="confirm-check-row">
                    <input type="checkbox" id="saved-confirm" required />
                    <label for="saved-confirm">
                        I confirm I have <strong>saved my private key</strong> in a secure location.
                        I understand it cannot be recovered if lost.
                    </label>
                </div>
                <button type="submit" class="btn-continue">Continue to Dashboard</button>
            </form>
        </div>
    </div>
</div>

<footer class="site-footer">
    <span>VeriTrust &mdash; Post-Quantum Digital Credentials</span>
    <span>Role: <?= htmlspecialchars(ucfirst($role ?? 'user'), ENT_QUOTES, 'UTF-8') ?></span>
</footer>

<script>
function copyKey() {
    const ta = document.getElementById('key-display');
    const btn = document.getElementById('copy-btn');
    ta.select();
    navigator.clipboard.writeText(ta.value).then(() => {
        btn.textContent = '✓ Copied!';
        btn.classList.add('copied');
        setTimeout(() => { btn.textContent = 'Copy Key'; btn.classList.remove('copied'); }, 2500);
    });
}
</script>
</body>
</html>