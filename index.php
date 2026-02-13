<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="js/jquery.js"></script>
    <title>Login | Credit Management</title>
    <style>
        :root {
            --primary-bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --glass-bg: rgba(255, 255, 255, 0.15);
            --glass-border: rgba(255, 255, 255, 0.2);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--primary-bg);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            overflow: hidden;
        }

        .login-card {
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 3rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .brand-logo {
            font-size: 2.5rem;
            font-weight: 600;
            color: white;
            text-align: center;
            margin-bottom: 0.5rem;
            letter-spacing: -1px;
        }

        .brand-subtitle {
            color: rgba(255, 255, 255, 0.7);
            text-align: center;
            margin-bottom: 2.5rem;
            font-size: 0.95rem;
        }

        .form-floating > .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            color: white;
            padding-left: 1.25rem;
        }

        .form-floating > .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: white;
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.1);
            color: white;
        }

        .form-floating > label {
            color: rgba(255, 255, 255, 0.6);
            padding-left: 1.25rem;
        }

        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            color: white;
            opacity: 0.8;
        }

        .btn-login {
            background: white;
            color: #764ba2;
            border: none;
            border-radius: 12px;
            padding: 0.8rem;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            background: rgba(255, 255, 255, 0.9);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        #messageBox {
            margin-top: 1.5rem;
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.3);
            border-radius: 12px;
            padding: 1rem;
            color: #ff9999;
            font-size: 0.9rem;
            display: none;
            backdrop-filter: blur(5px);
        }

        .close-msg {
            cursor: pointer;
            float: right;
            color: white;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="brand-logo">Credit</div>
        <div class="brand-subtitle">Financial Management Portal</div>
        
        <div>
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="admin_username" placeholder="Username">
                <label for="admin_username">Username</label>
            </div>
            <div class="form-floating mb-4">
                <input type="password" class="form-control" id="admin_password" placeholder="Password">
                <label for="admin_password">Password</label>
            </div>
            
            <button class="btn btn-login" id="btn_sign_in">Sign in</button>
        </div>

        <div id="messageBox">
            <a class="close-msg" id="close_messageBox">&times;</a>
            <div id="messageBody">Invalid credentials, please try again.</div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/login.js"></script>
</body>

</html>