<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign In — VeriTrust</title>
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
            --green:        #1A7A4A;
            --red:          #C0392B;
            --red-bg:       #FDF0EF;
        }
        body {
            font-family: 'Barlow', sans-serif;
            background: var(--surface);
            color: var(--ink);
            min-height: 100vh;
            display: flex; flex-direction: column;
        }
        .top-stripe { height: 4px; background: var(--orange); }

        /* Header */
        .site-header {
            background: var(--white);
            border-bottom: 1px solid var(--rule);
            padding: 0 48px; height: 62px;
            display: flex; align-items: center; gap: 12px;
        }
        .logo-lockup {
            display: flex; align-items: center; gap: 10px; text-decoration: none;
        }
        .logo-lockup svg { width: 38px; height: 38px; flex-shrink: 0; }
        .logo-wordmark {
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 700; font-size: 22px; letter-spacing: 1px;
            color: var(--ink); text-transform: uppercase; line-height: 1;
        }
        .logo-wordmark em {
            font-style: normal; color: var(--orange);
        }
        .header-tagline {
            margin-left: auto; font-size: 11px; color: var(--ink-light);
            letter-spacing: 0.5px; text-transform: uppercase;
        }

        /* Page */
        .page-content {
            flex: 1; display: flex; align-items: center;
            justify-content: center; padding: 48px 24px;
        }
        .login-wrap { width: 100%; max-width: 420px; }

        .page-title {
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 700; font-size: 28px; letter-spacing: 1px;
            color: var(--ink); text-transform: uppercase; margin-bottom: 4px;
        }
        .page-subtitle { font-size: 13px; color: var(--ink-light); margin-bottom: 28px; }

        .flash {
            padding: 12px 16px; border-radius: 2px; font-size: 13px;
            margin-bottom: 20px; border-left: 3px solid;
        }
        .flash.error   { background: var(--red-bg); color: var(--red); border-color: var(--red); }
        .flash.success { background: #EFF9F4; color: var(--green); border-color: var(--green); }
        .flash.warning { background: #FFF8E6; color: #7A5A00; border-color: #E0A800; }

        .form-card {
            background: var(--white);
            border: 1px solid var(--rule);
            border-top: 3px solid var(--orange);
            padding: 32px;
        }
        .field { margin-bottom: 18px; }
        .field-label {
            display: block; font-size: 11px; font-weight: 500;
            text-transform: uppercase; letter-spacing: 0.8px;
            color: var(--ink-mid); margin-bottom: 6px;
        }
        .field input, .field textarea {
            width: 100%; border: 1px solid var(--rule);
            background: var(--white); padding: 10px 12px;
            font-size: 14px; font-family: 'Barlow', sans-serif;
            color: var(--ink); outline: none; border-radius: 2px;
            transition: border-color 0.15s;
        }
        .field input:focus, .field textarea:focus {
            border-color: var(--orange);
            box-shadow: 0 0 0 3px rgba(232,101,10,0.08);
        }
        .field textarea { resize: none; font-size: 12px; font-family: monospace; line-height: 1.6; }

        .key-info {
            background: var(--orange-light); border: 1px solid #F5C89C;
            padding: 12px 14px; margin-top: 8px;
        }
        .key-info p { font-size: 11px; color: #7A3800; line-height: 1.6; }

        .btn-primary {
            width: 100%; background: var(--orange); color: var(--white);
            border: none; padding: 12px;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 14px; font-weight: 600; letter-spacing: 1.5px;
            text-transform: uppercase; cursor: pointer;
            transition: background 0.15s; margin-top: 8px; border-radius: 2px;
        }
        .btn-primary:hover { background: var(--orange-dark); }

        .form-footer {
            text-align: center; margin-top: 20px;
            font-size: 13px; color: var(--ink-light);
        }
        .form-footer a { color: var(--orange); text-decoration: none; font-weight: 500; }
        .form-footer a:hover { text-decoration: underline; }

        .role-legend {
            background: var(--white); border: 1px solid var(--rule);
            padding: 16px 20px; margin-top: 16px;
        }
        .role-legend-title {
            font-size: 10px; text-transform: uppercase; letter-spacing: 0.8px;
            color: var(--ink-light); font-weight: 500; margin-bottom: 10px;
        }
        .role-row { display: flex; gap: 8px; align-items: flex-start; margin-bottom: 6px; }
        .role-label {
            font-weight: 500; color: var(--orange); min-width: 64px;
            font-size: 11px; text-transform: uppercase;
        }
        .role-desc { font-size: 12px; color: var(--ink-mid); }

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
    <!-- VeriTrust Shield Logo -->
    <a class="logo-lockup" href="<?= $base ?>/">
        <svg viewBox="0 0 100 110" xmlns="http://www.w3.org/2000/svg">
            <!-- Shield body - dark orange base -->
            <path d="M50 5 L92 22 L92 58 C92 80 72 100 50 107 C28 100 8 80 8 58 L8 22 Z" fill="#C4540A"/>
            <!-- Shield body - main orange -->
            <path d="M50 5 L92 22 L92 58 C92 80 72 100 50 107 C28 100 8 80 8 58 L8 22 Z" fill="#E8650A" opacity="0.9"/>
            <!-- Shield inner highlight -->
            <path d="M50 12 L85 26 L85 58 C85 76 68 94 50 100 C32 94 15 76 15 58 L15 26 Z" fill="#F07A25" opacity="0.5"/>
            <!-- Shield dark bottom -->
            <path d="M50 107 C28 100 8 80 8 58 L8 70 C8 88 28 104 50 110 Z" fill="#993D00" opacity="0.4"/>
            <!-- Checkmark body -->
            <path d="M30 55 L44 70 L72 38" stroke="white" stroke-width="9" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            <!-- Circuit dots on checkmark -->
            <circle cx="30" cy="55" r="4" fill="white" opacity="0.9"/>
            <circle cx="72" cy="38" r="4" fill="white" opacity="0.9"/>
            <!-- Circuit lines emanating top-right -->
            <line x1="72" y1="38" x2="85" y2="28" stroke="white" stroke-width="2.5" stroke-linecap="round" opacity="0.85"/>
            <line x1="85" y1="28" x2="85" y2="20" stroke="white" stroke-width="2.5" stroke-linecap="round" opacity="0.85"/>
            <line x1="85" y1="28" x2="93" y2="28" stroke="white" stroke-width="2.5" stroke-linecap="round" opacity="0.85"/>
            <!-- Circuit endpoint dots -->
            <circle cx="85" cy="20" r="3" fill="white" opacity="0.85"/>
            <circle cx="93" cy="28" r="3" fill="white" opacity="0.85"/>
        </svg>
        <span class="logo-wordmark">Veri<em>Trust</em></span>
    </a>
    <span class="header-tagline">Post-Quantum Credential System</span>
</header>

<div class="page-content">
    <div class="login-wrap">
        <h1 class="page-title">Sign In</h1>
        <p class="page-subtitle">Enter your credentials to access the VeriTrust portal</p>

        <?php if (!empty($flash)): ?>
            <div class="flash <?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>">
                <?= strip_tags($flash['message'], '<a><strong><code>') ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <form action="<?= $base ?>/login" method="POST">
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>" />

                <div class="field">
                    <label class="field-label">Username</label>
                    <input name="username" type="text" required autocomplete="username"
                           value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
                </div>

                <div class="field">
                    <label class="field-label">Password</label>
                    <input name="password" type="password" required autocomplete="current-password" />
                </div>

                <div class="field">
                    <label class="field-label">Private Key
                        <span style="font-size:10px;font-weight:400;color:var(--ink-light);text-transform:none;letter-spacing:0;">
                            (Issuer only — leave blank for Holder / Verifier)
                        </span>
                    </label>
                    <textarea name="private_key" rows="4"
                              placeholder="KAZSIGN-PRV-v1::paste your key here..."></textarea>
                    <div class="key-info">
                        <p><strong>Where is my private key?</strong><br/>
                        Shown once after registration — starts with <code>KAZSIGN-PRV-v1::</code><br/>
                        <strong>Issuer:</strong> Required to sign credentials. &nbsp;
                        <strong>Holder / Verifier:</strong> Leave blank.</p>
                    </div>
                </div>

                <button type="submit" class="btn-primary">Sign In to VeriTrust</button>
            </form>
        </div>

        <p class="form-footer">
            No account? <a href="<?= $base ?>/register">Create one here</a>
        </p>

        <div class="role-legend">
            <p class="role-legend-title">Role Access Reference</p>
            <div class="role-row">
                <span class="role-label">Issuer</span>
                <span class="role-desc">Signs and issues verifiable credentials to holders</span>
            </div>
            <div class="role-row">
                <span class="role-label">Holder</span>
                <span class="role-desc">Receives credentials and downloads certificates</span>
            </div>
            <div class="role-row">
                <span class="role-label">Verifier</span>
                <span class="role-desc">Validates credentials via certificate serial number</span>
            </div>
        </div>
    </div>
</div>

<footer class="site-footer">
    <span>VeriTrust &mdash; Post-Quantum Digital Credentials</span>
    <span>KAZ-SIGN-128 Algorithm</span>
</footer>
</body>
</html>