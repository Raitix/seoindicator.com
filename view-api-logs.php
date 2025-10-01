<?php
require_once 'db_config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_errno) {
    die("Connection failed: " . $conn->connect_error);
}

$result = $conn->query("SELECT * FROM api_logs ORDER BY created_at DESC LIMIT 50");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Logs - SEOIndicator.com</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        h1 { color: #333; }
        table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #4CAF50; color: white; position: sticky; top: 0; }
        tr:hover { background: #f9f9f9; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .code-200 { background: #d4edda; }
        .code-403 { background: #f8d7da; }
        .code-other { background: #fff3cd; }
        pre { max-width: 400px; overflow: auto; font-size: 11px; margin: 0; }
        .timestamp { font-size: 0.9em; color: #666; }
    </style>
</head>
<body>
    <h1>🔍 API Request Logs</h1>
    <p><a href="/">← Back to Home</a></p>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Job ID</th>
                <th>Provider</th>
                <th>Model</th>
                <th>HTTP</th>
                <th>Status</th>
                <th>Request Data</th>
                <th>Response Data</th>
                <th>Error</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr class="code-<?= $row['http_code'] ?>">
                        <td><?= $row['id'] ?></td>
                        <td><small><?= substr($row['job_id'], 0, 8) ?>...</small></td>
                        <td><strong><?= htmlspecialchars($row['provider']) ?></strong></td>
                        <td><?= htmlspecialchars($row['model']) ?></td>
                        <td><?= $row['http_code'] ?></td>
                        <td class="<?= $row['success'] ? 'success' : 'error' ?>">
                            <?= $row['success'] ? '✓' : '✗' ?>
                        </td>
                        <td><pre><?= htmlspecialchars(substr($row['request_data'], 0, 200)) ?>...</pre></td>
                        <td><pre><?= htmlspecialchars(substr($row['response_data'], 0, 300)) ?>...</pre></td>
                        <td><?= htmlspecialchars($row['error_message']) ?></td>
                        <td class="timestamp"><?= $row['created_at'] ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="10" style="text-align: center; padding: 40px;">No logs found</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
<?php $conn->close(); ?>

