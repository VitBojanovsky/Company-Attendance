<?php
require_once 'scripts/config.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Status - Admin</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #1a1a1a;
        }

        .server-status-container {
            width: 100%;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .status-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .status-header h1 {
            margin: 0;
            font-size: 24px;
        }

        .status-header a {
            color: white;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 4px;
            transition: background 0.3s;
        }

        .status-header a:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        #statusFrame {
            flex: 1;
            border: none;
            background: #fff;
        }
    </style>
</head>
<body>
    <div class="server-status-container">
        <div class="status-header">
            <h1>Server Status</h1>
            <a href="admin-dashboard.php">← Back to Dashboard</a>
        </div>
        <iframe id="statusFrame" src="/server-status"></iframe>
    </div>

    <script>
        const iframe = document.getElementById("statusFrame");

        iframe.onload = () => {
            const doc = iframe.contentDocument;

            const link = doc.createElement("link");
            link.rel = "stylesheet";
            link.href = "/styles.css";

            doc.head.appendChild(link);
        };
    </script>
</body>
</html>
