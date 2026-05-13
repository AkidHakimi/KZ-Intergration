<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard — KAZ-SIGN System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet" />
    <style> body { font-family: 'JetBrains Mono', monospace; } </style>
</head>
<body class="min-h-full bg-slate-950 text-slate-100">

<?php include SRC_PATH . '/Views/partials/nav.php'; ?>

<main class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-10 space-y-8">

    <!-- Flash message -->
    <?php if (!empty($flash)): ?>
        <div class="rounded-lg border px-4 py-3 text-sm
            <?= $flash['type'] === 'success'
                ? 'border-emerald-700 bg-emerald-900/30 text-emerald-300'
                : 'border-red-800 bg-red-950/50 text-red-300' ?>">
            <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <!-- Verification result panel -->
    <?php if (!empty($verify_result)): ?>
        <div class="rounded-xl border border-slate-800 bg-slate-900 p-6 space-y-3">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                Verification Result — Document #<?= (int)$verify_result['document']['id'] ?>
            </h2>

            <!-- Hash check -->
            <div class="flex items-center justify-between rounded-lg bg-slate-800 px-4 py-3">
                <span class="text-xs text-slate-400">File integrity (SHA-256)</span>
                <?php if ($verify_result['hash_intact']): ?>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-700
                                 bg-emerald-900/30 px-3 py-0.5 text-[10px] font-bold text-emerald-400">Intact</span>
                <?php else: ?>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-red-800
                                 bg-red-950/50 px-3 py-0.5 text-[10px] font-bold text-red-400">MODIFIED</span>
                <?php endif; ?>
            </div>

            <!-- Signature check -->
            <div class="flex items-center justify-between rounded-lg bg-slate-800 px-4 py-3">
                <span class="text-xs text-slate-400">KAZ-SIGN signature</span>
                <?php if ($verify_result['signature_valid']): ?>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-700
                                 bg-emerald-900/30 px-3 py-0.5 text-[10px] font-bold text-emerald-400">Valid</span>
                <?php else: ?>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-red-800
                                 bg-red-950/50 px-3 py-0.5 text-[10px] font-bold text-red-400">Invalid</span>
                <?php endif; ?>
            </div>

            <!-- Overall -->
            <div class="flex items-center justify-between rounded-lg px-4 py-3
                <?= $verify_result['verified']
                    ? 'border border-emerald-800 bg-emerald-900/20'
                    : 'border border-red-900 bg-red-950/30' ?>">
                <span class="text-xs font-semibold
                    <?= $verify_result['verified'] ? 'text-emerald-300' : 'text-red-300' ?>">
                    Overall result
                </span>
                <span class="text-xs font-bold
                    <?= $verify_result['verified'] ? 'text-emerald-400' : 'text-red-400' ?>">
                    <?= $verify_result['verified'] ? 'Authentic &amp; untampered' : 'VERIFICATION FAILED' ?>
                </span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Upload card -->
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-6 space-y-4">
        <div>
            <h2 class="text-sm font-semibold text-slate-100">Sign a document</h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Upload a PDF, DOC, DOCX, or TXT file. It will be hashed and signed with your KAZ-SIGN private key.
            </p>
        </div>

        <?php if (empty($_SESSION['private_key'])): ?>
            <div class="rounded-lg border border-yellow-700 bg-yellow-900/20 px-4 py-3 text-xs text-yellow-300 space-y-1">
                <p class="font-semibold">No private key loaded — signing is disabled.</p>
                <p class="text-yellow-600">
                    <a href="<?= $base ?>/logout" class="underline hover:text-yellow-400">Log out</a>
                    and sign in again with your private key to enable document signing.
                </p>
            </div>
        <?php else: ?>
            <form action="<?= $base ?>/documents/upload" method="POST"
                  enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>" />

                <div class="space-y-1.5">
                    <label for="document"
                           class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">
                        Choose file
                        <span class="ml-1 text-[10px] font-normal text-slate-600 normal-case">
                            — PDF, DOC, DOCX, TXT · max 10 MB
                        </span>
                    </label>
                    <input type="file" id="document" name="document"
                           accept=".pdf,.doc,.docx,.txt" required
                           class="w-full rounded-lg border border-slate-700 bg-slate-800 px-4 py-2.5
                                  text-xs text-slate-300 focus:outline-none focus:border-emerald-500
                                  file:mr-3 file:rounded file:border-0 file:bg-slate-700
                                  file:px-3 file:py-1 file:text-[10px] file:font-semibold
                                  file:text-slate-200 file:cursor-pointer hover:file:bg-slate-600" />
                </div>

                <button type="submit"
                        class="rounded-lg bg-emerald-500 px-5 py-2 text-xs font-bold uppercase tracking-widest
                               text-slate-950 hover:bg-emerald-400 active:scale-95 transition-all duration-150">
                    Upload &amp; Sign
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Documents table -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-100">My signed documents</h2>
            <span class="rounded-full border border-slate-700 bg-slate-800
                         px-2.5 py-0.5 text-[10px] text-slate-500">
                <?= count($documents) ?> document<?= count($documents) !== 1 ? 's' : '' ?>
            </span>
        </div>

        <?php if (empty($documents)): ?>
            <div class="rounded-xl border border-slate-800 bg-slate-900 px-6 py-16 text-center space-y-1">
                <p class="text-sm text-slate-500">No documents yet.</p>
                <?php if (!empty($_SESSION['private_key'])): ?>
                    <p class="text-xs text-slate-600">Upload your first document above to get started.</p>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <div class="rounded-xl border border-slate-800 overflow-hidden">
                <table class="w-full text-xs">
                    <thead class="border-b border-slate-700 bg-slate-800">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-400 uppercase tracking-wider">#</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-400 uppercase tracking-wider">File</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-400 uppercase tracking-wider">SHA-256</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-400 uppercase tracking-wider">Signed at</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-400 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 bg-slate-900">
                    <?php foreach ($documents as $doc): ?>
                        <tr class="hover:bg-slate-800/50 transition-colors">

                            <td class="px-4 py-3 text-slate-500">
                                <?= (int)$doc['id'] ?>
                            </td>

                            <td class="px-4 py-3 text-slate-300 max-w-[220px] truncate"
                                title="<?= htmlspecialchars($doc['file_name'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($doc['file_name'], ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td class="px-4 py-3 font-mono text-slate-500"
                                title="<?= htmlspecialchars($doc['file_hash'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars(substr($doc['file_hash'], 0, 12), ENT_QUOTES, 'UTF-8') ?>…
                            </td>

                            <td class="px-4 py-3">
                                <?php $s = $doc['status']; ?>
                                <span class="inline-flex rounded-full border px-2.5 py-0.5 text-[10px] font-semibold
                                    <?= match($s) {
                                        'signed'   => 'border-blue-800 bg-blue-900/30 text-blue-400',
                                        'verified' => 'border-emerald-700 bg-emerald-900/30 text-emerald-400',
                                        'rejected' => 'border-red-800 bg-red-950/40 text-red-400',
                                        default    => 'border-slate-700 bg-slate-800 text-slate-400',
                                    } ?>">
                                    <?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>

                            <td class="px-4 py-3 text-slate-500">
                                <?= htmlspecialchars($doc['created_at'], ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td class="px-4 py-3">
                                <a href="<?= $base ?>/documents/<?= (int)$doc['id'] ?>/verify"
                                   class="text-emerald-400 hover:text-emerald-300 hover:underline transition-colors">
                                    Verify
                                </a>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</main>
</body>
</html>
