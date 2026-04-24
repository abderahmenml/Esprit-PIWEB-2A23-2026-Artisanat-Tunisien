<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Synchronisation Supabase</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background-color: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            line-height: 1.6;
        }
        pre {
            background-color: #2d2d2d;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            border-left: 4px solid #0ea5e9;
        }
        h1 {
            color: #0ea5e9;
            margin-bottom: 20px;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #888;
        }
    </style>
</head>
<body>
    <h1>Test de Synchronisation Supabase</h1>
    <pre><?php echo htmlspecialchars(implode("\n", $output ?? [])); ?></pre>
    <div class="footer">
        <p>Cree le: <?php echo date('Y-m-d H:i:s'); ?></p>
        <p>Version: 1.0 - CraftLink Supabase Sync</p>
    </div>
</body>
</html>
