<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register — KAZ-SIGN</title>
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
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Barlow', sans-serif;
            background: var(--surface);
            color: var(--ink);
            min-height: 100vh;
            display: flex; flex-direction: column;
        }
        .top-stripe { height: 4px; background: var(--orange); }
        .site-header {
            background: var(--white);
            border-bottom: 1px solid var(--rule);
            padding: 0 48px; height: 60px;
            display: flex; align-items: center; gap: 12px;
        }
        .logo-mark {
            width: 32px; height: 32px; background: var(--orange);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 700; font-size: 14px; color: var(--white);
        }
        .brand {
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 600; font-size: 18px; letter-spacing: 2px;
            color: var(--ink); text-transform: uppercase;
        }
        .tagline {
            margin-left: auto; font-size: 11px; color: var(--ink-light);
            letter-spacing: 0.5px; text-transform: uppercase;
        }
        .page-content {
            flex: 1; display: flex; align-items: flex-start;
            justify-content: center; padding: 48px 24px;
        }
        .form-wrap { width: 100%; max-width: 500px; }
        .page-title {
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 700; font-size: 28px; letter-spacing: 1px;
            color: var(--ink); text-transform: uppercase; margin-bottom: 4px;
        }
        .page-subtitle { font-size: 13px; color: var(--ink-light); margin-bottom: 28px; }

        .flash {
            padding: 12px 16px; border-radius: 3px; font-size: 13px;
            margin-bottom: 20px; border-left: 3px solid;
        }
        .flash.error   { background: var(--red-bg); color: var(--red); border-color: var(--red); }
        .flash.success { background: #EFF9F4; color: var(--green); border-color: var(--green); }

        .form-card {
            background: var(--white);
            border: 1px solid var(--rule);
            border-top: 3px solid var(--orange);
            padding: 32px;
        }

        /* Role selector */
        .role-selector { margin-bottom: 24px; }
        .role-selector-label {
            font-size: 11px; font-weight: 500; text-transform: uppercase;
            letter-spacing: 0.8px; color: var(--ink-mid); margin-bottom: 8px; display: block;
        }
        .role-tabs { display: grid; grid-template-columns: 1fr 1fr 1fr; border: 1px solid var(--rule); }
        .role-tab {
            padding: 10px 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.12s;
            border-right: 1px solid var(--rule);
            background: var(--white);
        }
        .role-tab:last-child { border-right: none; }
        .role-tab input[type="radio"] { display: none; }
        .role-tab-name {
            display: block;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 13px; font-weight: 600; letter-spacing: 0.5px;
            text-transform: uppercase; color: var(--ink-mid);
        }
        .role-tab-desc {
            display: block; font-size: 10px; color: var(--ink-light); margin-top: 2px;
        }
        .role-tab.active {
            background: var(--orange-light);
            border-bottom: 2px solid var(--orange);
        }
        .role-tab.active .role-tab-name { color: var(--orange); }

        .field { margin-bottom: 16px; }
        .field-label {
            display: block; font-size: 11px; font-weight: 500;
            text-transform: uppercase; letter-spacing: 0.8px;
            color: var(--ink-mid); margin-bottom: 6px;
        }
        .field input, .field select {
            width: 100%; border: 1px solid var(--rule);
            background: var(--white); padding: 10px 12px;
            font-size: 14px; font-family: 'Barlow', sans-serif;
            color: var(--ink); outline: none; border-radius: 2px;
            transition: border-color 0.15s;
        }
        .field input:focus, .field select:focus {
            border-color: var(--orange);
            box-shadow: 0 0 0 3px rgba(232,101,10,0.08);
        }
        .field-hint { font-size: 11px; color: var(--ink-light); margin-top: 4px; }

        .fields-section { margin-top: 4px; }
        .fields-section.hidden { display: none; }

        .form-divider {
            border: none; border-top: 1px solid var(--rule);
            margin: 20px 0;
        }

        .key-notice {
            background: var(--orange-light); border: 1px solid #F5C89C;
            padding: 12px 14px; margin-bottom: 20px;
        }
        .key-notice p { font-size: 12px; color: #7A3800; line-height: 1.6; }

        .btn-primary {
            width: 100%; background: var(--orange); color: var(--white);
            border: none; padding: 12px;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 14px; font-weight: 600; letter-spacing: 1.5px;
            text-transform: uppercase; cursor: pointer;
            transition: background 0.15s; border-radius: 2px;
        }
        .btn-primary:hover { background: var(--orange-dark); }

        .form-footer {
            text-align: center; margin-top: 20px;
            font-size: 13px; color: var(--ink-light);
        }
        .form-footer a { color: var(--orange); text-decoration: none; font-weight: 500; }
        .form-footer a:hover { text-decoration: underline; }

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
    <div class="logo-mark">KZ</div>
    <span class="brand">KAZ-SIGN</span>
    <span class="tagline">Post-Quantum Credential System</span>
</header>

<div class="page-content">
    <div class="form-wrap">
        <h1 class="page-title">Create Account</h1>
        <p class="page-subtitle">Register to access the KAZ-SIGN credential portal</p>

        <?php if (!empty($flash)): ?>
            <div class="flash <?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>">
                <?= strip_tags($flash['message'], '<a><strong><code>') ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <form action="<?= $base ?>/register" method="POST">
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>" />

                <!-- Role selector -->
                <div class="role-selector">
                    <label class="role-selector-label">Account Role</label>
                    <div class="role-tabs">
                        <label class="role-tab" id="tab-issuer" onclick="switchRole('issuer')">
                            <input type="radio" name="role" value="issuer" onchange="switchRole('issuer')" />
                            <span class="role-tab-name">Issuer</span>
                            <span class="role-tab-desc">Sign credentials</span>
                        </label>
                        <label class="role-tab active" id="tab-holder" onclick="switchRole('holder')">
                            <input type="radio" name="role" value="holder" checked onchange="switchRole('holder')" />
                            <span class="role-tab-name">Holder</span>
                            <span class="role-tab-desc">Receive credentials</span>
                        </label>
                        <label class="role-tab" id="tab-verifier" onclick="switchRole('verifier')">
                            <input type="radio" name="role" value="verifier" onchange="switchRole('verifier')" />
                            <span class="role-tab-name">Verifier</span>
                            <span class="role-tab-desc">Verify credentials</span>
                        </label>
                    </div>
                </div>

                <!-- Base fields -->
                <div class="field">
                    <label class="field-label">Username</label>
                    <input name="username" type="text" required autocomplete="username" />
                </div>
                <div class="field">
                    <label class="field-label">Email Address</label>
                    <input name="email" type="email" required autocomplete="email" />
                </div>
                <div class="field">
                    <label class="field-label">Password</label>
                    <input name="password" type="password" required minlength="8" autocomplete="new-password" />
                    <p class="field-hint">Minimum 8 characters</p>
                </div>

                <hr class="form-divider" />

                <!-- Holder fields -->
                <div id="fields-holder" class="fields-section">
                    <div class="field">
                        <label class="field-label">Full Name</label>
                        <input name="full_name" type="text" placeholder="e.g. Ahmad bin Ali" />
                    </div>
                    <div class="field">
                        <label class="field-label">ID Number</label>
                        <input name="id_number" type="text" placeholder="e.g. 991234-01-5678" />
                    </div>
                </div>

                <!-- Issuer / Verifier fields -->
                <div id="fields-org" class="fields-section hidden">
                    <div class="field">
                        <label class="field-label">Organisation Name</label>
                        <input name="organisation" type="text" placeholder="e.g. Universiti Teknologi Malaysia" />
                    </div>
                </div>

                <div class="key-notice">
                    <p><strong>Key pair generated automatically on registration.</strong><br/>
                    Your private key will be shown once on the next screen — save it securely.
                    It is never stored on the server.</p>
                </div>

                <button type="submit" class="btn-primary">Create Account &amp; Generate Keys</button>
            </form>
        </div>

        <p class="form-footer">
            Already have an account? <a href="<?= $base ?>/login">Sign in here</a>
        </p>
    </div>
</div>

<footer class="site-footer">
    <span>KAZ-SIGN System &mdash; Post-Quantum Digital Credentials</span>
    <span>KAZ-SIGN-128 Algorithm</span>
</footer>

<script>
function switchRole(role) {
    ['issuer','holder','verifier'].forEach(r => {
        document.getElementById('tab-' + r).classList.remove('active');
    });
    document.getElementById('tab-' + role).classList.add('active');

    document.getElementById('fields-holder').classList.add('hidden');
    document.getElementById('fields-org').classList.add('hidden');

    if (role === 'holder') {
        document.getElementById('fields-holder').classList.remove('hidden');
    } else {
        document.getElementById('fields-org').classList.remove('hidden');
    }
}
</script>
</body>
</html>