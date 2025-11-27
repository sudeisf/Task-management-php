<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts: Rubik for headings, Inter for body -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Rubik:wght@500;600;700&display=swap" rel="stylesheet">

    <style>
        html, body {
            height: 100%;
        }
        body {
            background: #f5f6fa;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            display: flex;
            flex-direction: column;
        }
        .auth-card {
            max-width: 600px;
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
        footer {
            margin-top: auto;
        }
    </style>
</head>

<body>

<div class=" auth-card">
    <h3 class="mb-3">OTP Verification</h3>
    <p class="text-muted helper-text">Enter the one-time password we sent to your email.</p>

    <!-- OTP Form -->
    <form action="../../controllers/AuthController.php?action=verify_otp" method="POST">
        <!-- If you use tokens or email identifiers, include hidden fields here -->

        <!-- OTP Code -->
        <div class="mb-3">
            <label class="form-label">OTP Code</label>
            <input
                type="text"
                name="otp"
                maxlength="6"
                class="form-control text-center"
                placeholder="••••••"
                required
            >
        </div>

        <!-- Submit -->
        <button type="submit" class="btn btn-black-shadow w-100 mt-2">
            Verify OTP
        </button>

        <!-- Resend link -->
        <div class="text-center mt-3">
            <small>Didn't receive the code?
                <a href="#"> Resend OTP</a>
            </small>
        </div>
    </form>
</div>



</body>
</html>
