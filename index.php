<?php
session_start();
require_once 'includes/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $action = $_POST['action'] ?? 'login';

    if ($action === 'register') {
        $name = $_POST['name'] ?? 'Student';
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $db->prepare("INSERT INTO users (email, password, name) VALUES (?, ?, ?)");
            $stmt->execute([$email, $hashed, $name]);
            $_SESSION['user_id'] = $db->lastInsertId();
            $_SESSION['user_name'] = $name;
            header("Location: dashboard.php");
            exit;
        } catch (PDOException $e) {
            $error = "Registration failed: Email might already exist.";
        }
    } else {
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid credentials. Please try again.";
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<div style="display: flex; justify-content: center; align-items: center; min-height: 70vh;">
    <div class="card" style="width: 100%; max-width: 450px; padding: 40px; border-top: 5px solid #004085;">
        <div style="text-align: center; margin-bottom: 30px;">
            <img src="assets/images/logo.png" alt="MU Logo" style="height: 60px; margin-bottom: 15px;">
            <h1 style="margin: 0; font-size: 1.8rem; color: #001a33;">MU-Classroom</h1>
            <p style="color: #64748b; margin-top: 8px;">Your academic journey starts here.</p>
        </div>

        <?php if ($error): ?>
            <div style="background: #fff1f2; color: #9d174d; padding: 12px 15px; border-radius: 10px; border: 1px solid #fda4af; margin-bottom: 20px; font-size: 0.9rem; text-align: center;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div id="login-form">
            <h2 style="font-size: 1.4rem; margin-bottom: 20px; color: #1e293b;">Login</h2>
            <form method="POST">
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <input type="email" name="email" placeholder="Email Address" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <input type="hidden" name="action" value="login">
                    <button type="submit" style="margin-top: 10px; padding: 14px;">Sign In &rarr;</button>
                </div>
            </form>
            <p style="text-align: center; margin-top: 25px; color: #64748b; font-size: 0.95rem;">
                New student? <a href="#" onclick="showRegister()" style="color: #004085; font-weight: 700; text-decoration: none;">Create an account</a>
            </p>
        </div>

        <div id="register-form" style="display: none;">
            <h2 style="font-size: 1.4rem; margin-bottom: 20px; color: #1e293b;">Register</h2>
            <form method="POST">
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <input type="text" name="name" placeholder="Full Name" required>
                    <input type="email" name="email" placeholder="Email Address" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <input type="hidden" name="action" value="register">
                    <button type="submit" style="margin-top: 10px; padding: 14px; background-color: #004085; color: white; border-radius: 12px; border: none; font-weight: 700; cursor: pointer;">Create Account &rarr;</button>
                </div>
            </form>
            <p style="text-align: center; margin-top: 25px; color: #64748b; font-size: 0.95rem;">
                Already have an account? <a href="#" onclick="showLogin()" style="color: #004085; font-weight: 700; text-decoration: none;">Login here</a>
            </p>
        </div>
    </div>
</div>

<script>
function showRegister() {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    loginForm.style.display = 'none';
    registerForm.style.display = 'block';
}
function showLogin() {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    loginForm.style.display = 'block';
    registerForm.style.display = 'none';
}
</script>

<?php include 'includes/footer.php'; ?>
