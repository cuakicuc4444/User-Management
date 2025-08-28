<?php
session_start();
// --- Tạo tài khoản admin mặc định nếu chưa có ---
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "userdb";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$adminPass = md5('admin123');
$check = $conn->query("SELECT id FROM accounts WHERE id=1");
if ($check && $check->num_rows == 0) {
    $sql = "INSERT INTO accounts (id, rule, status, user_name, email, password, created_at) VALUES (1, 'admin', 'active', 'admin', 'admin@gmail.com', '$adminPass', '2004-06-16 16:16:00')";
    $conn->query($sql);
    if ($conn->error) { echo $conn->error; }
}
$conn->close();

// --- Hết tạo tài khoản admin mặc định ---
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: /sign_in');
    exit();
}
if (!isset($_SESSION['user_email'])) {
    header('Location: /sign_in');
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">

</head>

<body>
<?php ?>
    <div class="main-center">
    <?php
    $addErrorArr = [];
    $apiUrl = 'http://localhost:8080/users/get';
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $users = json_decode($response, true);
    if (!is_array($users)) $users = [];
    $searchError = '';
    $filteredUsers = $users;
    if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
        $deleteId = intval($_GET['delete_id']);
        if ($deleteId <= 0) {
            echo '<div id="deleteMsg" class="delete-msg">Invalid user ID!</div>';
        } else {
        $deleteUrl = 'http://localhost:8080/users/delete/' . $deleteId;
            $chd = curl_init($deleteUrl);
            curl_setopt($chd, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($chd, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($chd, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: application/json'));
            curl_setopt($chd, CURLOPT_FOLLOWLOCATION, true);
            $delResponse = curl_exec($chd);
            $delStatus = curl_getinfo($chd, CURLINFO_HTTP_CODE);
            curl_close($chd);
            $msg = '';
            if ($delStatus == 200 || $delStatus == 204) {
                $msg = 'User deleted successfully!';
                $url = strtok($_SERVER["REQUEST_URI"], '?');
                $qs = $_GET;
                unset($qs['delete_id']);
                $qs['msg'] = urlencode($msg);
                header('Location: ' . $url . (count($qs) ? '?' . http_build_query($qs) : ''));
                exit;
            } else {
                $msg = 'Delete user failed!';
            }
        }
    }
    if ($users && is_array($users)) {
        if (isset($_GET['search']) && $_GET['search'] !== '') {
            $search = mb_strtolower(trim($_GET['search']));
            $filteredUsers = array_filter($users, function($u) use ($search) {
                return (mb_strpos(mb_strtolower($u['user_name']), $search) !== false)
                    || (mb_strpos(mb_strtolower($u['email']), $search) !== false);
            });
            if (count($filteredUsers) === 0) {
                $searchError = 'No matching user found: ' . htmlspecialchars($search);
                $filteredUsers = $users;
            }
        }
        $sort = isset($_GET['sort']) && $_GET['sort'] === 'desc' ? 'desc' : 'asc';
        usort($filteredUsers, function($a, $b) use ($sort) {
            return $sort === 'desc' ? $b['id'] - $a['id'] : $a['id'] - $b['id'];
        });
        $total = count($filteredUsers);
        $perPage = 5;
        $totalPages = ceil($total / $perPage);
    $pageError = '';
        if (isset($_GET['page'])) {
            $inputPage = $_GET['page'];
            if (!is_numeric($inputPage) || intval($inputPage) < 1 || intval($inputPage) > $totalPages) {
                $pageError = 'Page not found.';
                $page = 1;
            } else {
                $page = intval($inputPage);
            }
        } else {
            $page = 1;
        }
        $start = ($page - 1) * $perPage;
        $usersPage = array_slice($filteredUsers, $start, $perPage);
    } else {
        $usersPage = [];
        $totalPages = 1;
        $page = 1;
    }
    ?>
    <h2 style="text-align:center;  color: #222;margin:32px auto 12px auto;font-size:2.8rem;font-family:'Georgia',serif;font-weight:bold;">
        <a href="<?php echo strtok($_SERVER['REQUEST_URI'], '?'); ?>" style="color:inherit;text-decoration:none;cursor:pointer;">
            User Management
        </a>
    </h2>
<?php
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['initial_total_users'])) {
            $_SESSION['initial_total_users'] = count($users);
        }
        $initialTotalUsers = $_SESSION['initial_total_users'];
        $currentTotalUsers = count($users);
        $isFullTable = (!isset($_GET['search']) || $_GET['search'] === '') && (!isset($_GET['searchType']) || $_GET['searchType'] === 'id');
        $totalUsers = $currentTotalUsers;
?>
<div class="user-stats-bar" style="display:flex;justify-content:space-between;align-items:center;width:100%;max-width:950px;margin:0 auto 10px auto;padding-left:0;padding-right:0;gap:10px;">
    <div class="user-stats">
        <?php echo 'Total users: ' . $totalUsers ; ?>
    </div>
    <div style="display:flex;align-items:center;gap:10px;">
        <form method="get" class="search-form" style="margin-bottom:0;">
            <div class="search-input-wrapper">
                <input type="text" name="search" placeholder="Search username or email..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" class="search-input">
            </div>
            <button type="submit" class="search-btn">Search</button>
        </form>
        <button type="button" class="search-btn add-btn-center" onclick="openUserModal(null)">Add</button>
            <?php if (isset($_SESSION['user_rule']) && $_SESSION['user_rule'] === 'admin'): ?>
                <a href="/account" class="search-btn" style="background:#197278; color:#fff;">Account Management</a>
            <?php endif; ?>
            <form method="get" action="index.php" style="display:inline; margin-left:8px;">
                <button type="submit" name="logout" value="1" class="search-btn" style="background:#197278; color:#fff;">Log out</button>
            </form>
    </div>
</div>

<div id="userModal" class="modal" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.25);align-items:center;justify-content:center;">
    <div class="user-modal-content">
        <h3 id="modalTitle" style="font-size:1.5rem;font-weight:bold;margin-bottom:10px;text-align:center;margin-left:30px;">User</h3>
        <form id="userForm" method="post" autocomplete="off" onsubmit="return submitUserModal(event)">
            <input type="hidden" name="user_id" id="modal_user_id">
            <div style="margin-bottom:12px;">
                <label>Username</label>
                <input type="text" name="user_name" id="modal_user_name" class="input-user" autocomplete="off" style="width:100%;">
            </div>
            <div style="margin-bottom:12px;">
                <label>First Name</label>
                <input type="text" name="first_name" id="modal_first_name" class="input-user" autocomplete="off" style="width:100%;">
            </div>
            <div style="margin-bottom:12px;">
                <label>Last Name</label>
                <input type="text" name="last_name" id="modal_last_name" class="input-user" autocomplete="off" style="width:100%;">
            </div>
            <div style="margin-bottom:12px;">
                <label>Email</label>
                <input type="text" name="email" id="modal_email" class="input-email" autocomplete="off" style="width:100%;">
            </div>
            <div style="margin-bottom:12px;">
                <label>Status</label>
                <select name="status" id="modal_status" class="input-user" style="width:106%;">
                  <option value="Active">Active</option>
                  <option value="Inactive">Inactive</option>
                </select>
            </div>
            <div id="modalError" style="color:#b00020;text-align:center;margin-bottom:8px;display:none;"></div>
            <div style="display:flex;justify-content:center;gap:16px;margin-top:18px;margin-left:30px;">
                <button type="submit" class="search-btn" id="modalSaveBtn">Save</button>
                <button type="button" class="search-btn" style="background:#aaa;" onclick="closeUserModal()">Cancel</button>
            </div>
        </form>
        <button onclick="closeUserModal()" style="position:absolute;top:8px;right:12px;font-size:22px;background:none;border:none;cursor:pointer;">&times;</button>
    </div>
</div>

<div class="table-container" style="margin-bottom:0;">
    <table id="userTable" class="user-table">
        <colgroup>
            <col style="width: 220px;">
            <col style="width: 120px;">
            <col style="width: auto;">
            <col style="width: 120px;">
        </colgroup>
        <thead style="background:#fff; color:#111;">
            <tr>
                <th style="border:1px solid #222; background:#fff; color:#111;">User</th>
                <th style="border:1px solid #222; background:#fff; color:#111;">Status</th>
                <th style="border:1px solid #222; background:#fff; color:#111;">Email</th>
                <th style="border:1px solid #222; background:#fff; color:#111;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($usersPage as $user) {
                $username = isset($user['user_name']) ? $user['user_name'] : '';
                $firstname = isset($user['first_name']) ? $user['first_name'] : '';
                $lastname = isset($user['last_name']) ? $user['last_name'] : '';
                $initial = $firstname ? strtoupper($firstname[0]) : ($username ? strtoupper($username[0]) : '?');
                $status = isset($user['status']) ? $user['status'] : 'Active';
                $deletedAt = isset($user['deleted_at']) ? $user['deleted_at'] : null;
                $isDeleted = ($deletedAt !== null && $deletedAt !== '' && $deletedAt !== 'null');
                $isInactive = ($status !== 'Active');
                if ($isDeleted) {
                    $rowStyle = 'background:#f6f8fb;';
                } else {
                    $rowStyle = '';
                }
                echo '<tr style="' . $rowStyle . '">';
                echo '<td style="border:1px solid #222; background:#fff; color:#111;">';
                echo '<div style="display:flex;align-items:center;gap:10px;">';
                echo '<span class="avatar-container" style="position:relative;display:inline-block;width:36px;height:36px;">';
                echo '<span class="avatar avatar-xs avatar-rounded" style="background:#e6f4ff;width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;font-weight:bold;font-size:16px;">' . $initial . '</span>';
                if ($status === 'Active') {
                    echo '<span class="status-dot"><span style="display:block;width:11px;height:11px;background:#22c55e;border-radius:50%;border:3px solid #fff;aspect-ratio:1/1;"></span></span>';
                } else {
                        echo '<span class="status-dot"><span style="display:block;width:11px;height:11px;background:#b1b1b1;border-radius:50%;border:3px solid #fff;aspect-ratio:1/1;"></span></span>'; 
                }
                echo '</span>';
                echo '<span style="font-weight:500;">' . htmlspecialchars($firstname . ' ' . $lastname . ' ' . $username) . '</span>';
                echo '</div>';
                echo '</td>';
                echo '<td style="border:1px solid #222; background:#fff; color:#111;">';
                if ($status === 'Active') {
                    echo '<span class="badge-status badge-status-active">Active</span>';
                } else if ($isDeleted) {
                    echo '<span class="badge-status badge-status-inactive" style="background:#f6f8fb;color:#888;">Inactive</span>';
                } else if ($isInactive) {
                    echo '<span class="badge-status badge-status-inactive" style="background:#f6f8fb;color:#888;font-weight:600;">Inactive</span>';
                }
                echo '</td>';
                echo '<td style="border:1px solid #222; background:#fff; color:#111;">' . htmlspecialchars($user['email']) . '</td>';
                echo '<td style="border:1px solid #222; background:#fff; color:#111;white-space:nowrap;">';
                if ($isDeleted) {
                    $editOnClick = 'openUserModal(' . htmlspecialchars(json_encode($user), ENT_QUOTES, 'UTF-8') . ', true);return false;';
                    echo '<a href="#" class="text-info fs-14 lh-1 tooltip-hover" data-tooltip="View (Deleted)" style="display:inline-block;vertical-align:middle;margin-right:6px;pointer-events:auto;" onclick="' . $editOnClick . '"><i class="ri-eye-line" style="font-size:22px;"></i></a> ';
                } else {
                    $editOnClick = 'openUserModal(' . htmlspecialchars(json_encode($user), ENT_QUOTES, 'UTF-8') . ');return false;';
                    echo '<a href="#" class="text-info fs-14 lh-1 tooltip-hover" data-tooltip="Edit" style="display:inline-block;vertical-align:middle;margin-right:6px;" onclick="' . $editOnClick . '"><i class="ri-edit-line" style="font-size:22px;"></i></a> ';
                    $qs = $_GET;
                    $qs['delete_id'] = $user['id'];
                    $delUrl = strtok($_SERVER['REQUEST_URI'], '?') . '?' . http_build_query($qs);
                    echo '<a href="#" class="text-danger fs-14 lh-1 tooltip-hover" data-tooltip="Delete" style="display:inline-block;vertical-align:middle;" onclick="showDeleteConfirm(\'' . htmlspecialchars($delUrl, ENT_QUOTES, 'UTF-8') . '\');return false;"><i class="ri-delete-bin-5-line" style="font-size:22px;"></i></a>';
                }
                echo '</td>';
                echo '</tr>';
            }
            if (empty($usersPage)) {
                echo '<tr><td colspan="7">No users found</td></tr>';
            }
        ?>
        </tbody>
    </table>
</div>

<div style="margin-top: 16px; width: 100%; max-width: 950px; margin: 10px auto 0 auto;">
    <div class="pagination-container">
        <div class="pagination-bar">
            <?php
            if (isset($totalPages)) {
                $queryStr = '';
                if (isset($_GET['search']) && $_GET['search'] !== '') {
                    $queryStr .= '&search=' . urlencode($_GET['search']);
                }
                if (isset($sort) && $sort === 'desc') {
                    $queryStr .= '&sort=desc';
                }
                if ($totalPages > 1) {
                    echo '<button '.($page <= 1 ? 'disabled class=\'page-btn\'' : 'class=\'page-btn\'').' onclick="window.location.href=\'?page='.($page-1).$queryStr.'\'">Previous</button>';
                    echo '<button '.($page == 1 ? 'class=\'page-btn active\'' : 'class=\'page-btn\'').' onclick="window.location.href=\'?page=1'.$queryStr.'\'">1</button>';
                    if ($page > 2 && $page < $totalPages) echo '<span class="page-ellipsis">...</span>';
                    if ($page != 1 && $page != $totalPages) {
                        echo '<button class="page-btn active">'.$page.'</button>';
                    }
                    if ($page < $totalPages - 1 && $page > 1) echo '<span class="page-ellipsis">...</span>';
                    if ($totalPages > 1) {
                        echo '<button '.($page == $totalPages ? 'class=\'page-btn active\'' : 'class=\'page-btn\'').' onclick="window.location.href=\'?page='.$totalPages.$queryStr.'\'">'.$totalPages.'</button>';
                    }
                    echo '<button '.($page >= $totalPages ? 'disabled class=\'page-btn\'' : 'class=\'page-btn\'').' onclick="window.location.href=\'?page='.($page+1).$queryStr.'\'">Next</button>';
                } else {
                    echo '<button disabled class="page-btn">Previous</button>';
                    echo '<button class="page-btn active">1</button>';
                    echo '<button disabled class="page-btn">Next</button>';
                }
            }
            ?>
        </div>
    </div>
</div>

<script>
function openUserModal(user, viewOnly = false) {
    document.getElementById('userModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = user && user.id ? (viewOnly ? 'View User' : 'Edit User') : 'Add User';
    document.getElementById('modal_user_id').value = user && user.id ? user.id : '';
    document.getElementById('modal_user_name').value = user && user.user_name ? user.user_name : '';
    document.getElementById('modal_first_name').value = user && user.first_name ? user.first_name : '';
    document.getElementById('modal_last_name').value = user && user.last_name ? user.last_name : '';
    document.getElementById('modal_email').value = user && user.email ? user.email : '';
    document.getElementById('modalError').style.display = 'none';
    document.getElementById('modalError').textContent = '';
    document.getElementById('modal_status').value = user && user.status ? user.status : 'Active';

    document.getElementById('modal_user_name').disabled = !!viewOnly;
    document.getElementById('modal_first_name').disabled = !!viewOnly;
    document.getElementById('modal_last_name').disabled = !!viewOnly;
    document.getElementById('modal_email').disabled = !!viewOnly;
    document.getElementById('modal_status').disabled = !!viewOnly;
    document.getElementById('modalSaveBtn').style.display = viewOnly ? 'none' : '';
}
function closeUserModal() {
    document.getElementById('userModal').style.display = 'none';
}
function submitUserModal(e) {
    e.preventDefault();
    var id = document.getElementById('modal_user_id').value;
    var user_name = document.getElementById('modal_user_name').value.trim();
    var first_name = document.getElementById('modal_first_name').value.trim();
    var last_name = document.getElementById('modal_last_name').value.trim();
    var email = document.getElementById('modal_email').value.trim();
    var status = document.getElementById('modal_status').value;
    if (!user_name || !first_name || !last_name || !email) {
        showToast('All fields are required.', 'error');
        return false;
    }

    var emailPattern = /^\S+@\S+\.[\w\d]+$/;
    if (!emailPattern.test(email)) {
        showToast('Invalid email.', 'error');
        document.getElementById('modal_email').focus();
        return false;
    }
    var url = id ? 'http://localhost:8080/users/put/' + id : 'http://localhost:8080/users/add';
    var method = id ? 'PUT' : 'POST';
    var data = JSON.stringify({ user_name, first_name, last_name, email, status });
    fetch(url, {
        method: method,
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: data
    })
    .then(async r => {
        let body = null;
        let text = '';
        try {
            text = await r.text();
            try {
                body = JSON.parse(text);
            } catch (e) {
                body = null;
            }
        } catch (e) {
            body = null;
        }
        return { status: r.status, body, text };
    })
    .then(res => {
        if ((res.status === 201 && !id) || (res.status === 200 && id)) {
            let msg = id ? 'User updated successfully!' : 'User added successfully!';
            const url = new URL(window.location.href);
            url.searchParams.delete('search'); 
            url.searchParams.set('msg', encodeURIComponent(msg));
            window.location.href = url.toString();
        } else {
            var msg = 'Error!';
            if (res.body) {
                if (res.body.error) msg = res.body.error;
                else if (res.body.message) msg = res.body.message;
                else if (res.body.detail) msg = res.body.detail;
            } else if (res.text) {
                msg = res.text;
            }
            if (res.status === 409 && (!res.body && !res.text)) msg = 'Username or email already exists.';
            showToast(msg, 'error');
        }
    })
    .catch(() => showModalError('Network error!'));
    return false;
}
function showModalError(msg) {
    var err = document.getElementById('modalError');
    err.textContent = msg;
    err.style.display = 'block';
}
function showToast(message, type = 'success', timeout = 2000) {
    let toastContainer = document.getElementById('custom-toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'custom-toast-container';
        toastContainer.style.zIndex = 99999;
        document.body.appendChild(toastContainer);
    }
    const toast = document.createElement('div');
    toast.className = 'custom-toast custom-toast-' + type;
    toast.innerHTML = `<span>${message}</span><button class=\"custom-toast-close\" onclick=\"this.parentNode.remove()\">&times;</button>`;
    toastContainer.appendChild(toast);
    setTimeout(() => { toast.remove(); }, timeout);
}

function showDeleteConfirm(delUrl) {
    let confirmModal = document.getElementById('custom-delete-confirm');
    if (!confirmModal) {
        confirmModal = document.createElement('div');
        confirmModal.id = 'custom-delete-confirm';
        confirmModal.style.position = 'fixed';
        confirmModal.style.left = 0;
        confirmModal.style.top = 0;
        confirmModal.style.width = '100vw';
        confirmModal.style.height = '100vh';
        confirmModal.style.background = 'rgba(0,0,0,0.25)';
        confirmModal.style.display = 'flex';
        confirmModal.style.alignItems = 'center';
        confirmModal.style.justifyContent = 'center';
        confirmModal.style.zIndex = 10000;
        confirmModal.innerHTML = `
            <div style="background:#fff;padding:32px 32px 24px 32px;border-radius:10px;box-shadow:0 2px 16px rgba(0,0,0,0.18);min-width:320px;max-width:90vw;text-align:center;position:relative;">
                <div style="font-size:1.2rem;font-weight:500;margin-bottom:18px;">Are you sure you want to delete this user?</div>
                <div style="display:flex;justify-content:center;gap:18px;">
                    <button id="deleteConfirmBtn" class="search-btn" style="background:#d32f2f;color:#fff;min-width:80px;">Delete</button>
                    <button id="deleteCancelBtn" class="search-btn" style="background:#aaa;min-width:80px;">Cancel</button>
                </div>
                <button onclick="document.getElementById('custom-delete-confirm').remove()" style="position:absolute;top:8px;right:12px;font-size:22px;background:none;border:none;cursor:pointer;">&times;</button>
            </div>
        `;
        document.body.appendChild(confirmModal);
    } else {
        confirmModal.style.display = 'flex';
    }
    document.getElementById('deleteConfirmBtn').onclick = function() {
        window.location.href = delUrl;
    };
    document.getElementById('deleteCancelBtn').onclick = function() {
        confirmModal.remove();
    };
}
</script>
</body>

<script>
<?php if (isset($_GET['msg']) && $_GET['msg'] !== ''): ?>
    showToast("<?php echo htmlspecialchars(urldecode($_GET['msg']), ENT_QUOTES); ?>", "success");
<?php endif; ?>
<?php if (!empty($searchError)): ?>
    showToast("<?php echo htmlspecialchars($searchError, ENT_QUOTES); ?>", "error");
    document.addEventListener('DOMContentLoaded', function() {
        var searchInput = document.querySelector('input[name=\'search\']');
        if (searchInput) searchInput.value = '';
    });
<?php endif; ?>
</script>
</html>