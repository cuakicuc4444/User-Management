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
			<div style="display:flex;align-items:center;gap:10px;">
				<form method="get" class="search-form" style="margin-bottom:0;">
					<div class="search-input-wrapper">
						<input type="text" name="search" placeholder="Search username or email..." class="search-input">
					</div>
					<button type="submit" class="search-btn">Search</button>
				</form>
				<button type="button" class="search-btn add-btn-center" onclick="openAccountModal(null)">Add</button>
				<a href="/user" class="search-btn" style="font-weight:bold;text-decoration:underline;">User Management</a>
				<a href="/sign_in?logout=1" class="search-btn">Log out</a>
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
				// Lấy danh sách account từ API
				$apiUrl = 'http://localhost:8080/accounts/get';
				$ch = curl_init($apiUrl);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$response = curl_exec($ch);
				curl_close($ch);
				$accounts = json_decode($response, true);
				if (!is_array($accounts)) $accounts = [];
				   foreach ($accounts as $acc) {
					$username = htmlspecialchars($acc['user_name']);
					$rule = htmlspecialchars($acc['rule']);
					$status = htmlspecialchars($acc['status']);
					$password = !empty($acc['password']) ? str_repeat('*', 8) : '';
					$email = htmlspecialchars($acc['email']);
					   $rowData = htmlspecialchars(json_encode($acc), ENT_QUOTES, 'UTF-8');
					   $isDeleted = isset($acc['deleted_at']) && $acc['deleted_at'] !== null;
					   echo '<tr>';
					   echo '<td>' . $username . '</td>';
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
				}
				if (empty($accounts)) {
					echo '<tr><td colspan="6">No accounts found</td></tr>';
				}
				?>
				</tbody>
				</table>
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
</html>