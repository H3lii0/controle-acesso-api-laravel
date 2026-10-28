<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Ative seu acesso</title>
  </head>
  <body style="margin: 0; padding: 0; background: #f4f7f8; color: #152b3d; font-family: Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background: #f4f7f8;">
      <tr>
        <td align="center" style="padding: 36px 16px;">
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width: 600px;">
            <tr>
              <td align="center" style="padding: 0 0 24px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                  <tr>
                    <td align="center" valign="middle" width="42" height="42" style="width: 42px; height: 42px; border-radius: 10px; background: #0f766e;">
                      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M3 21h18M5 21V9l7-5 7 5v12M8 21v-7h8v7M9 10h.01M15 10h.01" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </td>
                    <td style="padding-left: 10px; color: #152b3d; font-size: 18px; font-weight: 700;">Acesso Escolar</td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr>
              <td style="border: 1px solid #d7e0e4; border-radius: 10px; background: #ffffff;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                  <tr>
                    <td style="padding: 36px 38px 12px;">
                      <p style="margin: 0 0 8px; color: #0f766e; font-size: 12px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;">Convite de acesso</p>
                      <h1 style="margin: 0; color: #152b3d; font-size: 24px; line-height: 1.3;">Olá, {{ $recipientName }}!</h1>
                    </td>
                  </tr>
                  <tr>
                    <td style="padding: 0 38px; color: #526b7d; font-size: 15px; line-height: 1.7;">
                      <p style="margin: 0 0 14px;">Seu acesso ao sistema da {{ $schoolName }} foi criado.</p>
                      <p style="margin: 0;">Crie sua senha para ativar a conta e acessar as informações dos alunos vinculados.</p>
                    </td>
                  </tr>
                  <tr>
                    <td align="center" style="padding: 28px 38px;">
                      <a href="{{ $activationUrl }}" style="display: inline-block; border-radius: 6px; padding: 13px 22px; color: #ffffff; background: #0f766e; font-size: 14px; font-weight: 700; text-decoration: none;">Criar minha senha</a>
                    </td>
                  </tr>
                  <tr>
                    <td style="padding: 0 38px 30px; color: #526b7d; font-size: 13px; line-height: 1.6;">
                      <p style="margin: 0 0 14px;">Este convite expira em <strong style="color: #152b3d;">{{ $expiration }}</strong>.</p>
                      <p style="margin: 0;">Se você não esperava este convite, pode desconsiderar esta mensagem.</p>
                    </td>
                  </tr>
                  <tr>
                    <td style="border-top: 1px solid #e3eaed; padding: 22px 38px 28px; color: #526b7d; font-size: 13px; line-height: 1.6;">
                      <p style="margin: 0 0 4px;">Atenciosamente,</p>
                      <p style="margin: 0; color: #152b3d; font-weight: 700;">Equipe {{ $schoolName }}</p>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr>
              <td style="padding: 20px 24px 0; color: #78909c; font-size: 11px; line-height: 1.6; text-align: center;">
                <p style="margin: 0 0 8px;">Se o botão não funcionar, copie e cole este endereço no navegador:</p>
                <p style="margin: 0; word-break: break-all;"><a href="{{ $activationUrl }}" style="color: #0f766e;">{{ $activationUrl }}</a></p>
                <p style="margin: 18px 0 0;">{{ $schoolName }} · Sistema de controle de acesso escolar</p>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>
