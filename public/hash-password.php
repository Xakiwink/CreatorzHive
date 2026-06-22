<?php
/**
 * Web-based password hasher
 * Visit: https://creatorz.freedev.app/hash-password.php
 *
 * Use this to generate bcrypt hashes for manual admin user creation in phpMyAdmin
 */

error_reporting(0);
ini_set('display_errors', 0);

$hash = '';
$password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters';
    } elseif (strlen($password) > 100) {
        $error = 'Password too long (max 100 characters)';
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}

?><!DOCTYPE html>
<html>
<head>
    <title>Password Hasher</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .container { background: white; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); padding: 40px; max-width: 500px; width: 100%; }
        h1 { color: #333; margin-bottom: 10px; font-size: 28px; }
        .subtitle { color: #666; margin-bottom: 30px; font-size: 14px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; color: #333; font-weight: 500; margin-bottom: 8px; }
        input[type="password"], input[type="text"] { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 5px; font-size: 14px; transition: border-color 0.3s; }
        input[type="password"]:focus, input[type="text"]:focus { outline: none; border-color: #667eea; }
        button { width: 100%; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 5px; font-weight: 600; font-size: 16px; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
        button:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4); }
        .result { margin-top: 30px; padding: 20px; background: #f5f5f5; border-radius: 5px; display: none; }
        .result.show { display: block; }
        .result-label { color: #666; font-size: 12px; text-transform: uppercase; margin-bottom: 5px; }
        .result-value { background: white; padding: 12px; border-radius: 3px; font-family: 'Courier New', monospace; font-size: 13px; word-break: break-all; border: 1px solid #e0e0e0; margin-bottom: 10px; }
        .copy-btn { background: #4CAF50; padding: 8px 15px; font-size: 12px; width: auto; }
        .copy-btn:hover { background: #45a049; }
        .error { color: #d32f2f; background: #ffebee; padding: 12px; border-radius: 5px; margin-bottom: 20px; font-size: 14px; }
        .success { color: #388e3c; background: #e8f5e9; padding: 12px; border-radius: 5px; margin-bottom: 20px; font-size: 14px; }
        .instructions { background: #e3f2fd; padding: 15px; border-radius: 5px; margin-top: 30px; font-size: 13px; color: #1565c0; }
        .instructions h3 { margin-bottom: 10px; font-size: 14px; }
        .instructions ol { margin-left: 20px; }
        .instructions li { margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Password Hasher</h1>
        <p class="subtitle">Generate bcrypt hash for phpMyAdmin user creation</p>

        <?php if (isset($error)): ?>
        <div class="error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="password">Enter Password</label>
                <input type="password" id="password" name="password" placeholder="Admin@1234" required minlength="8" maxlength="100" value="<?php echo htmlspecialchars($password); ?>">
            </div>

            <button type="submit">Generate Hash</button>
        </form>

        <?php if ($hash): ?>
        <div class="result show">
            <div class="result-label">🎯 Your Bcrypt Hash</div>
            <div class="result-value"><?php echo htmlspecialchars($hash); ?></div>
            <button class="copy-btn" onclick="copyHash()">📋 Copy to Clipboard</button>
        </div>

        <div class="success">
            ✅ Hash generated successfully!<br>
            Now paste this into phpMyAdmin's users table password field.
        </div>
        <?php endif; ?>

        <div class="instructions">
            <h3>📝 How to Use</h3>
            <ol>
                <li>Enter your desired password (8+ characters)</li>
                <li>Click "Generate Hash"</li>
                <li>Copy the hash (click the button)</li>
                <li>Go to phpMyAdmin → users table → Insert</li>
                <li>Paste hash into the password field</li>
                <li>Fill in other fields: name, username, email, role=admin</li>
                <li>Click Insert/Save</li>
            </ol>
        </div>
    </div>

    <script>
    function copyHash() {
        const hash = document.querySelector('.result-value').textContent;
        navigator.clipboard.writeText(hash).then(() => {
            alert('✅ Hash copied to clipboard!');
        }).catch(() => {
            alert('Manual copy: Select the hash above and press Ctrl+C');
        });
    }
    </script>
</body>
</html>
