<?php

// ────────────────────────────────────────────────
// CONFIGURATION - change PROJECT_BASE_FS if your folder name changes
// ────────────────────────────────────────────────
define('PROJECT_BASE_FS', $_SERVER['DOCUMENT_ROOT'] . '/G19bcsy3Nang/');
define('USER_PHOTO_DIR_FS', PROJECT_BASE_FS . 'assets/images/user_photos/');
define('USER_PHOTO_DIR_REL', 'assets/images/user_photos/');
define('DEFAULT_PHOTO', './assets/images/emptyuser.png');

// ────────────────────────────────────────────────
// USER MANAGEMENT FUNCTIONS
// ────────────────────────────────────────────────

function usernameExists($username)
{
    global $db;
    $query = $db->prepare('SELECT 1 FROM tbl_users WHERE username = ? LIMIT 1');
    $query->bind_param('s', $username);
    $query->execute();
    $result = $query->get_result();
    return $result->num_rows > 0;
}

function registerUser($name, $username, $passwd)
{
    global $db;
    $query = $db->prepare('INSERT INTO tbl_users (name, username, passwd) VALUES (?, ?, ?)');
    $query->bind_param('sss', $name, $username, $passwd);
    $query->execute();
    return $db->affected_rows > 0;
}

function logUserIn($username, $passwd)
{
    global $db;
    $query = $db->prepare('SELECT * FROM tbl_users WHERE username = ? AND passwd = ?');
    $query->bind_param('ss', $username, $passwd);
    $query->execute();
    $result = $query->get_result();
    return $result->num_rows ? $result->fetch_object() : false;
}

function loggedInUser()
{
    global $db;
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $user_id = (int) $_SESSION['user_id']; // safer casting
    if ($user_id < 1) {
        return null;
    }

    $query = $db->prepare('SELECT * FROM tbl_users WHERE id = ? LIMIT 1');
    $query->bind_param('i', $user_id);
    $query->execute();
    $result = $query->get_result();
    return $result->num_rows ? $result->fetch_object() : null;
}

function isAdmin()
{
    $user = loggedInUser();
    return $user && $user->level_user === 'admin';
}

function isUserHasPassword($passwd)
{
    global $db;
    $user = loggedInUser();
    if (!$user) {
        return false;
    }

    $query = $db->prepare("SELECT 1 FROM tbl_users WHERE id = ? AND passwd = ? LIMIT 1");
    $query->bind_param('is', $user->id, $passwd);
    $query->execute();
    $result = $query->get_result();
    return $result->num_rows > 0;
}

function setUserNewPassword($passwd)
{
    global $db;
    $user = loggedInUser();
    if (!$user) {
        return false;
    }

    $query = $db->prepare("UPDATE tbl_users SET passwd = ? WHERE id = ?");
    $query->bind_param('si', $passwd, $user->id);
    $query->execute();
    return $db->affected_rows > 0;
}

function updateUserName($name, $userId)
{
    global $db;
    $userId = (int) $userId;
    if ($userId < 1) {
        return false;
    }

    $query = $db->prepare("UPDATE tbl_users SET name = ? WHERE id = ?");
    $query->bind_param('si', $name, $userId);
    $query->execute();
    return $db->affected_rows > 0;
}


// PHOTO FUNCTIONS

function uploadUserPhoto($file)
{
    global $db;
    $user = loggedInUser();

    // Allowed file types
    $allowed_types = array('image/jpg', 'image/jpeg', 'image/png');
    $allowed_extensions = array('jpg', 'jpeg', 'png');

    // Check if file is selected
    if (!isset($file['name']) || $file['name'] == '') {
        return array('success' => false, 'message' => 'No file selected for upload');
    }

    // Check file size
    $max_size = 5 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        return array('success' => false, 'message' => 'File size exceeds 5MB limit');
    }

    // Check MIME type
    if (!in_array($file['type'], $allowed_types)) {
        return array('success' => false, 'message' => 'Only JPG, JPEG, and PNG files are allowed');
    }

    // Check file extension
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_extensions)) {
        return array('success' => false, 'message' => 'Invalid file extension');
    }

    // Generate random filename
    $random_name = bin2hex(random_bytes(8)) . '.' . $file_ext;
    $upload_dir = __DIR__ . '/../../assets/images/';
    $file_path = $upload_dir . $random_name;
    $db_photo_path = './assets/images/' . $random_name;

    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        return array('success' => false, 'message' => 'Failed to upload file');
    }

    // Delete old photo if exists
    $old_photo_query = $db->prepare('SELECT photo FROM tbl_users WHERE id = ?');
    $old_photo_query->bind_param('s', $user->id);
    $old_photo_query->execute();
    $result = $old_photo_query->get_result();
    if ($result->num_rows && $row = $result->fetch_assoc()) {
        if ($row['photo']) {
            // Extract filename from full path
            $old_file = $upload_dir . basename($row['photo']);
            if (file_exists($old_file)) {
                unlink($old_file);
            }
        }
    }

    // Update database with new photo path (full path)
    $query = $db->prepare('UPDATE tbl_users SET photo = ? WHERE id = ?');
    $query->bind_param('ss', $db_photo_path, $user->id);
    $query->execute();

    if ($db->affected_rows) {
        return array('success' => true, 'message' => 'Photo uploaded successfully', 'photo' => $db_photo_path);
    } else {
        unlink($file_path);
        return array('success' => false, 'message' => 'Failed to save photo information');
    }
}
function deleteUserPhoto()
{
    global $db;
    $user = loggedInUser();

    // Get current photo
    $query = $db->prepare('SELECT photo FROM tbl_users WHERE id = ?');
    $query->bind_param('s', $user->id);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows && $row = $result->fetch_assoc()) {
        $photo = $row['photo'];
        if ($photo) {
            // Delete file from server - extract filename from full path
            $upload_dir = __DIR__ . '/../../assets/images/';
            $filename = basename($photo);
            $file_path = $upload_dir . $filename;
            if (file_exists($file_path)) {
                unlink($file_path);
            }

            // Update database
            $update_query = $db->prepare('UPDATE tbl_users SET photo = NULL WHERE id = ?');
            $update_query->bind_param('d', $user->id);
            $update_query->execute();

            if ($db->affected_rows) {
                return array('success' => true, 'message' => 'Photo deleted successfully');
            } else {
                return array('success' => false, 'message' => 'Failed to delete photo from database');
            }
        } else {
            return array('success' => false, 'message' => 'No photo to delete');
        }
    }
    return array('success' => false, 'message' => 'Error retrieving photo information');
}
function getUserPhoto()
{
    $user = loggedInUser();
    if ($user && isset($user->photo) && $user->photo) {
        return $user->photo;
    }
    return './assets/images/emptyuser.png';
}
?>