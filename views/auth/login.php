<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f5f6fa;
        }
        .login-card {
            max-width: 430px;
            margin: 60px auto;
            border-radius: 12px;
            padding: 30px;
        }
    </style>
</head>

<body>

<div class="card shadow login-card">
    <h3 class="text-center mb-3">Sign In</h3>
    <p class="text-center text-muted">Welcome back! Please log in to continue.</p>

    <!-- Login Form -->
    <form action="../../controllers/AuthController.php?action=login" method="POST">
        
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
                placeholder="Enter your password"
                required
            >
        </div>

        <!-- Submit -->
        <button type="submit" class="btn btn-primary w-100 mt-2">
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
