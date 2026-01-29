<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Server Error</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 60px 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 600px;
            width: 100%;
        }
        .error-code {
            font-size: 120px;
            font-weight: bold;
            color: #667eea;
            line-height: 1;
            margin-bottom: 20px;
        }
        h1 {
            color: #2d3748;
            font-size: 32px;
            margin-bottom: 15px;
        }
        p {
            color: #718096;
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #e2e8f0;
            color: #2d3748;
        }
        .btn-secondary:hover {
            background: #cbd5e0;
            transform: translateY(-2px);
        }
        .info {
            margin-top: 40px;
            padding: 20px;
            background: #f7fafc;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        .info h3 {
            color: #2d3748;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .info ul {
            list-style: none;
            text-align: left;
            color: #4a5568;
        }
        .info li {
            padding: 8px 0;
            font-size: 14px;
        }
        .info li:before {
            content: "→ ";
            color: #667eea;
            font-weight: bold;
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-code">500</div>
        <h1>Internal Server Error</h1>
        <p>Oops! Something went wrong on our end. We're working to fix the issue.</p>
        
        <div class="actions">
            <a href="/" class="btn btn-primary">Go to Homepage</a>
            <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
        </div>

        <?php if (isset($_ENV['APP_ENV']) && in_array($_ENV['APP_ENV'], ['development', 'dev'])): ?>
        <div class="info">
            <h3>Troubleshooting Steps:</h3>
            <ul>
                <li>Check server error logs for details</li>
                <li>Verify database connection settings</li>
                <li>Ensure all required PHP extensions are installed</li>
                <li>Check file permissions on uploads and logs folders</li>
                <li>Run debug_error.php for detailed diagnostics</li>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
