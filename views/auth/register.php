<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>

    <!-- Bootstrap -->
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
        .register-card {
            max-width: 450px;
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
            .register-card {
                margin: 30px 15px;
                padding: 20px;
            }
        }
    </style>
</head>

<body>

<div class=" register-card">

    <h3 class="text-left mb-3">Create an Account</h3>
    <p class="text-left text-muted">Join us and start managing your tasks efficiently.</p>

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

    <!-- Register Form -->
    <form action="../../controller/AuthController.php?action=register" method="POST">

        <!-- Full Name -->
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input 
                type="text" 
                name="full_name" 
                class="form-control" 
                placeholder="Your full name"
                required
            >
        </div>

        <!-- Email -->
        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input 
                type="email" 
                name="email" 
                class="form-control" 
                placeholder="name@example.com"
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

        <!-- Confirm Password -->
        <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <input 
                type="password" 
                name="confirm_password" 
                class="form-control"
                placeholder="••••••••••"
                required
            >
        </div>

        <!-- Submit -->
        <button type="submit" class="btn btn-black-shadow w-100 mt-2">
            Register
        </button>

        <!-- Login Link -->
        <div class="text-center mt-3">
            <small>Already have an account?
                <a href="login.php"> Login</a>
            </small>
        </div>

    </form>
</div>

<footer class="text-center text-muted mt-4 mb-3 small">
    &copy; <?php echo date('Y'); ?> Task Manager. All rights reserved.
</footer>

</body>
</html>
