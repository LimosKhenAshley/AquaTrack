<?php
require_once '../../app/middleware/auth.php';
checkRole(['customer']);
require_once '../../app/config/database.php';
require_once '../../app/layouts/main.php';
require_once '../../app/layouts/sidebar.php';

$user_id = $_SESSION['user']['id'];

/* =========================
   LOAD USER
========================= */
$stmt = $pdo->prepare("SELECT full_name,email,phone FROM users WHERE id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if(!$user) die("User not found");

$profile_msg  = "";
$password_msg = "";

/* =========================
   UPDATE PROFILE
========================= */
if(isset($_POST['update_profile'])){
    $name  = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    // Auto-normalize: 09XXXXXXXXX → +639XXXXXXXXX
    if(preg_match('/^09\d{9}$/', $phone)){
        $phone = '+63' . substr($phone, 1);
    }

    if(!preg_match('/^\+63\d{10}$/', $phone)){
        $profile_msg = "<div class='aq-alert error'>⚠ Phone must be in format 09XXXXXXXXX or +639XXXXXXXXX</div>";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $profile_msg = "<div class='aq-alert error'>⚠ Invalid email address</div>";
    } else {
        $upd = $pdo->prepare("UPDATE users SET full_name=?, email=?, phone=? WHERE id=?");
        $upd->execute([$name, $email, $phone, $user_id]);
        if(function_exists('auditLog')) auditLog($pdo, $user_id, "Updated profile");
        $profile_msg = "<div class='aq-alert success'>✓ Profile updated successfully</div>";
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    }
}

/* =========================
   CHANGE PASSWORD
========================= */
if(isset($_POST['change_password'])){
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if(strlen($new) < 6){
        $password_msg = "<div class='aq-alert error'>⚠ New password must be at least 6 characters</div>";
    } elseif($new !== $confirm){
        $password_msg = "<div class='aq-alert error'>⚠ Passwords do not match</div>";
    } else {
        $hashStmt = $pdo->prepare("SELECT password FROM users WHERE id=?");
        $hashStmt->execute([$user_id]);
        $hash = $hashStmt->fetchColumn();
        if(!password_verify($current, $hash)){
            $password_msg = "<div class='aq-alert error'>⚠ Current password is incorrect</div>";
        } else {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
            $upd->execute([$newHash, $user_id]);
            $password_msg = "<div class='aq-alert success'>✓ Password changed successfully</div>";
            if(function_exists('auditLog')) auditLog($pdo, 'CHANGE_PASSWORD', "User ID: $user_id");
        }
    }
}

/* Avatar initials */
$initials = strtoupper(implode('', array_map(fn($w) => $w[0], explode(' ', trim($user['full_name'])))));
$initials  = substr($initials, 0, 2);
?>

<style>
  @import url('https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap');

  :root {
    --aq-surface:    #ffffff;
    --aq-navy:       #1e3a5f;
    --aq-blue:       #2563eb;
    --aq-blue-mid:   #3b82f6;
    --aq-blue-lt:    #dbeafe;
    --aq-blue-soft:  #eff6ff;
    --aq-border:     #bfdbfe;
    --aq-text:       #1e3a5f;
    --aq-muted:      #64748b;
    --aq-danger:     #dc2626;
    --aq-danger-lt:  #fef2f2;
    --aq-success:    #16a34a;
    --aq-success-lt: #f0fdf4;

    --s-weak:    #ef4444;
    --s-fair:    #f97316;
    --s-good:    #eab308;
    --s-strong:  #22c55e;
    --s-vstrong: #2563eb;

    --radius-card: 18px;
    --radius-inp:  10px;
    --shadow-card: 0 4px 24px rgba(37,99,235,.10);
  }

  /* ---- Page wrap ---- */
  .aq-profile-wrap {
    font-family: 'Nunito', sans-serif;
    width: 100%;
    padding: 28px 24px 48px;
    box-sizing: border-box;
    color: var(--aq-text);
  }

  /* ---- Page title row ---- */
  .aq-page-title {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 24px;
  }

  .aq-avatar {
    width: 46px; height: 46px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--aq-blue) 0%, var(--aq-navy) 100%);
    color: #fff;
    font-size: 16px; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 3px 10px rgba(37,99,235,.28);
  }

  .aq-page-title h3 {
    margin: 0 0 2px;
    font-size: 20px; font-weight: 800;
    color: var(--aq-navy);
  }
  .aq-page-title p {
    margin: 0;
    font-size: 13px;
    color: var(--aq-muted);
  }

  /* ---- Side-by-side grid ---- */
  .aq-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    align-items: start;
  }

  @media(max-width: 860px){
    .aq-grid { grid-template-columns: 1fr; }
  }

  /* ---- Cards ---- */
  .aq-card {
    background: var(--aq-surface);
    border: 1.5px solid var(--aq-border);
    border-radius: var(--radius-card);
    box-shadow: var(--shadow-card);
    overflow: hidden;
    height: 100%;
    box-sizing: border-box;
  }

  .aq-card-head {
    padding: 16px 22px 14px;
    border-bottom: 1.5px solid var(--aq-blue-lt);
    display: flex; align-items: center; gap: 10px;
    background: var(--aq-blue-soft);
  }

  .aq-card-icon {
    width: 34px; height: 34px;
    border-radius: 9px;
    background: var(--aq-blue-lt);
    color: var(--aq-blue);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }
  .aq-card-icon svg {
    width: 17px; height: 17px;
    stroke: currentColor; fill: none;
    stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
  }

  .aq-card-head h5 { margin: 0; font-size: 14px; font-weight: 700; color: var(--aq-navy); }
  .aq-card-head p  { margin: 2px 0 0; font-size: 11.5px; color: var(--aq-muted); }

  .aq-card-body { padding: 20px 22px 24px; }

  /* ---- Fields ---- */
  .aq-field { margin-bottom: 15px; }
  .aq-field:last-of-type { margin-bottom: 0; }

  .aq-field label {
    display: block; font-size: 11.5px; font-weight: 700;
    letter-spacing: .5px; text-transform: uppercase;
    color: var(--aq-muted); margin-bottom: 5px;
  }

  .aq-input-wrap { position: relative; }

  .aq-field input {
    width: 100%;
    padding: 10px 40px 10px 13px;
    border: 1.5px solid var(--aq-border);
    border-radius: var(--radius-inp);
    font-family: 'Nunito', sans-serif;
    font-size: 14px; font-weight: 500;
    color: var(--aq-text);
    background: var(--aq-blue-soft);
    transition: border-color .18s, box-shadow .18s, background .18s;
    box-sizing: border-box; outline: none;
  }

  .aq-field input:focus {
    border-color: var(--aq-blue-mid);
    box-shadow: 0 0 0 3px rgba(59,130,246,.15);
    background: #fff;
  }

  .aq-hint {
    font-size: 11px; color: var(--aq-muted);
    margin-top: 4px; font-weight: 500;
  }

  .aq-divider { border: none; border-top: 1.5px solid var(--aq-blue-lt); margin: 18px 0; }

  /* ---- Toggle pw ---- */
  .aq-toggle-pw {
    position: absolute; right: 11px; top: 50%; transform: translateY(-50%);
    background: none; border: none; cursor: pointer; padding: 0;
    color: var(--aq-muted); display: flex; align-items: center;
    transition: color .15s;
  }
  .aq-toggle-pw:hover { color: var(--aq-blue); }
  .aq-toggle-pw svg {
    width: 16px; height: 16px; stroke: currentColor; fill: none;
    stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
  }

  /* ---- Strength ---- */
  .aq-strength-wrap { margin-top: 8px; }
  .aq-strength-bars { display: flex; gap: 4px; margin-bottom: 5px; }
  .aq-strength-bar {
    flex: 1; height: 5px; border-radius: 99px;
    background: var(--aq-blue-lt); transition: background .3s;
  }
  .aq-strength-label { font-size: 11.5px; font-weight: 700; color: var(--aq-muted); transition: color .3s; }

  .str-1 .aq-strength-bar:nth-child(1)      { background: var(--s-weak); }
  .str-2 .aq-strength-bar:nth-child(-n+2)   { background: var(--s-fair); }
  .str-3 .aq-strength-bar:nth-child(-n+3)   { background: var(--s-good); }
  .str-4 .aq-strength-bar:nth-child(-n+4)   { background: var(--s-strong); }
  .str-5 .aq-strength-bar                   { background: var(--s-vstrong); }

  .str-1 .aq-strength-label { color: var(--s-weak); }
  .str-2 .aq-strength-label { color: var(--s-fair); }
  .str-3 .aq-strength-label { color: var(--s-good); }
  .str-4 .aq-strength-label { color: var(--s-strong); }
  .str-5 .aq-strength-label { color: var(--s-vstrong); }

  /* ---- Checklist ---- */
  .aq-pw-checks {
    list-style: none; padding: 0; margin: 8px 0 0;
    display: grid; grid-template-columns: 1fr 1fr; gap: 4px 10px;
  }
  .aq-pw-checks li {
    font-size: 11.5px; font-weight: 600; color: var(--aq-muted);
    display: flex; align-items: center; gap: 5px; transition: color .2s;
  }
  .aq-pw-checks li svg { width: 13px; height: 13px; flex-shrink: 0; }
  .aq-pw-checks li.pass { color: var(--aq-success); }

  /* ---- Match ---- */
  .aq-match {
    font-size: 11.5px; font-weight: 700;
    margin-top: 5px; min-height: 16px; transition: color .2s;
  }
  .aq-match.ok   { color: var(--aq-success); }
  .aq-match.fail { color: var(--aq-danger); }

  /* ---- Phone badge ---- */
  .aq-phone-badge {
    display: none;
    font-size: 11.5px; font-weight: 700;
    color: var(--aq-blue);
    margin-top: 5px;
    background: var(--aq-blue-lt);
    border-radius: 6px;
    padding: 3px 9px;
    width: fit-content;
  }

  /* ---- Button ---- */
  .aq-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 10px 22px;
    background: linear-gradient(135deg, var(--aq-blue) 0%, var(--aq-navy) 100%);
    color: #fff; border: none; border-radius: 10px;
    font-family: 'Nunito', sans-serif; font-size: 13.5px; font-weight: 700;
    cursor: pointer;
    transition: opacity .18s, transform .12s, box-shadow .18s;
    box-shadow: 0 3px 10px rgba(37,99,235,.28);
  }
  .aq-btn:hover { opacity: .9; box-shadow: 0 5px 16px rgba(37,99,235,.38); transform: translateY(-1px); }
  .aq-btn:active { transform: translateY(0); }
  .aq-btn svg {
    width: 14px; height: 14px; stroke: currentColor; fill: none;
    stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0;
  }
  .aq-btn-row { margin-top: 20px; }

  /* ---- Alerts ---- */
  .aq-alert {
    padding: 10px 14px; border-radius: 9px;
    font-size: 13px; font-weight: 600; margin-bottom: 16px;
  }
  .aq-alert.success { background: var(--aq-success-lt); color: var(--aq-success); border: 1.5px solid #bbf7d0; }
  .aq-alert.error   { background: var(--aq-danger-lt);  color: var(--aq-danger);  border: 1.5px solid #fecaca; }
</style>

<div class="aq-profile-wrap">

  <!-- Slim title row -->
  <div class="aq-page-title">
    <div class="aq-avatar"><?= htmlspecialchars($initials) ?></div>
    <div>
      <h3><?= htmlspecialchars($user['full_name']) ?></h3>
    </div>
  </div>

  <!-- Side-by-side cards -->
  <div class="aq-grid">

    <!-- ===== LEFT: Profile Info ===== -->
    <div class="aq-card">
      <div class="aq-card-head">
        <div class="aq-card-icon">
          <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        <div>
          <h5>Personal Information</h5>
          <p>Update your name, email &amp; phone</p>
        </div>
      </div>
      <div class="aq-card-body">
        <?= $profile_msg ?>
        <form method="POST">

          <div class="aq-field">
            <label>Full Name</label>
            <div class="aq-input-wrap">
              <input type="text" name="full_name" required
                     value="<?= htmlspecialchars($user['full_name']) ?>">
            </div>
          </div>

          <div class="aq-field">
            <label>Email Address</label>
            <div class="aq-input-wrap">
              <input type="email" name="email" required
                     value="<?= htmlspecialchars($user['email']) ?>">
            </div>
          </div>

          <div class="aq-field">
            <label>Phone Number</label>
            <div class="aq-input-wrap">
              <input type="text" id="phone_input" name="phone"
                     placeholder="09XXXXXXXXX or +639XXXXXXXXX"
                     required
                     value="<?= htmlspecialchars((string)$user['phone']) ?>">
            </div>
            <div class="aq-phone-badge" id="phone-badge"></div>
            <p class="aq-hint">Enter 09XXXXXXXXX — we'll auto-format it for you</p>
          </div>

          <div class="aq-btn-row">
            <button class="aq-btn" name="update_profile" value="1">
              <svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
              Save Profile
            </button>
          </div>

        </form>
      </div>
    </div>

    <!-- ===== RIGHT: Change Password ===== -->
    <div class="aq-card">
      <div class="aq-card-head">
        <div class="aq-card-icon">
          <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <div>
          <h5>Change Password</h5>
          <p>Keep your account safe</p>
        </div>
      </div>
      <div class="aq-card-body">
        <?= $password_msg ?>
        <form method="POST">

          <div class="aq-field">
            <label>Current Password</label>
            <div class="aq-input-wrap">
              <input type="password" id="current_password" name="current_password" required>
              <button type="button" class="aq-toggle-pw" data-target="current_password">
                <svg class="eye-svg" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
          </div>

          <hr class="aq-divider">

          <div class="aq-field">
            <label>New Password</label>
            <div class="aq-input-wrap">
              <input type="password" id="new_password" name="new_password"
                     required minlength="6" autocomplete="new-password">
              <button type="button" class="aq-toggle-pw" data-target="new_password">
                <svg class="eye-svg" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>

            <!-- Strength bar -->
            <div class="aq-strength-wrap" id="strength-wrap" style="display:none">
              <div class="aq-strength-bars" id="strength-bars">
                <div class="aq-strength-bar"></div>
                <div class="aq-strength-bar"></div>
                <div class="aq-strength-bar"></div>
                <div class="aq-strength-bar"></div>
                <div class="aq-strength-bar"></div>
              </div>
              <span class="aq-strength-label" id="strength-label"></span>
            </div>

            <!-- Checklist -->
            <ul class="aq-pw-checks">
              <li id="chk-len">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                8+ characters
              </li>
              <li id="chk-upper">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Uppercase
              </li>
              <li id="chk-lower">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Lowercase
              </li>
              <li id="chk-num">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Number
              </li>
            </ul>
          </div>

          <div class="aq-field">
            <label>Confirm New Password</label>
            <div class="aq-input-wrap">
              <input type="password" id="confirm_password" name="confirm_password"
                     required autocomplete="new-password">
              <button type="button" class="aq-toggle-pw" data-target="confirm_password">
                <svg class="eye-svg" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
            <div class="aq-match" id="match-indicator"></div>
          </div>

          <div class="aq-btn-row">
            <button class="aq-btn" name="change_password" value="1">
              <svg viewBox="0 0 24 24"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
              Update Password
            </button>
          </div>

        </form>
      </div>
    </div>

  </div><!-- /.aq-grid -->
</div>

<script>
const ICON_OK   = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg>`;
const ICON_FAIL = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;

/* ---- Phone auto-normalize ---- */
const phoneInput = document.getElementById('phone_input');
const phoneBadge = document.getElementById('phone-badge');

phoneInput.addEventListener('input', () => {
  const raw = phoneInput.value.trim();
  if (/^09\d{9}$/.test(raw)) {
    phoneBadge.style.display = 'block';
    phoneBadge.textContent   = '→ Will be saved as +63' + raw.substring(1);
  } else {
    phoneBadge.style.display = 'none';
  }
});

/* ---- Toggle password visibility ---- */
document.querySelectorAll('.aq-toggle-pw').forEach(btn => {
  btn.addEventListener('click', () => {
    const input = document.getElementById(btn.dataset.target);
    const show  = input.type === 'password';
    input.type  = show ? 'text' : 'password';
    btn.querySelector('.eye-svg').innerHTML = show
      ? `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>`
      : `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
  });
});

/* ---- Password strength + checklist ---- */
const newPwInput    = document.getElementById('new_password');
const confPwInput   = document.getElementById('confirm_password');
const strengthWrap  = document.getElementById('strength-wrap');
const strengthBars  = document.getElementById('strength-bars');
const strengthLabel = document.getElementById('strength-label');
const matchEl       = document.getElementById('match-indicator');

const checks = {
  len:   { el: document.getElementById('chk-len'),   test: v => v.length >= 8 },
  upper: { el: document.getElementById('chk-upper'), test: v => /[A-Z]/.test(v) },
  lower: { el: document.getElementById('chk-lower'), test: v => /[a-z]/.test(v) },
  num:   { el: document.getElementById('chk-num'),   test: v => /[0-9]/.test(v) },
};

const levels = [
  { label: 'Too weak',    cls: 'str-1' },
  { label: 'Weak',        cls: 'str-2' },
  { label: 'Fair',        cls: 'str-3' },
  { label: 'Strong',      cls: 'str-4' },
  { label: 'Very strong', cls: 'str-5' },
];

function scorePassword(val) {
  if (!val) return 0;
  let score = 0;
  if (val.length >= 8)   score++;
  if (val.length >= 12)  score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[a-z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  return Math.min(5, Math.max(1, Math.round(score)));
}

newPwInput.addEventListener('input', () => {
  const val = newPwInput.value;

  Object.values(checks).forEach(({ el, test }) => {
    const pass  = test(val);
    const label = el.textContent.trim();
    el.classList.toggle('pass', pass);
    el.innerHTML = (pass ? ICON_OK : ICON_FAIL) + ' ' + label;
  });

  if (!val.length) { strengthWrap.style.display = 'none'; checkMatch(); return; }
  strengthWrap.style.display = 'block';
  const score = scorePassword(val);
  strengthBars.className = 'aq-strength-bars ' + levels[score - 1].cls;
  strengthLabel.textContent = levels[score - 1].label;

  checkMatch();
});

confPwInput.addEventListener('input', checkMatch);

function checkMatch() {
  const nv = newPwInput.value;
  const cv = confPwInput.value;
  if (!cv) { matchEl.textContent = ''; matchEl.className = 'aq-match'; return; }
  if (nv === cv) {
    matchEl.className   = 'aq-match ok';
    matchEl.textContent = '✓ Passwords match';
  } else {
    matchEl.className   = 'aq-match fail';
    matchEl.textContent = '✗ Passwords do not match';
  }
}
</script>

<?php require_once '../../app/layouts/footer.php'; ?>