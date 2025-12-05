<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts: Rubik for headings, Inter for body -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Rubik:wght@500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            background: #f5f6fa;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        .login-card {
            max-width: 430px;
            margin: 60px auto;
            border-radius: 12px;
            padding: 30px;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Rubik', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        .btn-black-shadow {
            background-color: #000;
            color: #fff;
            border: none;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
        }
        .btn-black-shadow:hover {
            background-color: #111;
            color: #fff;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.3);
        }
        @media (max-width: 576px) {
            .login-card {
                margin: 30px 15px;
                padding: 20px;
            }
        }
    </style>
</head>

<body>

<div class=" login-card">
    <h3 class="text-left font-semibold mb-3">Welcome back!</h3>
    <p class="text-left text-muted"> Wellcome back! Please log in to continue.</p>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
                echo $_SESSION['success']; 
                unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <!-- Login Form -->
    <form action="../../controller/AuthController.php?action=login" method="POST">
        
        <!-- Email -->
        <div class="mb-3">
            <label class="form-label">Email address</label>
            <input 
                type="email" 
                name="email" 
                class="form-control" 
                placeholder="your@email.com"
                required
            >
        </div>

        <!-- Password -->
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input 
                type="password" 
                name="password" 
                class="form-control" 
                placeholder="••••••••••" 
                required
            >
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember_me" id="rememberMe">
                <label class="form-check-label" for="rememberMe">
                    Remember me
                </label>
            </div>
            <a href="forgetPassword.php" class="text-decoration-none small">Forgot password?</a>
        </div>

        <!-- Submit -->
        <button type="submit" class="btn btn-black-shadow w-100 mt-2">
            Login
        </button>

        <!-- Register Link -->
        <div class="text-center mt-3">
            <small>Don't have an account?
                <a href="register.php"> Register</a>
            </small>
        </div>
    </form>
</div>

</body>
</html>
