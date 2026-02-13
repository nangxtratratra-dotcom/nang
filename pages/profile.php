<?php

// PROFILE PAGE LOGIC


$oldPasswd = $newPasswd = $confirmNewPasswd = '';
$oldPasswdErr = $newPasswdErr = '';
$photoMessage = '';
$user = loggedInUser();
if (!$user) {
    header("Location: ./?page=login");
    exit;
}

// Get current photo path (uses your function)
$currentPhoto = getUserPhoto();

$uploadMessage = '';
//upload photo
if (isset($_POST['uploadPhoto']) && !empty($_FILES['photo']['name'])) {

    $result = uploadUserPhoto($_FILES['photo']);

    if ($result['success']) {
        $uploadMessage = '<div class="alert alert-success">' . htmlspecialchars($result['message']) . '</div>';
        // After successful upload → refresh the photo path
        $currentPhoto = getUserPhoto();  // now returns the new one
    } else {
        $uploadMessage = '<div class="alert alert-danger">' . htmlspecialchars($result['message']) . '</div>';
    }
}
//delete photo
if (isset($_POST['deletePhoto'])) {

    $result = deleteUserPhoto();

    if ($result['success']) {
        $uploadMessage = '<div class="alert alert-info">' . htmlspecialchars($result['message']) . '</div>';
        $currentPhoto = getUserPhoto();  // should return emptyuser.png
    } else {
        $uploadMessage = '<div class="alert alert-warning">' . htmlspecialchars($result['message']) . '</div>';
    }
}

//Password Change 
if (isset($_POST['changePasswd'])) {
    $oldPasswd = trim($_POST['oldPasswd'] ?? '');
    $newPasswd = trim($_POST['newPasswd'] ?? '');
    $confirmNewPasswd = trim($_POST['confirmNewPasswd'] ?? '');

    $oldPasswdErr = $newPasswdErr = '';

    if (empty($oldPasswd))
        $oldPasswdErr = 'Please enter your old password';
    if (empty($newPasswd))
        $newPasswdErr = 'Please enter a new password';
    if ($newPasswd !== $confirmNewPasswd)
        $newPasswdErr = 'Passwords do not match';

    if (empty($oldPasswdErr) && empty($newPasswdErr)) {
        if (!isUserHasPassword($oldPasswd)) {
            $oldPasswdErr = 'Old password is incorrect';
        } else {
            if (setUserNewPassword($newPasswd)) {   // note: typo → should be setUserNewPassword ?
                // Logout after password change
                unset($_SESSION['user_id']);
                session_destroy();
                header("Location: ./?page=login");
                exit;
            } else {
                $photoMessage = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        Failed to update password. Please try again.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
            }
        }
    }
}
?>

<div class="row justify-content-center mb-5">
    <div class="col-12 col-md-6 col-lg-5 text-center">

        <?php if ($uploadMessage): ?>
            <?= $uploadMessage ?>
        <?php endif; ?>

        <form method="post" action="./?page=profile" enctype="multipart/form-data" id="photoForm">
            <!-- Hidden file input -->
            <input type="file" name="photo" id="profileUpload" accept="image/jpeg,image/jpg,image/png" hidden>

            <!-- Clickable image area -->
            <label for="profileUpload" style="cursor: pointer; display: inline-block;">
                <img id="profilePreview" src="<?= htmlspecialchars($currentPhoto) ?>" class="rounded-circle shadow"
                    style="width: 180px; height: 180px; object-fit: cover; border: 4px solid #dee2e6; background: #f8f9fa;">
            </label>

            <div class="mt-4 d-flex justify-content-center gap-3 flex-wrap">
                <button type="submit" name="uploadPhoto" class="btn btn-success px-4" id="btnUpload" disabled>
                    Upload
                </button>
                <button type="submit" name="deletePhoto" class="btn btn-danger px-4"
                    onclick="return confirm('Are you sure you want to remove your profile picture?');">
                    Delete
                </button>
            </div>
        </form>

    </div>
</div>

<!-- JavaScript: Live Preview + Enable Upload Button -->
<script>
    document.getElementById('profileUpload').addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (!file) return;

        // Show instant preview
        const reader = new FileReader();
        reader.onload = function (ev) {
            document.getElementById('profilePreview').src = ev.target.result;
        };
        reader.readAsDataURL(file);

        // Enable upload button
        document.getElementById('btnUpload').disabled = false;
    });
</script>

<!-- Password Section -->
<div class="col-6">
    <form method="post" action="./?page=profile" class="col-md-10 mx-auto" id="passwordForm">
        <h4 class="mb-4">Change Password</h4>

        <div class="mb-3">
            <label class="form-label">Old Password</label>
            <input value="<?php echo htmlspecialchars($oldPasswd); ?>" name="oldPasswd" type="password"
                class="form-control <?php echo $oldPasswdErr ? 'is-invalid' : ''; ?>">
            <div class="invalid-feedback"><?php echo $oldPasswdErr ?: ' '; ?></div>
        </div>

        <div class="mb-3">
            <label class="form-label">New Password</label>
            <input name="newPasswd" type="password"
                class="form-control <?php echo $newPasswdErr ? 'is-invalid' : ''; ?>">
            <div class="invalid-feedback"><?php echo $newPasswdErr ?: ' '; ?></div>
        </div>

        <div class="mb-3">
            <label class="form-label">Confirm New Password</label>
            <input name="confirmNewPasswd" type="password" class="form-control">
        </div>

        <button type="submit" name="changePasswd" class="btn btn-primary px-5 change-password-btn"
            onclick="return confirmPasswordChange();">
            Change Password
        </button>
    </form>
</div>
</div>



<script>
    // Photo preview + enable/disable upload button
    const fileInput = document.getElementById('profileUpload');
    const profileImg = document.getElementById('profileImg');
    const uploadBtn = document.getElementById('uploadBtn');
    let originalSrc = profileImg.src;

    fileInput.addEventListener('change', function () {
        const file = this.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function (e) {
                profileImg.src = e.target.result;
            };
            reader.readAsDataURL(file);
            uploadBtn.disabled = false;
        } else {
            profileImg.src = originalSrc;
            uploadBtn.disabled = true;
        }
    });

    // Optional: reset preview if user cancels file selection
    fileInput.addEventListener('click', () => {
        // Reset on next selection attempt
        setTimeout(() => {
            if (!fileInput.files.length) {
                profileImg.src = originalSrc;
                uploadBtn.disabled = true;
            }
        }, 100);
    });

    // Confirmation for Delete Photo
    function confirmDeletePhoto() {
        const userConfirmed = confirm('⚠️ Are you sure you want to permanently delete your profile photo?\n\nThis action cannot be undone.');
        return userConfirmed; // If false, form submission is prevented
    }

    // Confirmation for Change Password
    function confirmPasswordChange() {
        // Check if form fields are filled
        const oldPasswd = document.querySelector('input[name="oldPasswd"]').value;
        const newPasswd = document.querySelector('input[name="newPasswd"]').value;
        const confirmNewPasswd = document.querySelector('input[name="confirmNewPasswd"]').value;

        // If fields are empty, let the form validation handle it
        if (!oldPasswd || !newPasswd || !confirmNewPasswd) {
            return true; // Let the form submit and show validation errors
        }

        // Custom confirmation dialog
        const message = '🔐 Change Password Confirmation\n\n' +
            'Are you sure you want to change your password?\n\n' +
            '• You will be logged out immediately\n' +
            '• You will need to use your new password to log in again\n\n' +
            'Do you want to continue?';

        return confirm(message);
    }

    // Optional: Add form validation before submission
    document.getElementById('passwordForm')?.addEventListener('submit', function (e) {
        const newPasswd = document.querySelector('input[name="newPasswd"]').value;
        const confirmNewPasswd = document.querySelector('input[name="confirmNewPasswd"]').value;

        // Client-side password match validation
        if (newPasswd !== confirmNewPasswd) {
            e.preventDefault();
            alert('❌ Passwords do not match!\n\nPlease make sure your new password and confirmation match.');
            return false;
        }

        // Password strength validation (optional)
        if (newPasswd.length > 0 && newPasswd.length < 6) {
            e.preventDefault();
            alert('❌ Password too short!\n\nPassword must be at least 6 characters long.');
            return false;
        }

        return true;
    });

    // Enhanced delete photo confirmation with custom styling (optional)
    function enhancedDeleteConfirmation() {
        // This is a more visual confirmation alternative to confirm()
        // Uncomment to use custom dialog instead of native confirm

        /*
        const overlay = document.createElement('div');
        overlay.className = 'confirmation-dialog-overlay';
        
        const dialog = document.createElement('div');
        dialog.className = 'confirmation-dialog';
        dialog.innerHTML = `
            <h5>Delete Profile Photo</h5>
            <p>Are you sure you want to permanently delete your profile photo? This action cannot be undone.</p>
            <div class="btn-container">
                <button class="btn btn-secondary" onclick="this.closest('.confirmation-dialog-overlay').remove()">Cancel</button>
                <button class="btn btn-danger" onclick="document.getElementById('deletePhotoBtn').click(); this.closest('.confirmation-dialog-overlay').remove()">Delete</button>
            </div>
        `;
        
        overlay.appendChild(dialog);
        document.body.appendChild(overlay);
        */

        // Using native confirm for simplicity
        return confirm('⚠️ Are you sure you want to permanently delete your profile photo?\n\nThis action cannot be undone.');
    }

    // Show success message after password change (if redirected back)
    window.addEventListener('load', function () {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('password_changed') === '1') {
            alert('✅ Password changed successfully!\n\nYou have been logged out. Please log in with your new password.');
        }
    });
</script>