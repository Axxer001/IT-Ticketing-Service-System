<?php
session_start();
require_once "../classes/User.php";

$user = new User();
$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $boundGmail = filter_var($_POST['bound_gmail'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];
        $userType = $_POST['user_type'];
        
        // Validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
        
        if (!empty($boundGmail) && !filter_var($boundGmail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid Gmail format");
        }
        
        if (strlen($password) < 6) {
            throw new Exception("Password must be at least 6 characters");
        }
        
        if ($password !== $confirmPassword) {
            throw new Exception("Passwords do not match");
        }
        
        // Prepare additional data
        $additionalData = [];
        
        if ($userType === 'employee') {
            $additionalData = [
                'first_name' => $_POST['first_name'],
                'last_name' => $_POST['last_name'],
                'department_id' => $_POST['department_id'],
                'contact_number' => $_POST['contact_number'] ?? null
            ];
        } elseif ($userType === 'service_provider') {
            $additionalData = [
                'provider_name' => $_POST['provider_name'],
                'specialization' => $_POST['specialization'] ?? null,
                'contact_number' => $_POST['contact_number'] ?? null
            ];
        }
        
        // Create verification request instead of direct registration
        $verificationId = $user->createVerificationRequest($email, $password, $userType, $boundGmail, $additionalData);
        
        if ($verificationId) {
            $success = "Account verification request submitted! An administrator will review your request. You will receive an email notification once your account is approved.";
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$departments = $user->getDepartmentsByCategory();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account - Nexon Ticketing</title>
<link rel="stylesheet" href="../assets/css/theme.css">
<script>
    const PHP_SESSION_THEME = 'light';
</script>
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.register-container {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    max-width: 600px;
    width: 100%;
    padding: 40px;
    animation: slideUp 0.5s ease;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.logo {
    text-align: center;
    margin-bottom: 30px;
}

.logo h1 {
    font-size: 32px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-weight: 800;
    letter-spacing: 2px;
}

.logo p {
    color: #666;
    margin-top: 8px;
}

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
}

.alert-danger {
    background: #fee;
    color: #c33;
    border-left: 4px solid #c33;
}

.alert-success {
    background: #efe;
    color: #3c3;
    border-left: 4px solid #3c3;
}

.form-group {
    margin-bottom: 20px;
}

label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 600;
    font-size: 14px;
}

.required {
    color: #e74c3c;
}

input, select, textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s;
    font-family: inherit;
}

input:focus, select:focus, textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.help-text {
    font-size: 12px;
    color: #666;
    margin-top: 4px;
}

.btn {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s;
    margin-top: 10px;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}

.btn:active {
    transform: translateY(0);
}

.text-center {
    text-align: center;
    margin-top: 20px;
    color: #666;
    font-size: 14px;
}

.text-center a {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
}

.text-center a:hover {
    text-decoration: underline;
}

.hidden {
    display: none;
}

optgroup {
    font-weight: bold;
    font-style: normal;
}

@media (max-width: 600px) {
    .register-container {
        padding: 30px 20px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>

<div class="register-container">
    <div class="logo">
        <h1>NEXON</h1>
        <p>Create Your Account</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="" id="registerForm">
        <!-- User Type Selection -->
        <div class="form-group">
            <label>Account Type <span class="required">*</span></label>
            <select name="user_type" id="userType" required onchange="toggleFields()">
                <option value="">Select Account Type</option>
                <option value="employee">Employee</option>
                <option value="service_provider">Service Provider</option>
            </select>
        </div>

        <!-- Email & Gmail -->
        <div class="form-group">
            <label>Email Address (System Login) <span class="required">*</span></label>
            <input type="email" name="email" placeholder="your.email@nexon.com" required>
            <div class="help-text">This will be your login username</div>
        </div>

        <div class="form-group">
            <label>Gmail Address (For Email Notifications) <span class="required">*</span></label>
            <input type="email" name="bound_gmail" placeholder="your.personal@gmail.com" required>
            <div class="help-text">📧 Verification results and system notifications will be sent here</div>
        </div>

        <!-- Password -->
        <div class="form-row">
            <div class="form-group">
                <label>Password <span class="required">*</span></label>
                <div class="password-wrapper" style="position: relative;">
                    <input type="password" name="password" id="password" placeholder="Minimum 6 characters" required minlength="6">
                    <button type="button" onclick="togglePassword('password')" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 18px; color: #666;">👁️</button>
                </div>
            </div>
            <div class="form-group">
                <label>Confirm Password <span class="required">*</span></label>
                <div class="password-wrapper" style="position: relative;">
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Re-enter password" required>
                    <button type="button" onclick="togglePassword('confirm_password')" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 18px; color: #666;">👁️</button>
                </div>
            </div>
        </div>

        <!-- Employee Fields -->
        <div id="employeeFields" class="hidden">
            <div class="form-row">
                <div class="form-group">
                    <label>First Name <span class="required">*</span></label>
                    <input type="text" name="first_name" id="firstName" placeholder="John">
                </div>
                <div class="form-group">
                    <label>Last Name <span class="required">*</span></label>
                    <input type="text" name="last_name" id="lastName" placeholder="Doe">
                </div>
            </div>

            <div class="form-group">
                <label>Department <span class="required">*</span></label>
                <select name="department_id" id="departmentId">
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $category => $depts): ?>
                        <optgroup label="<?= htmlspecialchars($category) ?>">
                            <?php foreach ($depts as $dept): ?>
                                <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Contact Number</label>
                <input type="tel" name="contact_number" placeholder="+63 912 345 6789">
            </div>
        </div>

        <!-- Service Provider Fields -->
        <div id="providerFields" class="hidden">
            <div class="form-group">
                <label>Provider/Company Name <span class="required">*</span></label>
                <input type="text" name="provider_name" id="providerName" placeholder="Tech Solutions Inc.">
            </div>

            <div class="form-group">
                <label>Specialization</label>
                <input type="text" name="specialization" placeholder="e.g., Hardware, Software, Networking">
            </div>

            <div class="form-group">
                <label>Contact Number</label>
                <input type="tel" name="contact_number" placeholder="+63 912 345 6789">
            </div>
        </div>

        <button type="submit" class="btn">Submit Request</button>
    </form>

    <div class="text-center">
        Already have an account? <a href="login.php">Sign In</a>
    </div>
</div>

<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
    input.setAttribute('type', type);
}

function toggleFields() {
    const userType = document.getElementById('userType').value;
    const employeeFields = document.getElementById('employeeFields');
    const providerFields = document.getElementById('providerFields');
    
    employeeFields.classList.add('hidden');
    providerFields.classList.add('hidden');
    
    document.getElementById('firstName').removeAttribute('required');
    document.getElementById('lastName').removeAttribute('required');
    document.getElementById('departmentId').removeAttribute('required');
    document.getElementById('providerName').removeAttribute('required');
    
    if (userType === 'employee') {
        employeeFields.classList.remove('hidden');
        document.getElementById('firstName').setAttribute('required', 'required');
        document.getElementById('lastName').setAttribute('required', 'required');
        document.getElementById('departmentId').setAttribute('required', 'required');
    } else if (userType === 'service_provider') {
        providerFields.classList.remove('hidden');
        document.getElementById('providerName').setAttribute('required', 'required');
    }
}

document.getElementById('registerForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match!');
    }
});
</script>
<script src="../assets/js/theme.js"></script>
</body>
</html>