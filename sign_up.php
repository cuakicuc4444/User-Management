<?php
session_start();
$signupError = '';
$usernameError = '';
$emailError = '';
$passwordError = '';
?>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = isset($_POST['username']) ? trim($_POST['username']) : '';
  $email = isset($_POST['email']) ? trim($_POST['email']) : '';
  $password = isset($_POST['password']) ? $_POST['password'] : '';
  $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
  if ($username === '' || $email === '' || $password === '' || $confirm_password === '') {
    $signupError = 'Please fill in all required fields.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $emailError = 'Invalid email format.';
  } elseif ($password !== $confirm_password) {
    $passwordError = 'Passwords do not match.';
  } else {
    $apiUrl = 'http://localhost:8080/accounts/get';
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $accounts = json_decode($response, true);
    $userExists = false;
    $emailExists = false;
    if (is_array($accounts)) {
      foreach ($accounts as $acc) {
        if (isset($acc['user_name']) && strtolower($acc['user_name']) === strtolower($username)) {
          $userExists = true;
        }
        if (isset($acc['email']) && strtolower($acc['email']) === strtolower($email)) {
          $emailExists = true;
        }
      }
    }
    if ($userExists) {
      $usernameError = 'Username already exists.';
    } elseif ($emailExists) {
      $emailError = 'Email already exists.';
    } else {
      $apiAdd = 'http://localhost:8080/accounts/add';
      $data = json_encode([
        'user_name' => $username,
        'email' => $email,
        'password' => $password
      ]);
      $ch = curl_init($apiAdd);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
      $result = curl_exec($ch);
      curl_close($ch);
  $signupSuccess = true;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
  <!-- <link rel="stylesheet" href="style2.css"> -->
  	<link rel="stylesheet" href="/user/style2.css">
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/user/style.css">
  <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <!-- Remixicon CDN -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body style="background:#f8f9fd;">
    <div class="login-container">
      <div class="login-box">
        <div style="text-align:center;font-size:2rem;font-weight:600;margin-bottom:24px;color:#000000;">Sign Up</div>
  <form id="signup-form" method="post" autocomplete="off" onsubmit="return false;">
            <label for="signup-username">
                 Username <sup class="fs-12 text-danger">*</sup>
          </label>
          <input id="signup-username" name="username" placeholder="Enter your username" type="text" autocomplete="off">
          <label for="signup-email">
            Email Address <sup class="fs-12 text-danger">*</sup>
          </label>
          <input id="signup-email" name="email" placeholder="Enter your email address" type="text" autocomplete="off">
          <label for="signup-password">
            Password <sup class="fs-12 text-danger">*</sup>
          </label>
          <div style="position:relative;">
            <input id="signup-password" name="password" placeholder="Enter your password" type="password" autocomplete="off" style="width:100%;padding-right:40px;">
            <button class="show-password-button" type="button" onclick="createpassword('signup-password', this)" tabindex="-1" style="position:absolute; right:0px; top:35%; transform:translateY(-50%); background:transparent; border:none; padding:0; cursor:pointer;">
              <span><i class="ri-eye-off-line align-middle"></i></span>
            </button>
          </div>
          <label for="create-confirmpassword">
            Confirm Password <sup class="fs-12 text-danger">*</sup>
          </label>
          <div style="position:relative;">
            <input id="create-confirmpassword" name="confirm_password" placeholder="Re-enter your password" type="password" autocomplete="off" style="width:100%;padding-right:40px;">
            <button class="show-password-button" type="button" onclick="createpassword('create-confirmpassword', this)" tabindex="-1" style="position:absolute; right:0px; top:35%; transform:translateY(-50%); background:transparent; border:none; padding:0; cursor:pointer;">
              <span><i class="ri-eye-off-line align-middle"></i></span>
            </button>
          </div>
          <div class="form-check" style="margin-bottom:10px;">
            <input class="form-check-input" type="checkbox" id="termsCheck">
            <label class="form-check-label text-muted fw-normal fs-11" for="termsCheck" style="font-size:15px;">
              By creating an account, you agree to our 
              <a href="https://www.google.com/intl/en/policies/terms/" class="text-success"><u>Terms &amp; Conditions</u></a> 
              and 
              <a href="https://policies.google.com/privacy" class="text-success"><u>Privacy Policy</u></a>
            </label>
          </div>
          <button type="submit" class="btn-primary" id="signup-btn" style="font-size:20px;font-weight:600;border-radius:10px;padding:13px 0;">
            <span style="display:inline-flex;align-items:center;"> Create Account</span>
          </button>
        </form>
        <div class="text-center">
          <p class="text-muted mt-3 mb-0" style="font-size:16px;">
            Already have an account? 
            <a class="text-primary fw-medium text-decoration-underline" href="/sign_in">
              Sign In
            </a>
          </p>
        </div>
      </div>
    </div>
<script>
<?php if (isset($signupSuccess) && $signupSuccess): ?>
  showToast('Sign up successful! You can now sign in.', 'success');
  document.getElementById('signup-username').value = '';
  document.getElementById('signup-email').value = '';
  document.getElementById('signup-password').value = '';
  document.getElementById('create-confirmpassword').value = '';
  document.getElementById('termsCheck').checked = false;
<?php endif; ?>
if (window.location.search.includes('signup=success')) {
  showToast('Sign up successful! Please sign in.', 'success');
}
<?php if (!empty($signupError)): ?>
  showToast("<?php echo $signupError; ?>", 'error');
<?php endif; ?>
<?php if (!empty($usernameError)): ?>
  showToast("<?php echo $usernameError; ?>", 'error');
<?php endif; ?>
<?php if (!empty($emailError)): ?>
  showToast("<?php echo $emailError; ?>", 'error');
<?php endif; ?>
<?php if (!empty($passwordError)): ?>
  showToast("<?php echo $passwordError; ?>", 'error');
<?php endif; ?>
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

document.getElementById('signup-btn').addEventListener('click', async function(e) {
  const username = document.getElementById('signup-username').value.trim();
  const email = document.getElementById('signup-email').value.trim();
  const password = document.getElementById('signup-password').value;
  const confirmPassword = document.getElementById('create-confirmpassword').value;
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  if (!username || !email || !password || !confirmPassword) {
    showToast('Please fill in all required fields.', 'error');
    return false;
  }
  if (!document.getElementById('termsCheck').checked) {
    showToast('You must agree to the Terms & Conditions and Privacy Policy.', 'error');
    return false;
  }
  if (!emailRegex.test(email)) {
    showToast('Invalid email format.', 'error');
    document.getElementById('signup-email').focus();
    return false;
  }
  if (password !== confirmPassword) {
    showToast('Passwords do not match.', 'error');
    document.getElementById('create-confirmpassword').focus();
    return false;
  }
  let isDuplicate = false;
  try {
    const res = await fetch('http://localhost:8080/accounts/get');
    const accounts = await res.json();
    if (Array.isArray(accounts)) {
      for (const acc of accounts) {
        if (acc.user_name && acc.user_name.toLowerCase() === username.toLowerCase()) {
          showToast('Username already exists.', 'error');
          document.getElementById('signup-username').focus();
          isDuplicate = true;
          break;
        }
        if (acc.email && acc.email.toLowerCase() === email.toLowerCase()) {
          showToast('Email already exists.', 'error');
          document.getElementById('signup-email').focus();
          isDuplicate = true;
          break;
        }
      }
    }
  } catch (err) {
  }
  if (isDuplicate) {
    return false;
  }
  document.getElementById('signup-form').submit();
});
</script>
</body>
</html>
