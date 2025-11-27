<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password</title>

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
        .auth-card {
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
        .helper-text {
            font-size: 0.85rem;
        }
        @media (max-width: 576px) {
            .auth-card {
                margin: 30px 15px;
                padding: 20px;
            }
        }
    </style>
</head>

<body>

<div class=" auth-card">
    <h3 class="mb-3">Set New Password</h3>
    <p class="text-muted helper-text">Create a strong new password for your account.</p>

    <!-- New Password Form -->
    <form action="../../controllers/AuthController.php?action=reset" method="POST">
        <!-- If you use tokens, include a hidden token field here -->
        <!-- <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"> -->

        <!-- New Password -->
        <div class="mb-3">
            <label class="form-label">New Password</label>
            <input
                type="password"
                name="password"
                class="form-control text-left"
                placeholder="••••••••••"
                required
            >
        </div>

        <!-- Confirm New Password -->
        <div class="mb-3">
            <label class="form-label">Confirm New Password</label>
            <input
                type="password"
                name="confirm_password"
                class="form-control text-left"
                placeholder="••••••••••"
                required
            >
        </div>

        <!-- Submit -->
        <button type="submit" class="btn btn-black-shadow w-100 mt-2">
            Update password
        </button>

        <!-- Back to login -->
        <div class="text-center mt-3">
            <small>Remembered your password?
                <a href="login.php"> Back to login</a>
            </small>
        </div>
    </form>
</div>

<footer class="text-center text-muted mt-4 mb-3 small">
    &copy; <?php echo date('Y'); ?> Task Manager. All rights reserved.
</footer>

</body>
</html>
