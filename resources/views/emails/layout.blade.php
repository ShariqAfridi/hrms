<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'AxeCorp HRMS Notification' }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #0f172a;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #1e293b;
            -webkit-font-smoothing: antialiased;
        }
        .email-wrapper {
            width: 100%;
            background-color: #0f172a;
            padding: 40px 16px;
            box-sizing: border-box;
        }
        .email-card {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            border: 1px solid #334155;
        }
        .email-header {
            background: linear-gradient(135deg, #1e1b4b 0%, #0f172a 100%);
            padding: 32px 36px;
            text-align: left;
            border-bottom: 2px solid #4f46e5;
        }
        .brand-title {
            color: #ffffff;
            font-size: 20px;
            font-weight: 900;
            letter-spacing: -0.5px;
            margin: 0;
        }
        .brand-subtitle {
            color: #94a3b8;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 4px;
        }
        .email-body {
            padding: 36px;
            background-color: #ffffff;
            font-size: 14px;
            line-height: 1.6;
            color: #334155;
        }
        .headline {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 16px;
        }
        .meta-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #4f46e5;
            border-radius: 12px;
            padding: 16px 20px;
            margin: 20px 0;
        }
        .meta-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 13px;
            border-bottom: 1px dashed #e2e8f0;
        }
        .meta-row:last-child {
            border-bottom: none;
        }
        .meta-label {
            color: #64748b;
            font-weight: 600;
        }
        .meta-val {
            color: #0f172a;
            font-weight: 700;
            text-align: right;
        }
        .btn-wrapper {
            text-align: center;
            margin: 28px 0 12px;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
            color: #ffffff !important;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
            padding: 14px 32px;
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4);
        }
        .btn-success {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.4);
        }
        .btn-danger {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.4);
        }
        .email-footer {
            background-color: #f8fafc;
            padding: 24px 36px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
            line-height: 1.5;
        }
        .email-footer a {
            color: #6366f1;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-card">
            <!-- Header -->
            <div class="email-header">
                <h1 class="brand-title">AxeCorp Technologies</h1>
                <div class="brand-subtitle">Enterprise HRMS &amp; Remuneration Hub</div>
            </div>

            <!-- Content -->
            <div class="email-body">
                @yield('content')
            </div>

            <!-- Footer -->
            <div class="email-footer">
                <p>This is an automated confidential notification from AxeCorp HRMS.</p>
                <p>Please do not reply directly to this automated email. Log in to <a href="{{ url('/') }}">HRMS Portal</a> to manage your records.</p>
                <p style="margin-top: 8px; font-size: 10px; color: #cbd5e1;">AxeCorp Center • Corporate Directorate • System Ref: {{ md5(uniqid('', true)) }}</p>
            </div>
        </div>
    </div>
</body>
</html>
