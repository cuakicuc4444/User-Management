<?php
session_start();
$loginError = '';
$emailError = '';
$usernameOrEmailError = '';
$passwordError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$username_or_email = isset($_POST['username_or_email']) ? trim($_POST['username_or_email']) : '';
	$password = isset($_POST['password']) ? $_POST['password'] : '';
	if ($username_or_email === '') {
		$usernameOrEmailError = 'Username or email is required!';
	} else {
		$apiUrl = 'http://localhost:8080/accounts/get';
		$ch = curl_init($apiUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);
		$accounts = json_decode($response, true);
		$found = false;
		$userExists = false;
		$inactiveAccount = false;
		$password_md5 = md5($password);
		if (is_array($accounts)) {
			foreach ($accounts as $acc) {
				if ((isset($acc['email']) && strtolower($acc['email']) === strtolower($username_or_email)) ||
					(isset($acc['user_name']) && strtolower($acc['user_name']) === strtolower($username_or_email))) {
					$userExists = true;
					if (isset($acc['password']) && $acc['password'] === $password_md5) {
						if (!isset($acc['status']) || strtolower($acc['status']) !== 'active') {
							$inactiveAccount = true;
							break;
						}
						$_SESSION['user_email'] = $acc['email'];
						$_SESSION['user_name'] = $acc['user_name'];
						$_SESSION['account_id'] = $acc['id'];
						if (isset($acc['rule'])) {
							$_SESSION['user_rule'] = $acc['rule'];
						} else {
							$_SESSION['user_rule'] = '';
						}
						$found = true;
						break;
					}
				}
			}
		}
		if ($found) {
			header('Location: /user');
			exit();
		} elseif ($inactiveAccount) {
			$passwordError = 'Your account has been locked.';
		} elseif (!$userExists) {
			$usernameOrEmailError = 'Username or email does not exist!';
		} else {
			$passwordError = 'Incorrect password!';
		}
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Sign In</title>
	<link rel="stylesheet" href="/user/style1.css">
	<link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body>
	<div class="login-container">
		   <div class="login-box">
			   <div style="text-align:center;font-size:2rem;font-weight:600;margin-bottom:24px;color:#000000;">Sign In</div>
		   <form method="post" action="/sign_in" autocomplete="off">
			   <label for="signin-username-or-email">
				   Username or Email <sup class="fs-12 text-danger">*</sup>
			   </label>
			   <input id="signin-username-or-email" name="username_or_email" placeholder="Enter your username or email" type="text" value="<?php echo isset($_POST['username_or_email']) ? htmlspecialchars($_POST['username_or_email']) : ''; ?>">
				<label for="signin-password">
					Password <sup class="fs-12 text-danger">*</sup>
				</label>
				<div style="position:relative;">
					<input id="signin-password" name="password" placeholder="Enter your password" type="password" autocomplete="off" style="width:95%;padding-right:1px;">
					<button class="show-password-button" type="button" onclick="createpassword('signin-password', this)" tabindex="-1" style="position:absolute; right:0px; top:35%; transform:translateY(-50%); background:transparent; border:none; padding:0; cursor:pointer;">
						<span><i class="ri-eye-off-line align-middle"></i></span>
					</button>
				</div>
			   <div class="form-check" style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
				   <div style="display:flex;align-items:center;">
					   
				   </div>
				   <a href="#" class="text-success">Forgot Password?</a>
			   </div>
			   <button type="submit" class="btn-primary" id="signin-btn">
				   <span style="display:inline-flex;align-items:center;"><path d="M12 4v16m8-8H4" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg> Sign In</span>
			   </button>
		   </form>
			<div class="text-center">
				<p class="text-muted mt-3 mb-0">
					Don't have an account?
					<a class="text-primary" href="/sign_up">Sign Up</a>
				</p>
			</div>
		</div>
	</div>
<script>
	function createpassword(inputId, btn) {
		const input = document.getElementById(inputId);
		const icon = btn.querySelector('i');
		if (input.type === 'password') {
			input.type = 'text';
			icon.classList.remove('ri-eye-off-line');
			icon.classList.add('ri-eye-line');
		} else {
			input.type = 'password';
			icon.classList.remove('ri-eye-line');
			icon.classList.add('ri-eye-off-line');
		}
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

	   document.querySelector('form').addEventListener('submit', function(e) {
		   var usernameOrEmail = document.getElementById('signin-username-or-email').value.trim();
		   var password = document.getElementById('signin-password').value;
		   if (!usernameOrEmail) {
			   showToast('Please enter your username or email.', 'error');
			   document.getElementById('signin-username-or-email').focus();
			   e.preventDefault();
			   return false;
		   }
		   if (!password) {
			   showToast('Please enter your password.', 'error');
			   document.getElementById('signin-password').focus();
			   e.preventDefault();
			   return false;
		   }
	   });

	   <?php if (!empty($usernameOrEmailError)): ?>
		   showToast("<?php echo $usernameOrEmailError; ?>", 'error');
	   <?php endif; ?>
	   <?php if (!empty($emailError)): ?>
		   showToast("<?php echo $emailError; ?>", 'error');
	   <?php endif; ?>
	   <?php if (!empty($passwordError)): ?>
		   showToast("<?php echo $passwordError; ?>", 'error');
	   <?php endif; ?>
	   </script>
	</body>
	</html>
