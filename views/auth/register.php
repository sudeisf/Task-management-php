<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f5f6fa;
        }
        .register-card {
            max-width: 450px;
            margin: 60px auto;
            border-radius: 12px;
            padding: 30px;
        }
    </style>
</head>

<body>

<div class="card shadow register-card">

    <h3 class="text-center mb-3">Create an Account</h3>
    <p class="text-center text-muted">Join us and start managing your tasks efficiently.</p>

    <!-- Register Form -->
    <form action="../../controllers/AuthController.php?action=register" method="POST">

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
                placeholder="Create a strong password"
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
                placeholder="Repeat your password"
                required
            >
        </div>

        <!-- Submit -->
        <button type="submit" class="btn btn-success w-100 mt-2">
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

</body>
</html>
