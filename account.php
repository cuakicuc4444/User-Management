<?php
session_start();
$isAdmin = isset($_SESSION['user_email']) && isset($_SESSION['user_rule']) && $_SESSION['user_rule'] === 'admin';
if (!$isAdmin) {
	header('Location: /index.php');
	exit();
}
// Lấy danh sách account từ API chỉ 1 lần
$apiUrl = 'http://localhost:8080/accounts/get';
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
$accounts = json_decode($response, true);
if (!is_array($accounts)) $accounts = [];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchError = '';
// Thông báo lỗi search
$searchError = '';
// Phân trang
$perPage = 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? intval($_GET['page']) : 1;
$total = count($accounts);
$totalPages = max(1, ceil($total / $perPage));
if ($page > $totalPages) $page = $totalPages;
$start = ($page - 1) * $perPage;
$accountsPage = array_slice($accounts, $start, $perPage);
if ($search !== '') {
	$searchLower = mb_strtolower($search, 'UTF-8');
	$filteredAccounts = array_filter($accounts, function($acc) use ($searchLower) {
		$username = isset($acc['user_name']) ? mb_strtolower($acc['user_name'], 'UTF-8') : '';
		$email = isset($acc['email']) ? mb_strtolower($acc['email'], 'UTF-8') : '';
		return strpos($username, $searchLower) !== false || strpos($email, $searchLower) !== false;
	});
	$filteredAccounts = array_values($filteredAccounts);
	if (count($filteredAccounts) === 0) {
		$searchError = 'No matching account found: ' . htmlspecialchars($search);
		// Không thay đổi $accounts, giữ nguyên bảng đầy đủ
	} else {
		$accounts = $filteredAccounts;
		$total = count($accounts);
		$totalPages = max(1, ceil($total / $perPage));
		if ($page > $totalPages) $page = $totalPages;
		$start = ($page - 1) * $perPage;
		$accountsPage = array_slice($accounts, $start, $perPage);
	}
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
	<meta charset="UTF-8">
	<title>Account Management</title>
	<link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
	<link rel="stylesheet" href="/user/style3.css">
</head>
<body>
<!-- Sidebar -->
<div id="sidebar" style="position:fixed;top:0;left:0;height:100vh;width:250px;background:#a78bfa;color:#fff;z-index:10000;box-shadow:2px 0 16px #0002;display:none;flex-direction:column;transition:all 0.2s;">
    <div style="padding:24px 0 12px 28px;font-size:13px;letter-spacing:2px;color:#bfc8e2;font-weight:bold;">Main</div>
    <ul style="list-style:none;padding:0;margin:0;">
        <li style="margin-bottom:6px;">
            <button id="tableMenuBtn" style="width:100%;background:none;border:none;outline:none;color:#fff;display:flex;align-items:center;padding:12px 24px 12px 28px;font-size:17px;font-weight:500;border-radius:24px 0 0 24px;gap:12px;cursor:pointer;transition:background 0.15s;">
                <i class="ri-table-line" style="font-size:20px;"></i>
                Table
                <i id="tableMenuArrow" class="ri-arrow-down-s-line" style="font-size:22px;margin-left:auto;transition:transform 0.2s;"></i>
            </button>
            <ul id="tableSubMenu" style="list-style:none;padding:0 0 0 38px;margin:0;display:none;">
                <li>
                    <a href="/user" style="display:flex;align-items:center;padding:10px 0;color:#fff;text-decoration:none;font-size:16px;gap:10px;">
                        <i class="ri-user-3-line" style="font-size:18px;"></i>
                        User Management
                    </a>
                </li>
                <?php if (isset($_SESSION['user_rule']) && $_SESSION['user_rule'] === 'admin'): ?>
                <li>
                    <a href="/account" style="display:flex;align-items:center;padding:10px 0;color:#fff;text-decoration:none;font-size:16px;gap:10px;">
                        <i class="ri-user-settings-line" style="font-size:18px;"></i>
                        Account Management
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </li>
    </ul>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const menuBtn = document.getElementById('menuBtn');
    const sidebar = document.getElementById('sidebar');
    let sidebarVisible = false;
    if (menuBtn && sidebar) {
        menuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebarVisible = !sidebarVisible;
            sidebar.style.display = sidebarVisible ? 'flex' : 'none';
        });
        document.addEventListener('click', function(e) {
            if (sidebarVisible && !sidebar.contains(e.target) && !menuBtn.contains(e.target)) {
                sidebar.style.display = 'none';
                sidebarVisible = false;
            }
        });
    }
    // Table menu dropdown
    const tableMenuBtn = document.getElementById('tableMenuBtn');
    const tableSubMenu = document.getElementById('tableSubMenu');
    const tableMenuArrow = document.getElementById('tableMenuArrow');
    let tableMenuOpen = false;
    if (tableMenuBtn && tableSubMenu && tableMenuArrow) {
        tableMenuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            tableMenuOpen = !tableMenuOpen;
            tableSubMenu.style.display = tableMenuOpen ? 'block' : 'none';
            tableMenuArrow.style.transform = tableMenuOpen ? 'rotate(180deg)' : 'rotate(0)';
        });
    }
});</script>
<!-- Topbar/Navbar -->
<div class="topbar" style="width:100%;background:#fff;border-bottom:2px solid #e0e0e0;display:flex;align-items:center;justify-content:space-between;padding:10px 32px 10px 18px;box-sizing:border-box;gap:18px;">
    <!-- Menu icon (hamburger) -->
    <div style="display:flex;align-items:center;gap:10px;">
        <button id="menuBtn" style="background:none;border:none;outline:none;cursor:pointer;padding:0 8px 0 0;display:flex;align-items:center;">
            <i class="ri-menu-line" style="font-size:28px;color:#a78bfa;"></i>
        </button>
    </div>
    <!-- Search, Add, Avatar group -->
    <div style="display:flex;align-items:center;gap:16px;">
        <form method="get" class="search-form" style="display:flex;align-items:center;gap:0;">
            <input type="text" name="search" placeholder="Search username or email" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" class="search-input" style="margin-right:8px;min-width:220px;">
            <button type="submit" class="search-btn" style="margin-right:8px;">Search</button>
        </form>
        <button type="button" class="search-btn" style="margin-right:8px;" onclick="openAccountModal(null)">Add</button>
        <div class="avatar-dropdown" style="position:relative;">
            <span id="avatarDropdownBtn" class="avatar avatar-xs avatar-rounded" style="background:#e6f4ff;width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;font-weight:bold;font-size:18px;color:#7b61ff;cursor:pointer;border:2px solid #a78bfa;">
                <?php echo mb_strtoupper(mb_substr($_SESSION['user_name'], 0, 1, 'UTF-8'), 'UTF-8'); ?>
            </span>
            <div id="avatarDropdownMenu" style="display:none;position:absolute;right:0;top:44px;min-width:260px;background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,0.12);padding:20px 0 10px 0;z-index:100;">
                <div style="padding:0 24px 12px 24px;border-bottom:1px solid #f0f0f0;">
                    <div style="font-weight:700;font-size:18px;line-height:1.2;">
                        <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'User'; ?>
                    </div>
                    <div style="font-size:14px;color:#888;">
                        <?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : ''; ?>
                    </div>
                </div>
                <a href="#" style="display:flex;align-items:center;gap:12px;padding:14px 24px 8px 24px;color:#222;text-decoration:none;font-size:15px;">
                    <span style="font-size:18px;"><i class="ri-user-line"></i></span> My Profile
                </a>
                <a href="#" style="display:flex;align-items:center;gap:12px;padding:8px 24px;color:#222;text-decoration:none;font-size:15px;">
                    <span style="font-size:18px;"><i class="ri-mail-line"></i></span> Mail Inbox                </a>
                <a href="#" style="display:flex;align-items:center;gap:12px;padding:8px 24px;color:#222;text-decoration:none;font-size:15px;">
                    <span style="font-size:18px;"><i class="ri-settings-3-line"></i></span> Account Settings
                </a>
                <a href="/sign_in?logout=1" style="display:flex;align-items:center;gap:12px;padding:8px 24px 14px 24px;color:#e44a8b;text-decoration:none;font-size:15px;">
                    <span style="font-size:18px;"><i class="ri-logout-box-r-line"></i></span> Sign Out
                </a>
            </div>
        </div>
    </div>
</div>
<script>
// Sidebar toggle
const menuBtn = document.getElementById('menuBtn');
const sidebar = document.getElementById('sidebar');
let sidebarVisible = false;
menuBtn.addEventListener('click', function() {
    sidebarVisible = !sidebarVisible;
    sidebar.style.display = sidebarVisible ? 'flex' : 'none';
});
// Đóng sidebar khi click ra ngoài (nâng cao)
document.addEventListener('click', function(e) {
    if (sidebarVisible && !sidebar.contains(e.target) && !menuBtn.contains(e.target)) {
        sidebar.style.display = 'none';
        sidebarVisible = false;
    }
});
// Avatar dropdown logic
document.addEventListener('DOMContentLoaded', function() {
    var avatarBtn = document.getElementById('avatarDropdownBtn');
    var dropdown = document.getElementById('avatarDropdownMenu');
    if (avatarBtn && dropdown) {
        avatarBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        });
        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target) && e.target !== avatarBtn) {
                dropdown.style.display = 'none';
            }
        });
    }
});
</script>
<!-- Account Modal -->
<div id="accountModal" class="modal">
	<div class="account-modal-content">
		<h3 id="accountModalTitle" style="font-size:1.5rem;font-weight:bold;margin-bottom:10px;text-align:center;">Account</h3>
		<form id="accountForm" method="post" autocomplete="off" onsubmit="return submitAccountModal(event)">
			<input type="hidden" name="id" id="modal_account_id">
			<label>Username</label>
			<input type="text" name="user_name" id="modal_account_user_name">
			<label>Rule</label>
			<select name="rule" id="modal_account_rule">
				<option value="user">User</option>
				<option value="admin">Admin</option>
			</select>
			<label>Status</label>
			<select name="status" id="modal_account_status">
				<option value="active">Active</option>
				<option value="inactive">Inactive</option>
			</select>
			<label>Email</label>
			<input type="text" name="email" id="modal_account_email">
			<label>Password</label>
			<input type="password" name="password" id="modal_account_password">
			<div id="accountModalError" style="color:#b00020;text-align:center;margin-bottom:8px;display:none;"></div>
			<div id="accountModalSuccess" style="color:#097a0f;text-align:center;margin-bottom:8px;display:none;"></div>
			<div class="modal-btns">
				<button type="submit" class="search-btn" id="modalAccountSaveBtn">Save</button>
				<button type="button" class="search-btn" style="background:#aaa;" onclick="closeAccountModal()">Cancel</button>
			</div>
		</form>
		<button onclick="closeAccountModal()" style="position:absolute;top:8px;right:12px;font-size:22px;background:none;border:none;cursor:pointer;">&times;</button>
	</div>
</div>
<div class="main-center">
	<h2 style="text-align:center; color:#222; margin:32px auto 12px auto; font-size:2.8rem; font-family:'Georgia',serif; font-weight:bold;">
		<a href="<?php echo strtok($_SERVER['REQUEST_URI'], '?'); ?>" style="color:inherit;text-decoration:none;cursor:pointer;">
			Account Management
		</a>
	</h2>
	<div class="user-stats-bar" style="display:flex;justify-content:space-between;align-items:center;width:100%;max-width:950px;margin:0 auto 10px auto;padding-left:0;padding-right:0;gap:10px;">
		<div class="user-stats">
			<?php echo 'Total accounts: ' . count($accounts); ?>
		</div>

	</div>
		<div class="table-container" style="margin-bottom:0;">
			<table id="userTable" class="user-table">
			<colgroup>
				<col style="width: 220px;">
				<col style="width: 120px;">
				<col style="width: 120px;">
				<col style="width: 200px;">
				<col style="width: 260px;">
				<col style="width: 100px;">
			</colgroup>
			<thead>
				<tr>
					<th>Username</th>
					<th>Rule</th>
					<th>Status</th>
					<th>Password</th>
					<th>Email</th>
					<th class="action-col">Action</th>
				</tr>
			</thead>
			<tbody>
			<?php
			// Đã lấy và lọc $accounts ở đầu file, không cần lấy lại ở đây
				$i = 1;
				foreach ($accountsPage as $acc) {
				$username = htmlspecialchars($acc['user_name']);
				$rule = htmlspecialchars($acc['rule']);
				$status = htmlspecialchars($acc['status']);
				$password = !empty($acc['password']) ? str_repeat('*', 8) : '';
				$email = htmlspecialchars($acc['email']);
				$rowData = htmlspecialchars(json_encode($acc), ENT_QUOTES, 'UTF-8');
				$isDeleted = isset($acc['deleted_at']) && $acc['deleted_at'] !== null;
				echo '<tr>';
				// Avatar + username
				echo '<td>';
				echo '<div style="display:flex;align-items:center;gap:10px;">';
				echo '<span class="avatar-container" style="position:relative;display:inline-block;width:36px;height:36px;">';
				$firstChar = mb_strtoupper(mb_substr($username, 0, 1, 'UTF-8'), 'UTF-8');
				echo '<span class="avatar avatar-xs avatar-rounded" style="background:#e6f4ff;width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;font-weight:bold;font-size:16px;color:#7b61ff;">' . $firstChar . '</span>';
				$dotColor = (strtolower($status) === 'active') ? '#22c55e' : '#bdbdbd';
				echo '<span class="status-dot"><span style="display:block;width:11px;height:11px;background:' . $dotColor . ';border-radius:50%;border:3px solid #fff;aspect-ratio:1/1;"></span></span>';
				echo '</span>';
				echo '<span style="font-weight:500;">' . $username . '</span>';
				echo '</div>';
				echo '</td>';
				echo '<td>' . $rule . '</td>';
				echo '<td>';
				if (strtolower($status) === 'active') {
					echo '<span class="badge-status badge-status-active">Active</span>';
				} else {
					echo '<span class="badge-status badge-status-inactive">Inactive</span>';
				}
				echo '</td>';
				echo '<td>' . $password . '</td>';
				echo '<td>' . $email . '</td>';
				echo '<td>';
				if (strtolower($status) === 'active') {
					echo '<a href="#" class="text-info fs-14 lh-1 tooltip-hover" data-tooltip="Edit" onclick="openAccountModal(' . $rowData . ');return false;"><i class="ri-edit-line" style="font-size:22px;"></i></a> ';
					echo '<a href="#" class="text-danger fs-14 lh-1 tooltip-hover" data-tooltip="Delete" onclick="showAccountDeleteConfirm(' . $acc['id'] . ');return false;"><i class="ri-delete-bin-5-line" style="font-size:22px;"></i></a>';
				} else if ($isDeleted) {
					echo '<a href="#" class="text-info fs-14 lh-1 tooltip-hover" data-tooltip="View (Deleted)" style="pointer-events:auto;" onclick="openAccountModal(' . $rowData . ', true);return false;"><i class="ri-eye-line" style="font-size:22px;"></i></a>';
				} else {
					echo '<a href="#" class="text-info fs-14 lh-1 tooltip-hover" data-tooltip="Edit" onclick="openAccountModal(' . $rowData . ');return false;"><i class="ri-edit-line" style="font-size:22px;"></i></a> ';
					echo '<a href="#" class="text-danger fs-14 lh-1 tooltip-hover" data-tooltip="Delete" onclick="showAccountDeleteConfirm(' . $acc['id'] . ');return false;"><i class="ri-delete-bin-5-line" style="font-size:22px;"></i></a>';
				}
				echo '</td>';
				echo '</tr>';
				$i++;
			}
				if (empty($accountsPage)) {
					echo '<tr><td colspan="6">No accounts found</td></tr>';
				}
			?>
			</tbody>
			</table>
		</div>
		<!-- Pagination bar -->
		<div class="pagination-container" style="width:100%;max-width:950px;margin:10px auto 0 auto;box-sizing:border-box;text-align:right;">
			<div class="pagination-bar" style="display:inline-flex;align-items:center;gap:8px;">
			<?php
			$queryStr = '';
			if ($search !== '') $queryStr .= '&search=' . urlencode($search);
			if ($totalPages <= 1) {
				echo '<button class="page-btn active">1</button>';
			} else {
				echo '<button '.($page <= 1 ? 'disabled class="page-btn"' : 'class="page-btn"').' onclick="window.location.href=\'?page='.($page-1).$queryStr.'\'">Previous</button>';
				echo '<button '.($page == 1 ? 'class="page-btn active"' : 'class="page-btn"').' onclick="window.location.href=\'?page=1'.$queryStr.'\'">1</button>';
				if ($totalPages > 2) {
					if ($page > 2) {
						echo '<span class="page-ellipsis">...</span>';
					}
					if ($page != 1 && $page != $totalPages) {
						echo '<button class="page-btn active">'.$page.'</button>';
					}
					if ($page < $totalPages - 1) {
						echo '<span class="page-ellipsis">...</span>';
					}
					echo '<button '.($page == $totalPages ? 'class="page-btn active"' : 'class="page-btn"').' onclick="window.location.href=\'?page='.$totalPages.$queryStr.'\'">'.$totalPages.'</button>';
				} elseif ($totalPages == 2) {
					echo '<button '.($page == 2 ? 'class="page-btn active"' : 'class="page-btn"').' onclick="window.location.href=\'?page=2'.$queryStr.'\'">2</button>';
				}
				echo '<button '.($page >= $totalPages ? 'disabled class="page-btn"' : 'class="page-btn"').' onclick="window.location.href=\'?page='.($page+1).$queryStr.'\'">Next</button>';
			}
			?>
			</div>
		</div>
</div>
<script>
function openAccountModal(account, viewOnly = false) {
   document.getElementById('accountModal').style.display = 'flex';
   if (account && account.id) {
	   document.getElementById('accountModalTitle').textContent = viewOnly ? 'View Account' : 'Edit Account';
	   document.getElementById('modal_account_id').value = account.id;
	   document.getElementById('modal_account_user_name').value = account.user_name;
	   document.getElementById('modal_account_rule').value = account.rule;
	   document.getElementById('modal_account_status').value = account.status;
	   document.getElementById('modal_account_email').value = account.email;
	   document.getElementById('modal_account_password').value = '';
   } else {
	   document.getElementById('accountModalTitle').textContent = 'Add Account';
	   document.getElementById('modal_account_id').value = '';
	   document.getElementById('modal_account_user_name').value = '';
	   document.getElementById('modal_account_rule').value = 'user';
	   document.getElementById('modal_account_status').value = 'active';
	   document.getElementById('modal_account_email').value = '';
	   document.getElementById('modal_account_password').value = '';
   }
   // Disable all input fields if viewOnly
   document.getElementById('modal_account_user_name').disabled = !!viewOnly;
   document.getElementById('modal_account_rule').disabled = !!viewOnly;
   document.getElementById('modal_account_status').disabled = !!viewOnly;
   document.getElementById('modal_account_email').disabled = !!viewOnly;
   document.getElementById('modal_account_password').disabled = !!viewOnly;
   document.getElementById('modalAccountSaveBtn').style.display = viewOnly ? 'none' : '';
   document.getElementById('accountModalError').style.display = 'none';
   document.getElementById('accountModalError').textContent = '';
}
function closeAccountModal() {
	document.getElementById('accountModal').style.display = 'none';
}
function submitAccountModal(e) {
	e.preventDefault();
	var id = document.getElementById('modal_account_id').value;
	var user_name = document.getElementById('modal_account_user_name').value.trim();
	var rule = document.getElementById('modal_account_rule').value;
	var status = document.getElementById('modal_account_status').value;
	var email = document.getElementById('modal_account_email').value.trim();
	var password = document.getElementById('modal_account_password').value;
	if (!user_name || !email || (!id && !password.trim())) {
		showToast('Please fill in all information.', 'error');
		return false;
	}
	var emailPattern = /^\S+@\S+\.[\w\d]+$/;
	if (!emailPattern.test(email)) {
		showToast('Invalid email format.', 'error');
		document.getElementById('modal_account_email').focus();
		return false;
	}
	var url = id ? 'http://localhost:8080/accounts/put/' + id : 'http://localhost:8080/accounts/add';
	var method = id ? 'PUT' : 'POST';
	var payload = { user_name, rule, status, email };
	if (!id || password.trim()) payload.password = password;
	var data = JSON.stringify(payload);
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
			try { body = JSON.parse(text); } catch (e) { body = null; }
		} catch (e) { body = null; }
		return { status: r.status, body, text };
	})
	.then(res => {
		if ((res.status === 201 && !id) || (res.status === 200 && id)) {
			showToast(id ? 'Account update successful!' : 'Account creation successful!', 'success');
			setTimeout(() => window.location.reload(), 1200);
		} else {
			var msg = 'An error occurred!';
			if (res.body) {
				if (res.body.error) msg = res.body.error;
				else if (res.body.message) msg = res.body.message;
				else if (res.body.detail) msg = res.body.detail;
			} else if (res.text) {
				msg = res.text;
			}
			if (msg.toLowerCase().includes('duplicate') || msg.toLowerCase().includes('exists')) {
				msg = 'Username or email already exists.';
			}
			showToast(msg, 'error');
		}
	})
	.catch(() => showToast('Network or server error!', 'error'));
	return false;
}
// Toast notification system (copied from index.php)
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
function showAccountDeleteConfirm(id) {
   if (!id) return;
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
			   <div style="font-size:1.2rem;font-weight:500;margin-bottom:18px;">Are you sure you want to delete this account?</div>
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
	   fetch('http://localhost:8080/accounts/delete/' + id, { method: 'DELETE' })
		   .then(r => r.json())
		   .then(res => {
			   showToast('Account deleted successfully!', 'success');
			   document.getElementById('custom-delete-confirm').remove();
			   setTimeout(() => window.location.reload(), 1200);
		   })
		   .catch(() => {
			   showToast('Delete failed!', 'error');
			   document.getElementById('custom-delete-confirm').remove();
		   });
   };
   document.getElementById('deleteCancelBtn').onclick = function() {
	   confirmModal.remove();
   };
}
</script>
</body>
<script>
<?php if (!empty($searchError)): ?>
	showToast("<?php echo htmlspecialchars($searchError, ENT_QUOTES); ?>", "error");
	document.addEventListener('DOMContentLoaded', function() {
		var searchInput = document.querySelector('input[name=\'search\']');
		if (searchInput) searchInput.value = '';
	});
<?php endif; ?>
</script>
</html>