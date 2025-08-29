<?php

?>
<!DOCTYPE html>
<html lang="en">
<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Forgot Password</title>
		<link rel="stylesheet" href="/user/style1.css">
		<link rel="stylesheet" href="/user/style.css">
		<link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
		<style>
			.forgot-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f8f9fd; }
			.forgot-box { background: #fff; border-radius: 14px; box-shadow: 0 4px 24px #0001; padding: 36px 32px 32px 32px; max-width: 410px; width: 100%; }
			.forgot-title { text-align: center; font-size: 2rem; font-weight: 600; color: #7b61ff; margin-bottom: 24px; }
			.step { display: none; }
			.step.active { display: block; }
			.code-inputs { display: flex; gap: 12px; justify-content: center; margin: 24px 0 18px 0; }
			.code-inputs input { width: 48px; height: 48px; text-align: center; font-size: 1.5rem; border: 1.5px solid #bdbdbd; border-radius: 8px; background: #fafaff; transition: border 0.2s; }
			.code-inputs input:focus { border: 1.5px solid #a78bfa; outline: none; background: #fff; }
			.verify-btn, .save-btn { width: 100%; padding: 13px 0; background: #a78bfa; color: #fff; border: none; border-radius: 8px; font-size: 18px; font-weight: 600; cursor: pointer; margin-top: 18px; transition: background 0.2s; }
			.verify-btn:disabled, .save-btn:disabled { background: #d1c4e9; cursor: not-allowed; }
			.resend-row { display: flex; align-items: center; gap: 6px; margin-bottom: 10px; }
			.resend-link { color: #7b61ff; text-decoration: underline; cursor: pointer; font-weight: 500; font-size: 15px; }
			.msg { text-align: center; margin-bottom: 12px; font-size: 15px; color: #e53e3e; display: none; }
			.msg.success { color: #22c55e; }
			.input-group { margin-bottom: 18px; }
			.input-label { font-weight: 500; margin-bottom: 6px; display: block; }
			.input-row { position: relative; }
			.input-row input { width: 85%; padding: 12px 44px 12px 12px; border: 1.5px solid #bdbdbd; border-radius: 8px; font-size: 16px; background: #fafaff; }
			.input-row input:focus { border: 1.5px solid #a78bfa; outline: none; background: #fff; }
			.toggle-pass { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #bbb; font-size: 20px; }
			.back-link { display: block; text-align: center; margin-top: 18px; color: #7b61ff; text-decoration: underline; font-size: 15px; cursor: pointer; }
		</style>
</head>
<body>
	<div class="forgot-container">
		<div class="forgot-box">
			<div class="forgot-title">Forgot Password</div>
					<!-- Step 1: Enter Email -->
					<div class="step active" id="step-email">
						<form id="emailForm" autocomplete="off" onsubmit="return false;">
							<div class="input-group">
								<label class="input-label" for="forgotEmail">Enter your email <span style="color:#e53e3e;">*</span></label>
								<div class="input-row">
									<input type="text" id="forgotEmail" placeholder="Enter your email" autocomplete="off">
								</div>
							</div>
							<button class="verify-btn" id="sendCodeBtn">Send Verification Code</button>
							<div class="msg" id="msgEmail"></div>
						</form>
					</div>
					<!-- Step 2: Enter Verification Code -->
					<div class="step" id="step-code">
						<form id="codeForm" autocomplete="off" onsubmit="return false;">
							<div style="font-size:1.1rem;font-weight:500;margin-bottom:10px;">Enter Verification Code :</div>
							<div class="code-inputs">
								<input type="text" maxlength="1" inputmode="numeric" class="code-digit" autocomplete="off">
								<input type="text" maxlength="1" inputmode="numeric" class="code-digit" autocomplete="off">
								<input type="text" maxlength="1" inputmode="numeric" class="code-digit" autocomplete="off">
								<input type="text" maxlength="1" inputmode="numeric" class="code-digit" autocomplete="off">
							</div>
							<div class="resend-row">
								<input type="checkbox" id="notReceived" style="accent-color:#a78bfa;">
								<label for="notReceived" style="font-size:15px;color:#444;">Didn't receive a code?</label>
								<span class="resend-link" id="resendCode">Resend</span>
							</div>
							<button class="verify-btn" id="verifyBtn">Verify</button>
						</form>
					</div>
					<!-- Step 3: Reset Password -->
					<div class="step" id="step-pass">
						<form id="passForm" autocomplete="off" onsubmit="return false;">
							<div class="input-group">
								<label class="input-label" for="newPass">New Password <span style="color:#e53e3e;">*</span></label>
								<div class="input-row">
									<input type="password" id="newPass" placeholder="Enter new password" autocomplete="off">
									<button type="button" class="toggle-pass" onclick="togglePass('newPass', this)"><i class="ri-eye-off-line"></i></button>
								</div>
							</div>
							<div class="input-group">
								<label class="input-label" for="confirmPass">Confirm Password <span style="color:#e53e3e;">*</span></label>
								<div class="input-row">
									<input type="password" id="confirmPass" placeholder="Confirm new password" autocomplete="off">
									<button type="button" class="toggle-pass" onclick="togglePass('confirmPass', this)"><i class="ri-eye-off-line"></i></button>
								</div>
							</div>
							<button class="save-btn" id="saveBtn">Save Password</button>
							<div class="msg" id="msgPass"></div>
						</form>
						<a href="#" class="back-link" id="backHome">Back to Sign In</a>
					</div>
<script>
// Toast notification nổi góc phải
function showToast(message, type = 'error', timeout = 2000) {
	let toastContainer = document.getElementById('custom-toast-container');
	if (!toastContainer) {
		toastContainer = document.createElement('div');
		toastContainer.id = 'custom-toast-container';
		toastContainer.style.zIndex = 99999;
		toastContainer.style.position = 'fixed';
		toastContainer.style.top = '24px';
		toastContainer.style.right = '24px';
		document.body.appendChild(toastContainer);
	}
	const toast = document.createElement('div');
	toast.className = 'custom-toast custom-toast-' + type;
	toast.innerHTML = `<span>${message}</span><button class="custom-toast-close" onclick="this.parentNode.remove()">&times;</button>`;
	toastContainer.appendChild(toast);
	setTimeout(() => { toast.remove(); }, timeout);
}

const stepEmail = document.getElementById('step-email');
const stepCode = document.getElementById('step-code');
const stepPass = document.getElementById('step-pass');
const forgotEmail = document.getElementById('forgotEmail');
const sendCodeBtn = document.getElementById('sendCodeBtn');
const codeDigits = document.querySelectorAll('.code-digit');
const verifyBtn = document.getElementById('verifyBtn');
const resendCode = document.getElementById('resendCode');
const saveBtn = document.getElementById('saveBtn');
const backHome = document.getElementById('backHome');

let sentCode = '';
let emailForReset = '';

sendCodeBtn.onclick = async function(e) {
	e.preventDefault();
	const email = forgotEmail.value.trim();
	if (!email) {
		showToast('Please enter your email.', 'error');
		forgotEmail.focus();
		return;
	}
	const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
	if (!emailRegex.test(email)) {
		showToast('Invalid email format.', 'error');
		forgotEmail.focus();
		return;
	}
	try {
		const res = await fetch('http://localhost:8080/accounts/get');
		const accounts = await res.json();
		const found = Array.isArray(accounts) && accounts.some(acc => acc.email && acc.email.toLowerCase() === email.toLowerCase());
		if (!found) {
			showToast('Email does not exist in the system!', 'error');
			return;
		}
		stepEmail.classList.remove('active');
		stepCode.classList.add('active');
		codeDigits[0].focus();
		emailForReset = email;
		sentCode = Math.floor(1000 + Math.random() * 9000).toString();
		// Gửi code về email ở đây (tích hợp thực tế nếu muốn)
		showToast('Verification code sent to your email! (Demo: ' + sentCode + ')', 'success');
	} catch (err) {
		showToast('Server error, please try again!', 'error');
	}
};

// Auto focus next code input
codeDigits.forEach((input, idx) => {
	input.addEventListener('input', function(e) {
		if (this.value.length === 1 && idx < codeDigits.length - 1) {
			codeDigits[idx + 1].focus();
		}
		if (this.value.length === 0 && idx > 0 && e.inputType === 'deleteContentBackward') {
			codeDigits[idx - 1].focus();
		}
	});
	input.addEventListener('keydown', function(e) {
		if (e.key === 'Backspace' && this.value === '' && idx > 0) {
			codeDigits[idx - 1].focus();
		}
	});
});

resendCode.onclick = function() {
	if (!emailForReset) return;
	sentCode = Math.floor(1000 + Math.random() * 9000).toString();
	showToast('Code resent! (Demo: ' + sentCode + ')', 'success');
	codeDigits.forEach(inp => inp.value = '');
	codeDigits[0].focus();
};

verifyBtn.onclick = function(e) {
	e.preventDefault();
	let code = '';
	codeDigits.forEach(inp => code += inp.value);
	if (code.length < 4) {
		showToast('Please enter the 4-digit code.', 'error');
		return;
	}
	if (code !== sentCode) {
		showToast('Incorrect code!', 'error');
		return;
	}
	stepCode.classList.remove('active');
	stepPass.classList.add('active');
	document.getElementById('newPass').focus();
};

// Toggle password visibility
window.togglePass = function(id, btn) {
	const input = document.getElementById(id);
	const icon = btn.querySelector('i');
	if (input.type === 'password') {
		input.type = 'text';
		icon.classList.remove('ri-eye-off-line');
		icon.classList.add('ri-eye-line');
		btn.style.color = '#7b61ff';
	} else {
		input.type = 'password';
		icon.classList.remove('ri-eye-line');
		icon.classList.add('ri-eye-off-line');
		btn.style.color = '#bbb';
	}
};

saveBtn.onclick = async function(e) {
	e.preventDefault();
	const newPass = document.getElementById('newPass').value;
	const confirmPass = document.getElementById('confirmPass').value;
	if (!newPass || !confirmPass) {
		showToast('Please fill in all password fields.', 'error');
		return;
	}
	if (newPass.length < 6) {
		showToast('Password must be at least 6 characters.', 'error');
		return;
	}
	if (newPass !== confirmPass) {
		showToast('Passwords do not match!', 'error');
		return;
	}
	// Gửi API đổi mật khẩu (PUT)
	try {
		const res = await fetch('http://localhost:8080/accounts/get');
		const accounts = await res.json();
		const acc = Array.isArray(accounts) && accounts.find(acc => acc.email && acc.email.toLowerCase() === emailForReset.toLowerCase());
		if (!acc) {
			showToast('Account not found!', 'error');
			return;
		}
		const putRes = await fetch('http://localhost:8080/accounts/put/' + acc.id, {
			method: 'PUT',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ password: newPass })
		});
		if (putRes.ok) {
			showToast('Password reset successful!', 'success');
			setTimeout(() => { window.location.href = '/sign_in'; }, 1800);
		} else {
			showToast('Failed to reset password!', 'error');
		}
	} catch (err) {
		showToast('Server error, please try again!', 'error');
	}
};

backHome.onclick = function(e) {
	e.preventDefault();
	window.location.href = '/sign_in';
};
</script>
</body>
</html>