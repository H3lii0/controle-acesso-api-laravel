<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $movementLabel }}</title>
</head>
<body style="margin: 0; padding: 32px 16px; color: #17202a; background: #f3f6f8; font-family: Arial, sans-serif;">
    <div style="max-width: 560px; margin: 0 auto;">
        <div style="margin-bottom: 18px; color: #0f766e; font-size: 18px; font-weight: 700;">{{ $schoolName }}</div>
        <div style="border: 1px solid #d8e0e5; border-radius: 8px; padding: 28px; background: #ffffff;">
            <p style="margin: 0 0 18px; font-size: 16px;">Olá, {{ $recipientName }}.</p>
            <h1 style="margin: 0 0 12px; font-size: 22px;">{{ $movementLabel }}</h1>
            <p style="margin: 0 0 8px; color: #4d6070; font-size: 15px; line-height: 1.5;">
                O aluno <strong>{{ $studentName }}</strong> {{ strtolower($movementLabel) === 'entrada registrada' ? 'registrou entrada' : 'registrou saída' }} na escola.
            </p>
            <p style="margin: 0 0 24px; color: #4d6070; font-size: 15px;">{{ $occurredAt }}</p>
            <a href="{{ $portalUrl }}" style="display: inline-block; border-radius: 6px; padding: 12px 18px; color: #ffffff; background: #0f766e; font-size: 14px; font-weight: 700; text-decoration: none;">Acessar portal</a>
        </div>
    </div>
</body>
</html>
