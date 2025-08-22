<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
  <link rel="stylesheet" href="style2.css">
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Remixicon CDN -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body style="background:#f8f9fd;">
    <div class="login-container">
      <div class="login-box">
        <div style="text-align:center;font-size:2rem;font-weight:600;margin-bottom:24px;color:#000000;">Sign Up</div>
        <form id="signup-form" autocomplete="off">
          <label for="signup-email">
            Email Address <sup class="fs-12 text-danger">*</sup>
          </label>
          <input id="signup-email" name="email" placeholder="Enter your email address" type="email" required>
          <label for="signup-password">
            Password <sup class="fs-12 text-danger">*</sup>
          </label>
          <div style="display: flex; align-items: center; gap: 8px;">
            <input id="signup-password" name="password" placeholder="Enter your password" type="password" required style="flex:1;">
            <button class="show-password-button" type="button" onclick="createpassword('signup-password', this)" tabindex="-1" style="position:static; margin-left:-8px; margin-top:25px;">
              <span><i class="ri-eye-off-line align-middle"></i></span>
            </button>
          </div>
          <label for="create-confirmpassword">
            Confirm Password <sup class="fs-12 text-danger">*</sup>
          </label>
          <div style="display: flex; align-items: center; gap: 8px;">
            <input id="create-confirmpassword" name="confirm_password" placeholder="Re-enter your password" type="password" required style="flex:1;">
            <button class="show-password-button" type="button" onclick="createpassword('create-confirmpassword', this)" tabindex="-1" style="position:static; margin-left:-8px; margin-top:25px;">
              <span><i class="ri-eye-off-line align-middle"></i></span>
            </button>
          </div>
          <div class="form-check" style="margin-bottom:10px;">
            <input class="form-check-input" type="checkbox" id="termsCheck" required>
            <label class="form-check-label text-muted fw-normal fs-11" for="termsCheck" style="font-size:15px;">
              By creating an account, you agree to our 
              <a href="terms-conditions.html" class="text-success"><u>Terms &amp; Conditions</u></a> 
              and 
              <a href="javascript:void(0);" class="text-success"><u>Privacy Policy</u></a>
            </label>
          </div>
          <button type="submit" class="btn-primary" id="signup-btn" style="font-size:20px;font-weight:600;border-radius:10px;padding:13px 0;">
            <span style="display:inline-flex;align-items:center;"> Create Account</span>
          </button>
        </form>
        <div class="text-center">
          <p class="text-muted mt-3 mb-0" style="font-size:16px;">
            Already have an account? 
            <a class="text-primary fw-medium text-decoration-underline" href="sign_in.php">
              Sign In
            </a>
          </p>
        </div>
      </div>
    </div>
<script>
// Password show/hide logic
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
</script>
</body>
</html>
