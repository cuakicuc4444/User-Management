<?php
session_start();
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
    if ($conn->error) {
        echo $conn->error;
    }
}
$conn->close();

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
    header('Location: http://localhost/sign_in');
    exit();
}
if (!isset($_SESSION['user_email'])) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
    header('Location: http://localhost/sign_in');
    exit();
}
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $deleteId = intval($_GET['delete_id']);
    if ($deleteId <= 0) {
        $deleteMsg = 'Invalid user ID!';
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
            $deleteMsg = 'Delete user failed!';
        }
    }
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
    <script>
        if (!<?php echo json_encode(isset($_SESSION['user_email'])); ?>) {
            window.location.replace('http://localhost/sign_in');
        }
    </script>
    <!-- Collapsible Sidebar Layout -->
    <div class="layout-flex">
        <div id="sidebar" class="sidebar expanded">
            <div class="sidebar-logo" style="display:flex;align-items:center;justify-content:center;height:35px;padding:12px 0;">
                <img src="/user/image/logo-icon.png" alt="Logo Icon" class="sidebar-logo-icon" style="height:31px;width:32px;display:none;object-fit:contain;" onerror="this.style.display='none'">
                <img src="/user/image/logo.png" alt="Logo Full" class="sidebar-logo-full" style="height:31px;width:auto;max-width:80%;object-fit:contain;display:inline;" onerror="this.style.display='none'">
            </div>
            <div class="sidebar-divider" style="height:0;border-bottom:1px solid #e0e0e0;opacity:0.3;margin:0 0 0 1px;">
            </div>
            <?php
            $currentPage = basename($_SERVER['PHP_SELF']);
            ?>
            <ul style="list-style:none;padding:0;margin:0;">
                <li
                    class="sidebar-menu-item<?php echo ($currentPage === 'index.php' || $currentPage === 'user.php') ? ' active' : ''; ?>">
                    <a href="/user"
                        class="<?php echo ($currentPage === 'index.php' || $currentPage === 'user.php') ? 'active' : ''; ?>"
                        style="display:flex;align-items:center;color:inherit;text-decoration:none;font-size:14px;font-weight:500;gap:12px;">
                        <i class="ri-user-3-line" style="font-size:18px;"></i>
                        <span class="sidebar-label">User</span>
                    </a>
                </li>
                <?php if (isset($_SESSION['user_rule']) && $_SESSION['user_rule'] === 'admin'): ?>
                    <li class="sidebar-menu-item<?php echo ($currentPage === 'account.php') ? ' active' : ''; ?>">
                        <a href="/account" class="<?php echo ($currentPage === 'account.php') ? 'active' : ''; ?>"
                            style="display:flex;align-items:center;color:inherit;text-decoration:none;font-size:14px;font-weight:500;gap:12px;">
                            <i class="ri-user-settings-line" style="font-size:18px;"></i>
                            <span class="sidebar-label">Account</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item<?php echo ($currentPage === 'app.php') ? ' active' : ''; ?>">
                        <a href="/app" class="<?php echo ($currentPage === 'app.php') ? 'active' : ''; ?>"
                            style="display:flex;align-items:center;color:inherit;text-decoration:none;font-size:14px;font-weight:500;gap:12px;">
                            <i class="ri-apps-2-line" style="font-size:18px;"></i>
                            <span class="sidebar-label">App</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="main-content" id="mainContent">
            <script>

                document.addEventListener('DOMContentLoaded', function () {
                    const sidebar = document.getElementById('sidebar');
                    const menuBtn = document.getElementById('menuBtn');
                    sidebar.classList.remove('collapsed');
                    sidebar.classList.add('expanded');
                    document.querySelectorAll('.sidebar-label').forEach(function (label) {
                        label.style.display = '';
                    });
                    var menuIcon = document.getElementById('sidebarMenuIcon');
                    function updateMenuIcon() {
                        if (sidebar.classList.contains('collapsed')) {
                            menuIcon.classList.remove('ri-menu-line');
                            menuIcon.classList.add('ri-close-line');
                        } else {
                            menuIcon.classList.remove('ri-close-line');
                            menuIcon.classList.add('ri-menu-line');
                        }
                    }
                    menuBtn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        if (sidebar.classList.contains('collapsed')) {
                            sidebar.classList.remove('collapsed');
                            sidebar.classList.add('expanded');
                            document.querySelectorAll('.sidebar-label').forEach(function (label) {
                                label.style.display = '';
                            });
                        } else {
                            sidebar.classList.remove('expanded');
                            sidebar.classList.add('collapsed');
                            document.querySelectorAll('.sidebar-label').forEach(function (label) {
                                label.style.display = 'none';
                            });
                        }
                        updateMenuIcon();
                    });
                    updateMenuIcon();
                    const tableMenuBtn = document.getElementById('tableMenuBtn');
                    const tableSubMenu = document.getElementById('tableSubMenu');
                    const tableMenuArrow = document.getElementById('tableMenuArrow');
                    let tableMenuOpen = false;
                    if (tableMenuBtn && tableSubMenu && tableMenuArrow) {
                        tableMenuBtn.addEventListener('click', function (e) {
                            e.stopPropagation();
                            tableMenuOpen = !tableMenuOpen;
                            tableSubMenu.style.display = tableMenuOpen ? 'block' : 'none';
                            tableMenuArrow.style.transform = tableMenuOpen ? 'rotate(180deg)' : 'rotate(0)';
                        });
                    }
                    // Avatar dropdown logic
                    var avatarBtn = document.getElementById('avatarDropdownBtn');
                    var dropdown = document.getElementById('avatarDropdownMenu');
                    if (avatarBtn && dropdown) {
                        avatarBtn.addEventListener('click', function (e) {
                            e.stopPropagation();
                            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
                        });
                        document.addEventListener('click', function (e) {
                            if (!dropdown.contains(e.target) && e.target !== avatarBtn) {
                                dropdown.style.display = 'none';
                            }
                        });
                    }
                });
            </script>
            <!-- Topbar/Navbar -->
            <div class="topbar"
                style="width:100%;background:#fff;border-bottom:1px solid #e0e0e0;display:flex;align-items:center;justify-content:space-between;padding:10px 32px 10px 18px;box-sizing:border-box;">
                <!-- Menu icon (hamburger) -->
                <div style="display:flex;align-items:center;gap:10px;">
                    <button id="menuBtn"
                        style="background:none;border:none;outline:none;cursor:pointer;padding:0 8px 0 0;display:flex;align-items:center;">
                        <i id="sidebarMenuIcon" class="ri-menu-line" style="font-size:28px;color:#38487c;"></i>
                    </button>
                    <form method="get" class="topbar-search-form"
                        style="position:relative;display:flex;align-items:center;margin-left:12px;color: #bfc8e2;opacity: 0.7;">
                        <input type="text" name="quicksearch" placeholder="Search for Results..."
                            style="border:none;outline:none;padding:10px 38px 10px 16px;border-radius:24px;font-size:15px;background:#fafbff;color:#38487c;box-shadow:0 0 0 0.5px #e0e7ef;min-width:220px;transition:box-shadow 0.2s;">
                        <button type="submit"
                            style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;outline:none;cursor:pointer;padding:0;">
                            <i class="ri-search-line" style="font-size:20px;color:#bfc8e2;"></i>
                        </button>
                    </form>
                </div>
                <!-- Search, Add, Avatar group -->
                <div style="display:flex;align-items:center;gap:16px;">

                    <div class="avatar-dropdown" style="position:relative;">
                        <span id="avatarDropdownBtn" class="avatar avatar-xs avatar-rounded"
                            style="background:#e6f4ff;width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;font-weight:bold;font-size:18px;color:#7b61ff;cursor:pointer;border:2px solid #8b7eff;">
                            <?php echo mb_strtoupper(mb_substr($_SESSION['user_name'], 0, 1, 'UTF-8'), 'UTF-8'); ?>
                        </span>
                        <div id="avatarDropdownMenu"
                            style="display:none;position:absolute;right:0;top:44px;min-width:260px;background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,0.12);padding:20px 0 10px 0;z-index:100;">
                            <div style="padding:0 24px 12px 24px;border-bottom:1px solid #f0f0f0;">
                                <div style="font-weight:700;font-size:18px;line-height:1.2;">
                                    <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'User'; ?>
                                </div>
                                <div style="font-size:14px;color:#888;">
                                    <?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : ''; ?>
                                </div>
                            </div>
                            <a href="#"
                                style="display:flex;align-items:center;gap:12px;padding:14px 24px 8px 24px;color:#222;text-decoration:none;font-size:15px;">
                                <span style="font-size:18px;"><i class="ri-user-line"></i></span> My Profile
                            </a>
                            <a href="#"
                                style="display:flex;align-items:center;gap:12px;padding:8px 24px;color:#222;text-decoration:none;font-size:15px;">
                                <span style="font-size:18px;"><i class="ri-mail-line"></i></span> Mail Inbox

                            </a>
                            <a href="/account_setting"
                                style="display:flex;align-items:center;gap:12px;padding:8px 24px;color:#222;text-decoration:none;font-size:15px;">
                                <span style="font-size:18px;"><i class="ri-settings-3-line"></i></span> Account Settings
                            </a>
                            <a href="?logout=1"
                                style="display:flex;align-items:center;gap:12px;padding:8px 24px 14px 24px;color:#e44a8b;text-decoration:none;font-size:15px;">
                                <span style="font-size:18px;"><i class="ri-logout-box-r-line"></i></span> Sign Out
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="main-center">
                <?php
                $addErrorArr = [];
                $apiUrl = 'http://localhost:8080/users/get';
                $ch = curl_init($apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                curl_close($ch);
                $users = json_decode($response, true);
                if (!is_array($users))
                    $users = [];
                $searchError = '';
                $filteredUsers = $users;
                // ...đã xử lý xóa user ở đầu file...
                if (isset($deleteMsg)) {
                    echo '<div id="deleteMsg" class="delete-msg">' . htmlspecialchars($deleteMsg) . '</div>';
                }
                if ($users && is_array($users)) {
                    if (isset($_GET['search']) && $_GET['search'] !== '') {
                        $search = mb_strtolower(trim($_GET['search']));
                        $filteredUsers = array_filter($users, function ($u) use ($search) {
                            return (mb_strpos(mb_strtolower($u['user_name']), $search) !== false)
                                || (mb_strpos(mb_strtolower($u['email']), $search) !== false);
                        });
                        if (count($filteredUsers) === 0) {
                            $searchError = 'No matching user found: ' . htmlspecialchars($search);
                            $filteredUsers = $users;
                        }
                    }
                    $sort = isset($_GET['sort']) && $_GET['sort'] === 'desc' ? 'desc' : 'asc';
                    usort($filteredUsers, function ($a, $b) use ($sort) {
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

                <div class="user-table-card">
                  <h2
                      style="text-align:left; color:#222; margin-left:0; margin-right:auto; margin-top:3px; margin-bottom:13px; font-size:1.5rem; font-family:'Segoe UI', Poppins, sans-serif; font-weight:bold; max-width:1200px; width:100%;">
                      User Management
                  </h2>
                  <div class="user-table-divider"></div>
                  <?php
                  if (session_status() === PHP_SESSION_NONE)
                      session_start();
                  if (!isset($_SESSION['initial_total_users'])) {
                      $_SESSION['initial_total_users'] = count($users);
                  }
                  $initialTotalUsers = $_SESSION['initial_total_users'];
                  $currentTotalUsers = count($users);
                  $isFullTable = (!isset($_GET['search']) || $_GET['search'] === '') && (!isset($_GET['searchType']) || $_GET['searchType'] === 'id');
                  $totalUsers = $currentTotalUsers;
                  ?>
                  <div class="user-stats-bar">
                      <div class="user-stats">
                          <?php echo 'Total users: ' . $totalUsers; ?>
                      </div>
                      <div style="display:flex;align-items:center;gap:16px; margin-left:auto;">
                          <form method="get" class="search-form" style="display:flex;align-items:center;gap:0;">
                              <input type="text" name="search" placeholder="Search username or email"
                                  value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                                  class="search-input" style="margin-right:8px;min-width:220px;">
                              <button type="submit" class="search-btn" style="margin-right:8px;">Search</button>
                          </form>
                          <button type="button" class="search-btn" style="margin-right:0;"
                              onclick="openUserModal(null)">Add</button>
                      </div>
                  </div>

                  <div id="userModal" class="modal"
                      style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.25);align-items:center;justify-content:center;">
                      <div class="user-modal-content">
                          <h3 id="modalTitle"
                              style="font-size:1.5rem;font-weight:bold;margin-bottom:10px;text-align:center;margin-left:30px;">
                              User</h3>
                          <form id="userForm" method="post" autocomplete="off" onsubmit="return submitUserModal(event)">
                              <input type="hidden" name="user_id" id="modal_user_id">
                              <div style="margin-bottom:12px;">
                                  <label>Username</label>
                                  <input type="text" name="user_name" id="modal_user_name" class="input-user"
                                      autocomplete="off" style="width:100%;">
                              </div>
                              <div style="margin-bottom:12px;">
                                  <label>First Name</label>
                                  <input type="text" name="first_name" id="modal_first_name" class="input-user"
                                      autocomplete="off" style="width:100%;">
                              </div>
                              <div style="margin-bottom:12px;">
                                  <label>Last Name</label>
                                  <input type="text" name="last_name" id="modal_last_name" class="input-user"
                                      autocomplete="off" style="width:100%;">
                              </div>
                              <div style="margin-bottom:12px;">
                                  <label>Email</label>
                                  <input type="text" name="email" id="modal_email" class="input-email" autocomplete="off"
                                      style="width:100%;">
                              </div>
                              <div style="margin-bottom:12px;">
                                  <label>Status</label>
                                  <select name="status" id="modal_status" class="input-user" style="width:106%;">
                                      <option value="Active">Active</option>
                                      <option value="Inactive">Inactive</option>
                                  </select>
                              </div>
                              <div id="modalError"
                                  style="color:#b00020;text-align:center;margin-bottom:8px;display:none;"></div>
                              <div style="display:flex;justify-content:center;gap:16px;margin-top:18px;margin-left:30px;">
                                  <button type="submit" class="search-btn" id="modalSaveBtn">Save</button>
                                  <button type="button" class="search-btn" style="background:#aaa;"
                                      onclick="closeUserModal()">Cancel</button>
                              </div>
                          </form>
                          <button onclick="closeUserModal()"
                              style="position:absolute;top:8px;right:12px;font-size:22px;background:none;border:none;cursor:pointer;">&times;</button>
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
                                  <th>User</th>
                                  <th>Status</th>
                                  <th>Email</th>
                                  <th>Action</th>
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
                                  echo '<td>';
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
                                  echo '<td>';
                                  if ($status === 'Active') {
                                      echo '<span class="badge-status badge-status-active">Active</span>';
                                  } else if ($isDeleted) {
                                      echo '<span class="badge-status badge-status-inactive" style="background:#f6f8fb;color:#232323;">Inactive</span>';
                                  } else if ($isInactive) {
                                      echo '<span class="badge-status badge-status-inactive" style="background:#f6f8fb;color:#232323;font-weight:600;">Inactive</span>';
                                  }
                                  echo '</td>';
                                  echo '<td>' . htmlspecialchars($user['email']) . '</td>';
                                  echo '<td style="white-space:nowrap;">';
                                  if ($isDeleted) {
                                      $editOnClick = 'openUserModal(' . htmlspecialchars(json_encode($user), ENT_QUOTES, 'UTF-8') . ', true);return false;';
                                      echo '<a href="#" class="text-info fs-14 lh-1 tooltip-hover" data-tooltip="View (Deleted)" style="display:inline-block;vertical-align:middle;margin-right:6px;pointer-events:auto;text-decoration:none;" onclick="' . $editOnClick . '"><i class="ri-eye-line" style="font-size:22px;"></i></a> ';
                                  } else {
                                      $editOnClick = 'openUserModal(' . htmlspecialchars(json_encode($user), ENT_QUOTES, 'UTF-8') . ');return false;';
                                      echo '<a href="#" class="text-info fs-14 lh-1 tooltip-hover" data-tooltip="Edit" style="display:inline-block;vertical-align:middle;margin-right:6px;text-decoration:none;" onclick="' . $editOnClick . '"><i class="ri-edit-line" style="font-size:22px;"></i></a> ';
                                      $qs = $_GET;
                                      $qs['delete_id'] = $user['id'];
                                      $delUrl = strtok($_SERVER['REQUEST_URI'], '?') . '?' . http_build_query($qs);
                                      echo '<a href="#" class="text-danger fs-14 lh-1 tooltip-hover" data-tooltip="Delete" style="display:inline-block;vertical-align:middle;text-decoration:none;" onclick="showDeleteConfirm(\'' . htmlspecialchars($delUrl, ENT_QUOTES, 'UTF-8') . '\');return false;"><i class="ri-delete-bin-5-line" style="font-size:22px;"></i></a>';
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

                  <div style="margin-top: 16px; width: 100%;  margin: -1px auto 0 auto;">
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
                                      echo '<button ' . ($page <= 1 ? 'disabled class=\'page-btn\'' : 'class=\'page-btn\'') . ' onclick="window.location.href=\'?page=' . ($page - 1) . $queryStr . '\'">Previous</button>';
                                      echo '<button ' . ($page == 1 ? 'class=\'page-btn active\'' : 'class=\'page-btn\'') . ' onclick="window.location.href=\'?page=1' . $queryStr . '\'">1</button>';
                                      if ($page > 2 && $page < $totalPages)
                                          echo '<span class="page-ellipsis">...</span>';
                                      if ($page != 1 && $page != $totalPages) {
                                          echo '<button class="page-btn active">' . $page . '</button>';
                                      }
                                      if ($page < $totalPages - 1 && $page > 1)
                                          echo '<span class="page-ellipsis">...</span>';
                                      if ($totalPages > 1) {
                                          echo '<button ' . ($page == $totalPages ? 'class=\'page-btn active\'' : 'class=\'page-btn\'') . ' onclick="window.location.href=\'?page=' . $totalPages . $queryStr . '\'">' . $totalPages . '</button>';
                                      }
                                      echo '<button ' . ($page >= $totalPages ? 'disabled class=\'page-btn\'' : 'class=\'page-btn\'') . ' onclick="window.location.href=\'?page=' . ($page + 1) . $queryStr . '\'">Next</button>';
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
                        document.getElementById('deleteConfirmBtn').onclick = function () {
                            window.location.href = delUrl;
                        };
                        document.getElementById('deleteCancelBtn').onclick = function () {
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
        document.addEventListener('DOMContentLoaded', function () {
            var searchInput = document.querySelector('input[name=\'search\']');
            if (searchInput) searchInput.value = '';
        });
    <?php endif; ?>
</script>

</html>