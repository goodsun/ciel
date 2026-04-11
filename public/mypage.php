<?php
require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/auth.php';
require __DIR__ . '/../src/user.php';
requireLogin();

$pageTitle = t('title_mypage');
$pageHeading = t('title_mypage');
require __DIR__ . '/../templates/head.php';
require __DIR__ . '/../templates/header.php';

$user = getUserById($_SESSION['user']['id']);
?>

  <div class="panel" style="display:block;">
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;">
<?php if (!empty($_SESSION['user']['picture'])): ?>
      <img src="<?= htmlspecialchars($_SESSION['user']['picture']) ?>" style="width:48px;height:48px;border-radius:50%;">
<?php endif; ?>
      <div>
        <div style="font-size:1.1rem;color:var(--text);"><?= htmlspecialchars($user['name']) ?></div>
        <div style="font-size:0.82rem;color:var(--text-dim);"><?= htmlspecialchars($user['email']) ?></div>
      </div>
    </div>

    <div style="display:flex;gap:20px;margin-bottom:28px;">
      <div style="flex:1;background:var(--bg-input);border:1px solid var(--border);border-radius:4px;padding:18px;text-align:center;">
        <div style="font-family:var(--serif);font-size:0.82rem;color:var(--text-dim);margin-bottom:6px;letter-spacing:0.05em;"><?= t('balance') ?></div>
        <div style="font-family:var(--serif);font-size:1.6rem;color:#70d090;letter-spacing:0.02em;">$<?= number_format((float)$user['balance'], 4) ?></div>
      </div>
      <div style="flex:1;background:var(--bg-input);border:1px solid var(--border);border-radius:4px;padding:18px;text-align:center;">
        <div style="font-family:var(--serif);font-size:0.82rem;color:var(--text-dim);margin-bottom:6px;letter-spacing:0.05em;"><?= t('member_since') ?></div>
        <div style="font-family:var(--serif);font-size:1rem;color:var(--text);letter-spacing:0.02em;"><?= date('Y-m-d', strtotime($user['created_at'])) ?></div>
      </div>
    </div>

    <h3 style="font-family:var(--serif);color:var(--accent);margin-bottom:14px;font-size:1rem;font-weight:400;letter-spacing:0.05em;"><?= t('purchase_credits') ?></h3>
    <form action="/purchase.php" method="POST" id="purchaseForm" style="margin-bottom:28px;">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
      <div style="display:flex;gap:8px;margin-bottom:10px;">
        <select name="amount" style="flex:1;padding:10px 12px;background:var(--bg-input);border:1px solid var(--border);border-radius:4px;color:var(--text);font-size:0.88rem;font-family:var(--sans);">
          <option value="5">$5.00</option>
          <option value="10" selected>$10.00</option>
          <option value="25">$25.00</option>
          <option value="50">$50.00</option>
          <option value="100">$100.00</option>
        </select>
        <button type="submit" id="purchaseBtn" style="padding:10px 28px;background:transparent;border:1px solid var(--border-hover);color:#fff;font-family:var(--serif);font-size:0.9rem;letter-spacing:0.08em;cursor:pointer;transition:all 0.3s;border-radius:0;"><?= t('purchase') ?></button>
      </div>
      <label style="display:flex;align-items:center;gap:6px;font-size:0.8rem;color:var(--text-dim);cursor:pointer;justify-content:flex-end;">
        <input type="checkbox" name="agree_tos" value="1" id="agreeToS" style="accent-color:var(--accent);">
        <span><?= t('agree_tos') ?></span>
      </label>
    </form>
    <div id="tosModal" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(6,6,12,0.8);align-items:center;justify-content:center;">
      <div style="background:var(--bg-panel);border:1px solid var(--border-hover);border-radius:4px;padding:32px 28px;max-width:400px;width:90%;text-align:center;">
        <p style="color:var(--text);font-size:0.92rem;margin-bottom:18px;line-height:1.8;"><?= t('agree_tos_modal') ?></p>
        <button id="tosModalClose" style="padding:10px 36px;background:transparent;border:1px solid var(--border-hover);color:#fff;font-family:var(--serif);letter-spacing:0.08em;cursor:pointer;font-size:0.9rem;border-radius:0;transition:all 0.3s;">OK</button>
      </div>
    </div>
    <script>
    (function(){
      var form = document.getElementById('purchaseForm');
      var cb = document.getElementById('agreeToS');
      var modal = document.getElementById('tosModal');
      var closeBtn = document.getElementById('tosModalClose');
      form.addEventListener('submit', function(e){
        if (!cb.checked) {
          e.preventDefault();
          modal.style.display = 'flex';
        }
      });
      closeBtn.addEventListener('click', function(){ modal.style.display = 'none'; });
      modal.addEventListener('click', function(e){ if (e.target === modal) modal.style.display = 'none'; });
    })();
    </script>

    <h3 style="font-family:var(--serif);color:var(--accent);margin-bottom:14px;font-size:1rem;font-weight:400;letter-spacing:0.05em;"><?= t('recent_transactions') ?></h3>
<?php
$db = getDb();
$stmt = $db->prepare('SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 20');
$stmt->execute([$user['id']]);
$txns = $stmt->fetchAll();
?>
<?php if (empty($txns)): ?>
    <p style="color:#666;font-size:0.85rem;"><?= t('no_transactions') ?></p>
<?php else: ?>
    <table style="width:100%;font-size:0.82rem;border-collapse:collapse;">
      <tr style="color:var(--text-dim);text-align:left;">
        <th style="padding:8px 4px;border-bottom:1px solid var(--border);font-family:var(--serif);font-weight:400;letter-spacing:0.04em;"><?= t('th_date') ?></th>
        <th style="padding:8px 4px;border-bottom:1px solid var(--border);font-family:var(--serif);font-weight:400;letter-spacing:0.04em;"><?= t('th_type') ?></th>
        <th style="padding:8px 4px;border-bottom:1px solid var(--border);font-family:var(--serif);font-weight:400;letter-spacing:0.04em;text-align:right;"><?= t('th_amount') ?></th>
        <th style="padding:8px 4px;border-bottom:1px solid var(--border);font-family:var(--serif);font-weight:400;letter-spacing:0.04em;text-align:right;"><?= t('th_balance') ?></th>
        <th style="padding:8px 4px;border-bottom:1px solid var(--border);font-family:var(--serif);font-weight:400;letter-spacing:0.04em;"><?= t('th_note') ?></th>
      </tr>
<?php foreach ($txns as $tx): ?>
      <tr>
        <td style="padding:7px 4px;border-bottom:1px solid var(--border);color:var(--text-dim);"><?= date('m/d H:i', strtotime($tx['created_at'])) ?></td>
        <td style="padding:7px 4px;border-bottom:1px solid var(--border);color:var(--text);"><?= htmlspecialchars($tx['type']) ?></td>
        <td style="padding:7px 4px;border-bottom:1px solid var(--border);text-align:right;color:<?= $tx['amount'] >= 0 ? '#70d090' : '#e07070' ?>;">
          <?= $tx['amount'] >= 0 ? '+' : '' ?>$<?= number_format((float)$tx['amount'], 4) ?>
        </td>
        <td style="padding:7px 4px;border-bottom:1px solid var(--border);text-align:right;color:var(--text);">$<?= number_format((float)$tx['balance'], 4) ?></td>
        <td style="padding:7px 4px;border-bottom:1px solid var(--border);color:var(--text-dim);"><?= htmlspecialchars($tx['note'] ?? '') ?></td>
      </tr>
<?php endforeach; ?>
    </table>
<?php endif; ?>
  </div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
