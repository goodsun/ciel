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
        <div style="font-size:1.1rem;font-weight:600;"><?= htmlspecialchars($user['name']) ?></div>
        <div style="font-size:0.85rem;color:#888;"><?= htmlspecialchars($user['email']) ?></div>
      </div>
    </div>

    <div style="display:flex;gap:24px;margin-bottom:24px;">
      <div style="flex:1;background:#0d1b2a;border:1px solid #2a2a4a;border-radius:8px;padding:16px;text-align:center;">
        <div style="font-size:0.8rem;color:#888;margin-bottom:4px;"><?= t('balance') ?></div>
        <div style="font-size:1.5rem;font-weight:600;color:#6bff9e;">$<?= number_format((float)$user['balance'], 4) ?></div>
      </div>
      <div style="flex:1;background:#0d1b2a;border:1px solid #2a2a4a;border-radius:8px;padding:16px;text-align:center;">
        <div style="font-size:0.8rem;color:#888;margin-bottom:4px;"><?= t('member_since') ?></div>
        <div style="font-size:1rem;color:#ccc;"><?= date('Y-m-d', strtotime($user['created_at'])) ?></div>
      </div>
    </div>

    <h3 style="color:var(--accent,#8bb4ff);margin-bottom:12px;font-size:0.95rem;"><?= t('purchase_credits') ?></h3>
    <form action="/purchase.php" method="POST" id="purchaseForm" style="margin-bottom:24px;">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
      <div style="display:flex;gap:8px;margin-bottom:10px;">
        <select name="amount" style="flex:1;padding:10px;background:#0d1b2a;border:1px solid #2a2a4a;border-radius:6px;color:#e0e0e0;font-size:0.9rem;">
          <option value="5">$5.00</option>
          <option value="10" selected>$10.00</option>
          <option value="25">$25.00</option>
          <option value="50">$50.00</option>
          <option value="100">$100.00</option>
        </select>
        <button type="submit" id="purchaseBtn" style="padding:10px 24px;background:linear-gradient(135deg,#4a6fa5,#8bb4ff);border:none;border-radius:6px;color:#fff;font-weight:600;cursor:pointer;"><?= t('purchase') ?></button>
      </div>
      <label style="display:flex;align-items:center;gap:6px;font-size:0.82rem;color:#999;cursor:pointer;justify-content:flex-end;">
        <input type="checkbox" name="agree_tos" value="1" id="agreeToS" style="accent-color:#8bb4ff;">
        <span><?= t('agree_tos') ?></span>
      </label>
    </form>
    <div id="tosModal" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,0.7);align-items:center;justify-content:center;">
      <div style="background:#1a1a2e;border:1px solid #2a2a4a;border-radius:10px;padding:28px 24px;max-width:400px;width:90%;text-align:center;">
        <p style="color:#e0e0e0;font-size:0.95rem;margin-bottom:16px;line-height:1.7;"><?= t('agree_tos_modal') ?></p>
        <button id="tosModalClose" style="padding:8px 28px;background:linear-gradient(135deg,#4a6fa5,#8bb4ff);border:none;border-radius:6px;color:#fff;font-weight:600;cursor:pointer;font-size:0.9rem;">OK</button>
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

    <h3 style="color:var(--accent,#8bb4ff);margin-bottom:12px;font-size:0.95rem;"><?= t('recent_transactions') ?></h3>
<?php
$db = getDb();
$stmt = $db->prepare('SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 20');
$stmt->execute([$user['id']]);
$txns = $stmt->fetchAll();
?>
<?php if (empty($txns)): ?>
    <p style="color:#666;font-size:0.85rem;"><?= t('no_transactions') ?></p>
<?php else: ?>
    <table style="width:100%;font-size:0.85rem;border-collapse:collapse;">
      <tr style="color:#888;text-align:left;">
        <th style="padding:8px 4px;border-bottom:1px solid #2a2a4a;"><?= t('th_date') ?></th>
        <th style="padding:8px 4px;border-bottom:1px solid #2a2a4a;"><?= t('th_type') ?></th>
        <th style="padding:8px 4px;border-bottom:1px solid #2a2a4a;text-align:right;"><?= t('th_amount') ?></th>
        <th style="padding:8px 4px;border-bottom:1px solid #2a2a4a;text-align:right;"><?= t('th_balance') ?></th>
        <th style="padding:8px 4px;border-bottom:1px solid #2a2a4a;"><?= t('th_note') ?></th>
      </tr>
<?php foreach ($txns as $tx): ?>
      <tr>
        <td style="padding:6px 4px;border-bottom:1px solid #1a1a2e;color:#aaa;"><?= date('m/d H:i', strtotime($tx['created_at'])) ?></td>
        <td style="padding:6px 4px;border-bottom:1px solid #1a1a2e;color:#ccc;"><?= htmlspecialchars($tx['type']) ?></td>
        <td style="padding:6px 4px;border-bottom:1px solid #1a1a2e;text-align:right;color:<?= $tx['amount'] >= 0 ? '#6bff9e' : '#ff6b6b' ?>;">
          <?= $tx['amount'] >= 0 ? '+' : '' ?>$<?= number_format((float)$tx['amount'], 4) ?>
        </td>
        <td style="padding:6px 4px;border-bottom:1px solid #1a1a2e;text-align:right;color:#ccc;">$<?= number_format((float)$tx['balance'], 4) ?></td>
        <td style="padding:6px 4px;border-bottom:1px solid #1a1a2e;color:#888;"><?= htmlspecialchars($tx['note'] ?? '') ?></td>
      </tr>
<?php endforeach; ?>
    </table>
<?php endif; ?>
  </div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
