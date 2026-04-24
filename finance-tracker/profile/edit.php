<?php
/**
 * File: profile/edit.php
 * Purpose: Edit profile — name, currency, and avatar upload
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/functions.php';
require_once __DIR__ . '/../models/User.php';

// ── Ensure avatar column exists (safe migration) ──────────
try {
    $cols = $pdo->query("SHOW COLUMNS FROM `users`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('avatar', $cols)) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `avatar` VARCHAR(255) NULL DEFAULT NULL AFTER `currency`");
    }
    if (!in_array('username', $cols)) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `username` VARCHAR(60) NULL UNIQUE AFTER `name`");
        $pdo->exec("UPDATE `users` SET `username` = LOWER(SUBSTRING_INDEX(`email`,'@',1)) WHERE `username` IS NULL");
        $pdo->exec("ALTER TABLE `users` MODIFY COLUMN `username` VARCHAR(60) NOT NULL");
    }
} catch (PDOException $e) {
    // Columns already exist — ignore
}

$userId    = (int)$_SESSION['user_id'];
$userModel = new User($pdo);
$user      = $userModel->findById($userId);
$errors    = [];
$pwErrors  = [];
$success   = false;
$pwSuccess = false;

// ── Handle POST ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Invalid request. Please try again.';
    } else {

        // ════════════════════════════════
        // Profile update
        // ════════════════════════════════
        if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {

            $name     = trim($_POST['name'] ?? '');
            $currency = $_POST['currency'] ?? 'ETB';

            if (empty($name))                     $errors['name']     = 'Full name is required.';
            if (!in_array($currency, CURRENCIES)) $errors['currency'] = 'Invalid currency.';

            // ── Avatar upload ─────────────────────────────
            $avatarPath    = $user['avatar'] ?? null;
            $avatarChanged = false;

            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $file    = $_FILES['avatar'];
                $maxSize = 2 * 1024 * 1024;

                if (function_exists('finfo_open')) {
                    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);
                } else {
                    $mimeType = $file['type'];
                }

                $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

                if (!in_array($mimeType, $allowed)) {
                    $errors['avatar'] = 'Only JPG, PNG, GIF, or WEBP images are allowed.';
                } elseif ($file['size'] > $maxSize) {
                    $errors['avatar'] = 'Image must be under 2 MB.';
                } else {
                    $extMap   = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
                    $ext      = $extMap[$mimeType] ?? 'jpg';
                    $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
                    $destDir  = __DIR__ . '/../assets/images/avatars/';
                    $destPath = $destDir . $filename;

                    if (!is_dir($destDir)) mkdir($destDir, 0755, true);

                    if (move_uploaded_file($file['tmp_name'], $destPath)) {
                        if (!empty($avatarPath) && file_exists(__DIR__ . '/../' . $avatarPath)) {
                            @unlink(__DIR__ . '/../' . $avatarPath);
                        }
                        $avatarPath    = 'assets/images/avatars/' . $filename;
                        $avatarChanged = true;
                    } else {
                        $errors['avatar'] = 'Failed to save the uploaded file. Check folder permissions.';
                    }
                }
            } elseif (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadErrors = [
                    UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
                    UPLOAD_ERR_FORM_SIZE  => 'File exceeds form upload limit.',
                    UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
                    UPLOAD_ERR_CANT_WRITE => 'Cannot write file to disk.',
                ];
                $errors['avatar'] = $uploadErrors[$_FILES['avatar']['error']] ?? 'Upload error. Please try again.';
            }

            if (empty($errors)) {
                $userModel->update($userId, $name, $currency);
                if ($avatarChanged) {
                    $userModel->updateAvatar($userId, $avatarPath);
                    $_SESSION['avatar'] = $avatarPath;
                }
                $_SESSION['user_name'] = $name;
                $_SESSION['currency']  = $currency;
                $user    = $userModel->findById($userId);
                $success = true;
            }

        // ════════════════════════════════
        // Password change
        // ════════════════════════════════
        } elseif (isset($_POST['action']) && $_POST['action'] === 'change_password') {

            $currentPw  = $_POST['current_password']  ?? '';
            $newPw      = $_POST['new_password']       ?? '';
            $confirmPw  = $_POST['confirm_new_password'] ?? '';

            if (empty($currentPw)) {
                $pwErrors['current_password'] = 'Current password is required.';
            } elseif (!$userModel->verifyPassword($currentPw, $user['password'])) {
                $pwErrors['current_password'] = 'Current password is incorrect.';
            }

            if (empty($newPw)) {
                $pwErrors['new_password'] = 'New password is required.';
            } elseif (strlen($newPw) < 8) {
                $pwErrors['new_password'] = 'New password must be at least 8 characters.';
            }

            if ($newPw !== $confirmPw) {
                $pwErrors['confirm_new_password'] = 'Passwords do not match.';
            }

            if (empty($pwErrors)) {
                $userModel->updatePassword($userId, $newPw);
                $pwSuccess = true;
            }
        }
    }
}

$pageTitle = 'Edit Profile';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="ft-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="ft-main">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="ft-content">
            <?php renderFlash(); ?>

            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="fw-bold mb-0">Edit Profile</h4>
                    <small class="text-muted">Update your name, currency, and profile picture</small>
                </div>
            </div>

            <!-- Two-column full-width layout -->
            <div class="row g-4">

                <!-- LEFT: Profile info + avatar -->
                <div class="col-12 col-xl-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0 pt-3 pb-0">
                            <h6 class="fw-bold mb-0">
                                <i class="bi bi-person-circle me-2" style="color:var(--ft-primary);"></i>Profile Information
                            </h6>
                        </div>
                        <div class="card-body p-4">

                            <?php if ($success): ?>
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle me-2"></i>Profile updated successfully.
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($errors['general'])): ?>
                                <div class="alert alert-danger"><?= e($errors['general']) ?></div>
                            <?php endif; ?>

                            <form action="" method="POST" enctype="multipart/form-data" novalidate>
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="update_profile">

                                <!-- Avatar preview + upload -->
                                <div class="text-center mb-4">
                                    <div style="position:relative; display:inline-block;">
                                        <?php if (!empty($user['avatar'])): ?>
                                            <img id="avatarPreview"
                                                 src="<?= APP_URL ?>/<?= e($user['avatar']) ?>"
                                                 alt="Profile Picture"
                                                 style="width:110px;height:110px;border-radius:50%;object-fit:cover;border:3px solid var(--ft-primary);box-shadow:0 4px 16px rgba(21,101,192,0.2);">
                                        <?php else: ?>
                                            <div id="avatarPreview"
                                                 style="width:110px;height:110px;border-radius:50%;background:linear-gradient(135deg,#1565c0,#00b4d8);display:flex;align-items:center;justify-content:center;font-size:2.5rem;font-weight:700;color:#fff;border:3px solid var(--ft-primary);box-shadow:0 4px 16px rgba(21,101,192,0.2);">
                                                <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                        <!-- Camera overlay -->
                                        <label for="avatar" style="
                                            position:absolute;bottom:4px;right:4px;
                                            width:30px;height:30px;
                                            background:var(--ft-primary);
                                            border-radius:50%;
                                            display:flex;align-items:center;justify-content:center;
                                            cursor:pointer;
                                            box-shadow:0 2px 8px rgba(0,0,0,0.25);
                                        " title="Change photo">
                                            <i class="bi bi-camera-fill" style="color:#fff;font-size:0.8rem;"></i>
                                        </label>
                                    </div>

                                    <input type="file" id="avatar" name="avatar"
                                           accept="image/jpeg,image/png,image/gif,image/webp"
                                           class="d-none" onchange="previewAvatar(this)">

                                    <?php if (!empty($errors['avatar'])): ?>
                                        <div class="text-danger small mt-2">
                                            <i class="bi bi-exclamation-circle me-1"></i><?= e($errors['avatar']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="text-muted small mt-2">Click the camera icon to change photo</div>
                                    <div class="text-muted" style="font-size:0.7rem;">JPG, PNG, GIF or WEBP &middot; Max 2 MB</div>
                                </div>

                                <!-- Full Name -->
                                <div class="mb-3">
                                    <label for="name" class="form-label fw-semibold">Full Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                                        <input type="text" id="name" name="name"
                                               class="form-control <?= !empty($errors['name']) ? 'is-invalid' : '' ?>"
                                               value="<?= e($user['name'] ?? '') ?>" required>
                                        <?php if (!empty($errors['name'])): ?>
                                            <div class="invalid-feedback"><?= e($errors['name']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Username (read-only) -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-at"></i></span>
                                        <input type="text" class="form-control bg-light"
                                               value="<?= e($user['username'] ?? '') ?>" disabled>
                                    </div>
                                    <div class="form-text">Username cannot be changed.</div>
                                </div>

                                <!-- Email (read-only) -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                                        <input type="email" class="form-control bg-light"
                                               value="<?= e($user['email'] ?? '') ?>" disabled>
                                    </div>
                                </div>

                                <!-- Currency -->
                                <div class="mb-4">
                                    <label for="currency" class="form-label fw-semibold">Currency</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-currency-exchange"></i></span>
                                        <select id="currency" name="currency"
                                                class="form-select <?= !empty($errors['currency']) ? 'is-invalid' : '' ?>">
                                            <?php foreach (CURRENCIES as $cur): ?>
                                                <option value="<?= e($cur) ?>"
                                                    <?= ($user['currency'] ?? 'ETB') === $cur ? 'selected' : '' ?>>
                                                    <?= e($cur) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (!empty($errors['currency'])): ?>
                                            <div class="invalid-feedback"><?= e($errors['currency']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                                    <i class="bi bi-check-lg me-2"></i>Save Changes
                                </button>
                            </form>

                        </div><!-- /.card-body -->
                    </div><!-- /.card -->
                </div><!-- /.col profile -->

                <!-- RIGHT: Change Password -->
                <div class="col-12 col-xl-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0 pt-3 pb-0">
                            <h6 class="fw-bold mb-0">
                                <i class="bi bi-shield-lock me-2" style="color:var(--ft-primary);"></i>Change Password
                            </h6>
                        </div>
                        <div class="card-body p-4">

                            <?php if ($pwSuccess): ?>
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle me-2"></i>Password changed successfully.
                                </div>
                            <?php endif; ?>

                            <form action="" method="POST" novalidate>
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="change_password">

                                <!-- Current Password -->
                                <div class="mb-3">
                                    <label for="current_password" class="form-label fw-semibold">Current Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                        <input type="password" id="current_password" name="current_password"
                                               class="form-control <?= !empty($pwErrors['current_password']) ? 'is-invalid' : '' ?>"
                                               placeholder="Enter current password">
                                        <button class="btn btn-outline-secondary" type="button"
                                                onclick="togglePw('current_password','eye_cur')">
                                            <i class="bi bi-eye" id="eye_cur"></i>
                                        </button>
                                        <?php if (!empty($pwErrors['current_password'])): ?>
                                            <div class="invalid-feedback"><?= e($pwErrors['current_password']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- New Password -->
                                <div class="mb-3">
                                    <label for="new_password" class="form-label fw-semibold">New Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                        <input type="password" id="new_password" name="new_password"
                                               class="form-control <?= !empty($pwErrors['new_password']) ? 'is-invalid' : '' ?>"
                                               placeholder="Min. 8 characters">
                                        <button class="btn btn-outline-secondary" type="button"
                                                onclick="togglePw('new_password','eye_new')">
                                            <i class="bi bi-eye" id="eye_new"></i>
                                        </button>
                                        <?php if (!empty($pwErrors['new_password'])): ?>
                                            <div class="invalid-feedback"><?= e($pwErrors['new_password']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Confirm New Password -->
                                <div class="mb-4">
                                    <label for="confirm_new_password" class="form-label fw-semibold">Confirm New Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                        <input type="password" id="confirm_new_password" name="confirm_new_password"
                                               class="form-control <?= !empty($pwErrors['confirm_new_password']) ? 'is-invalid' : '' ?>"
                                               placeholder="Repeat new password">
                                        <button class="btn btn-outline-secondary" type="button"
                                                onclick="togglePw('confirm_new_password','eye_con')">
                                            <i class="bi bi-eye" id="eye_con"></i>
                                        </button>
                                        <?php if (!empty($pwErrors['confirm_new_password'])): ?>
                                            <div class="invalid-feedback"><?= e($pwErrors['confirm_new_password']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                                    <i class="bi bi-shield-check me-2"></i>Update Password
                                </button>
                            </form>

                        </div><!-- /.card-body -->
                    </div><!-- /.card -->
                </div><!-- /.col password -->

            </div><!-- /.row g-4 -->

        </div><!-- /.ft-content -->
    </div><!-- /.ft-main -->
</div><!-- /.ft-layout -->

<script>
function previewAvatar(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById('avatarPreview');
        if (preview.tagName === 'IMG') {
            preview.src = e.target.result;
        } else {
            const img = document.createElement('img');
            img.id        = 'avatarPreview';
            img.src       = e.target.result;
            img.alt       = 'Profile Picture';
            img.style.cssText = preview.style.cssText;
            img.style.objectFit = 'cover';
            preview.parentNode.replaceChild(img, preview);
        }
    };
    reader.readAsDataURL(input.files[0]);
}

function togglePw(inputId, eyeId) {
    const input = document.getElementById(inputId);
    const eye   = document.getElementById(eyeId);
    if (input.type === 'password') {
        input.type    = 'text';
        eye.className = 'bi bi-eye-slash';
    } else {
        input.type    = 'password';
        eye.className = 'bi bi-eye';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
