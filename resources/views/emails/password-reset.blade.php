<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reset your Star Coffee password</title>
</head>
<body style="margin:0;padding:0;background:#f5f0ea;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#1f1410;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#f5f0ea;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width:520px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(40,20,10,0.08);">
                    <tr>
                        <td style="background:linear-gradient(135deg,#78350f 0%,#92400e 100%);padding:28px 32px;color:#fff7ed;">
                            <p style="margin:0;font-size:12px;letter-spacing:2px;text-transform:uppercase;opacity:0.8;">Star Coffee</p>
                            <h1 style="margin:6px 0 0;font-size:22px;font-weight:700;">Reset your password</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px;">
                            <p style="margin:0 0 12px;font-size:15px;">Hi {{ $name ?: 'there' }},</p>
                            <p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#3a2a1c;">
                                We received a request to reset the password for your Star Coffee account.
                                Tap the button below to choose a new one.
                            </p>
                            <p style="margin:0 0 24px;">
                                <a href="{{ $url }}" style="display:inline-block;padding:12px 24px;background:#7c4a1e;color:#fff7ed;text-decoration:none;border-radius:999px;font-weight:700;font-size:14px;letter-spacing:0.5px;">
                                    Reset password
                                </a>
                            </p>
                            <p style="margin:0 0 12px;font-size:12px;color:#6b5444;">
                                This link expires in {{ $minutes }} minutes. If you didn't request a reset, you can safely ignore this email — your password stays the same.
                            </p>
                            <p style="margin:18px 0 0;font-size:11px;color:#8b7261;word-break:break-all;">
                                If the button doesn't work, copy this link into your browser:<br />
                                <span style="color:#7c4a1e;">{{ $url }}</span>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#fff7ed;padding:18px 32px;text-align:center;font-size:11px;color:#8b7261;">
                            — Star Coffee House —
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
