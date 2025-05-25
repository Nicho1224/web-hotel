<?php
// Profile Section for landing_page.php
// This file is included in landing_page.php

// Check if config file is included
if (!defined('INCLUDED_CONFIG')) {
    require_once 'config.php';
}

// Make sure user is logged in
if (!isset($_SESSION['id_user']) || !isset($_SESSION['lv'])) {
    echo '<div class="container py-5">';
    echo '<div class="alert alert-warning" role="alert">';
    echo 'You must be logged in to view your profile. <a href="login.php" class="alert-link">Login here</a>';
    echo '</div>';
    echo '</div>';
    return;
}

$id_user = $_SESSION['id_user'];
$sql = "SELECT * FROM user WHERE id_user = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$id_user]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo '<div class="container py-5">';
    echo '<div class="alert alert-danger" role="alert">User data not found.</div>';
    echo '</div>';
    return;
}

$error = [];
$success_message = '';

// Handle Username Update
if (isset($_POST['ganti_username'])) {
    $new_username = $_POST['username'] ?? '';
    $current_password = $_POST['current_password'] ?? '';

    if (!empty($new_username) && $new_username !== $user['username']) {
        if (password_verify($current_password, $user['password'])) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM user WHERE username = ? AND id_user != ?");
            $stmt->execute([$new_username, $id_user]);
            if ($stmt->fetchColumn() == 0) {
                $sql = "UPDATE user SET username = ? WHERE id_user = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$new_username, $id_user]);
                $success_message = 'Username has been updated successfully!';
                
                // Refresh user data
                $stmt = $conn->prepare($sql);
                $stmt->execute([$id_user]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error[] = "Username is already taken by another user.";
            }
        } else {
            $error[] = "Current password is incorrect. Cannot change username.";
        }
    }
}

// Handle Password Change
if (isset($_POST['ganti_password'])) {
    $username_input = $_POST['username_now'] ?? '';
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    if ($username_input !== $user['username']) {
        $error[] = "Username does not match.";
    } elseif (!password_verify($old_password, $user['password'])) {
        $error[] = "Old password is incorrect.";
    } elseif (empty($new_password)) {
        $error[] = "New password cannot be empty.";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE user SET password = ? WHERE id_user = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$hashed, $id_user]);
        $success_message = 'Password has been updated successfully!';
    }
}

// Handle Profile Photo Change
if (isset($_POST['ganti_foto']) && isset($_FILES['profile_image'])) {
    $profile_image = $_FILES['profile_image'];
    
    if ($profile_image['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($profile_image['type'], $allowed_types)) {
            $upload_dir = 'uploads/';
            $new_filename = $upload_dir . basename($profile_image['name']);

            if (move_uploaded_file($profile_image['tmp_name'], $new_filename)) {
                $sql = "UPDATE user SET profile_image = ? WHERE id_user = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$new_filename, $id_user]);

                $success_message = 'Profile photo has been updated successfully!';
                
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM user WHERE id_user = ?");
                $stmt->execute([$id_user]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error[] = 'Error uploading the image.';
            }
        } else {
            $error[] = 'Only image files (JPEG, PNG, GIF) are allowed.';
        }
    } else {
        $error[] = 'No file was uploaded or the file is too large.';
    }
}
?>

<section class="page-header" style="background-image: url('hotel2.jpg');">
  <div class="container">
    <div class="row">
      <div class="col-lg-8">
        <h1 class="page-title" data-aos="fade-up">My Profile</h1>
        <nav aria-label="breadcrumb" data-aos="fade-up" data-aos-delay="100">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="landing_page.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Profile</li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</section>

<section class="profile-section py-5">
    <div class="container">
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $success_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <?php foreach ($error as $err): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $err ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-4">
                <div class="card" data-aos="fade-up">
                    <div class="card-body profile-card pt-4 d-flex flex-column align-items-center">
                        <img src="<?= !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : 'assets/img/profile-img.jpg' ?>" 
                             alt="Profile" class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
                        <h2 class="mt-3"><?= htmlspecialchars($user['username']) ?></h2>
                        <p class="text-muted"><?= ucfirst(htmlspecialchars($user['lv'])) ?></p>
                        
                        <div class="mt-4 w-100">
                            <form action="" method="post" enctype="multipart/form-data" class="text-center">
                                <div class="mb-3">
                                    <label for="profile_image" class="form-label">Change Profile Picture</label>
                                    <input class="form-control" type="file" id="profile_image" name="profile_image">
                                </div>
                                <button type="submit" name="ganti_foto" class="btn btn-primary">
                                    <i class="bi bi-upload me-1"></i> Upload
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8" data-aos="fade-up" data-aos-delay="100">
                <div class="card">
                    <div class="card-body pt-3">
                        <!-- Tabs navigation -->
                        <ul class="nav nav-tabs nav-tabs-bordered">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#profile-overview">
                                    Overview
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-edit">
                                    Change Username
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-change-password">
                                    Change Password
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content pt-4">
                            <!-- Overview Tab -->
                            <div class="tab-pane fade show active" id="profile-overview">
                                <h5 class="card-title">Profile Details</h5>
                                <div class="row mb-3">
                                    <div class="col-lg-3">Username</div>
                                    <div class="col-lg-9"><?= htmlspecialchars($user['username']) ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-lg-3">Full Name</div>
                                    <div class="col-lg-9"><?= htmlspecialchars($user['nama']) ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-lg-3">Email</div>
                                    <div class="col-lg-9"><?= htmlspecialchars($user['email']) ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-lg-3">Phone</div>
                                    <div class="col-lg-9"><?= htmlspecialchars($user['telepon'] ?? 'Not set') ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-lg-3">Last Login</div>
                                    <div class="col-lg-9"><?= htmlspecialchars($user['last_online'] ?? 'Not available') ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-3">Account Type</div>
                                    <div class="col-lg-9"><?= ucfirst(htmlspecialchars($user['lv'])) ?></div>
                                </div>
                            </div>

                            <!-- Edit Username Tab -->
                            <div class="tab-pane fade" id="profile-edit">
                                <h5 class="card-title">Change Username</h5>
                                <form method="post" action="">
                                    <div class="row mb-3">
                                        <label for="username" class="col-md-4 col-lg-3 col-form-label">New Username</label>
                                        <div class="col-md-8 col-lg-9">
                                            <input name="username" type="text" class="form-control" id="username" 
                                                   value="<?= htmlspecialchars($user['username']) ?>" required>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="current_password" class="col-md-4 col-lg-3 col-form-label">Current Password</label>
                                        <div class="col-md-8 col-lg-9">
                                            <input name="current_password" type="password" class="form-control" id="current_password" required>
                                            <small class="text-muted">Enter your current password to confirm</small>
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <button type="submit" name="ganti_username" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>

                            <!-- Change Password Tab -->
                            <div class="tab-pane fade" id="profile-change-password">
                                <h5 class="card-title">Change Password</h5>
                                <form method="post" action="">
                                    <div class="row mb-3">
                                        <label for="username_now" class="col-md-4 col-lg-3 col-form-label">Username</label>
                                        <div class="col-md-8 col-lg-9">
                                            <input name="username_now" type="text" class="form-control" id="username_now" 
                                                   value="<?= htmlspecialchars($user['username']) ?>" required>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="old_password" class="col-md-4 col-lg-3 col-form-label">Current Password</label>
                                        <div class="col-md-8 col-lg-9">
                                            <input name="old_password" type="password" class="form-control" id="old_password" required>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="new_password" class="col-md-4 col-lg-3 col-form-label">New Password</label>
                                        <div class="col-md-8 col-lg-9">
                                            <input name="new_password" type="password" class="form-control" id="new_password" required>
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <button type="submit" name="ganti_password" class="btn btn-primary">Change Password</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>