<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Lupa Password</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6; font-family: Arial, sans-serif;">
  <!-- Outer table (background) -->
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" align="center" style="background-color:#f3f4f6; padding:20px 0;">
    <tr>
      <td align="center">

        <!-- Card table -->
        <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="background:#ffffff; border-radius:10px; overflow:hidden; border:1px solid #e6e6e6;">

          <!-- Header image -->
          <tr>
            <td style="text-align:center; padding:0;">
              <img
                src=https://anker-web.okkyprojects.com/images/email/header_email.png
                alt="Header"
                width="600"
                style="display:block; width:100%; max-width:600px; height:auto; border:0; line-height:100%; outline:none; text-decoration:none;">
            </td>
          </tr>

          <!-- Inner padded area -->
          <tr>
            <td style="padding:28px 40px 34px 40px;">

              <!-- Hero image centered -->
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:18px;">
                <tr>
                  <td align="center" style="padding-bottom:8px;">
                    <img
                      src="https://anker-web.okkyprojects.com/images/email/hero.png"
                      alt="Hero"
                      style="display:block; max-width:260px; width:100%; height:auto; border:0; outline:none; text-decoration:none;">
                  </td>
                </tr>
              </table>

              <!-- Title -->
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                  <td align="center" style="padding-bottom:6px;">
                    <span style="font-size:18px; font-weight:700; color:#111827;">Halo {{ $user?->name ?? 'User' }},</span>
                  </td>
                </tr>
                <tr>
                  <td align="center" style="padding-bottom:14px;">
                    <span style="font-size:14px; color:#6b7280; line-height:1.5;">
                      Kami menerima permintaan untuk mengatur ulang password akun Anda<br>
                      <strong> Gunakan kode berikut untuk melanjutkan proses reset password:</strong>
                    </span>
                  </td>
                </tr>
              </table>

              <!-- OTP boxes (table-based) -->
              <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:20px auto;">
                <tr>
                  <!-- each box -->
                  @for ($i = 0; $i < 6; $i++)
                    <td style="width:56px; height:56px; text-align:center; vertical-align:middle; border-radius:12px; border:1px solid #E5E7EB; background:#FFFFFF; font-size:20px; font-weight:700; color:#111827; line-height:56px;">
                      {{ isset($otp) ? substr($otp, $i, 1) : '' }}
                    </td>
                    @if ($i < 5)
                      <td style="width:8px;"></td>
                    @endif
                  @endfor
                </tr>
              </table>

              <!-- Expiry -->
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:10px;">
                <tr>
                  <td align="center" style="padding-top:6px; padding-bottom:18px; color:#6b7280; font-size:14px;">
                    Kode ini hanya berlaku selama <strong>{{ $expiresInMinutes }} menit</strong>
                  </td>
                </tr>
              </table>

              <!-- Important block -->
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f8fafc; border-radius:8px; padding:14px;">
                <tr>
                  <td style="padding:8px 10px; text-align:left; color:#111827; font-size:13px; line-height:1.6;">
                    <div style="font-weight:700; margin-bottom:6px;">PENTING:</div>
                    <div style="color:#6b7280;">
                      Demi keamanan, jangan pernah membagikan kode ini kepada siapa pun, termasuk tim {{ config('app.name') }}.<br>
                      Kami tidak akan pernah meminta kode Anda.<br><br>
                      Jika Anda tidak merasa meminta kode ini, mohon abaikan email ini.
                    </div>
                  </td>
                </tr>
              </table>

              <!-- Footer text -->
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:18px;">
                <tr>
                  <td align="left" style="color:#111827; font-size:14px;">
                    Terima kasih,<br>
                    Tim {{ config('app.name') }}
                  </td>
                </tr>
              </table>

            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>
</body>
</html>