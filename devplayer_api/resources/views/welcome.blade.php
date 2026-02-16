<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex">
    <meta name="googlebot" content="noindex, nofollow, noarchive, nosnippet, noimageindex">
    <title>DevPlayer API</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f1115;
            color: #e5e7eb;
            line-height: 1.6;
        }
        .container {
            max-width: 720px;
            margin: 0 auto;
            padding: 48px 24px;
        }
        h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 12px;
            color: #fff;
        }
        .subtitle {
            color: #cbd5e1;
            margin-bottom: 24px;
            font-size: 15px;
        }
        .card {
            background: #111827;
            border: 1px solid #1f2937;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        .card-title {
            font-size: 14px;
            font-weight: 600;
            color: #9ca3af;
            margin-bottom: 12px;
        }
        .card-content {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
        }
        .label {
            color: #9ca3af;
            font-size: 14px;
        }
        code {
            background: #1f2937;
            padding: 4px 8px;
            border-radius: 4px;
            font-family: 'Monaco', 'Courier New', monospace;
            font-size: 13px;
            color: #60a5fa;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #1f2937;
            color: #6b7280;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎬 DevPlayer API</h1>
        <p class="subtitle">Backend service for DevPlayer. This endpoint is not intended for indexing.</p>

        <div class="card">
            <div class="card-title">API Information</div>
            <div class="card-content">
                <div class="item">
                    <span class="label">API Base URL</span>
                    <code>/api/v1</code>
                </div>
                <div class="item">
                    <span class="label">Healthcheck</span>
                    <code>/health</code> or <code>/up</code>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-title">Authentication</div>
            <div class="card-content">
                <div class="item">
                    <span class="label">Auth Type</span>
                    <code>JWT</code>
                </div>
                <div class="item">
                    <span class="label">Auth Endpoints</span>
                    <code>/auth/login</code>, <code>/auth/register</code>, <code>/auth/logout</code>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>This is an internal API endpoint. Search engines and bots are blocked from indexing this page.</p>
        </div>
    </div>
</body>
</html>

