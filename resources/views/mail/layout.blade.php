<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Track Coach' }}</title>
</head>
<body style="margin:0;padding:0;background-color:#020617;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#020617;padding:32px 16px;">
    <tr>
        <td align="center">
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width:560px;background-color:#0f172a;border:1px solid #1e293b;border-radius:16px;overflow:hidden;">
                <tr>
                    <td style="padding:28px 32px 8px;">
                        <p style="margin:0;font-size:20px;font-weight:700;color:#f8fafc;">Track Coach</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:8px 32px 32px;color:#cbd5e1;font-size:15px;line-height:1.6;">
                        {{ $slot }}
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 32px 28px;color:#64748b;font-size:12px;line-height:1.5;">
                        Cet e-mail a été envoyé par Track Coach. Si tu n'es pas à l'origine de cette demande, tu peux l'ignorer.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
