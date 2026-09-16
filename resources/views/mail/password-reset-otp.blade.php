<x-mail::message>
Use this code to reset your password:

<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="center" style="padding: 32px 0;">
<table cellpadding="0" cellspacing="0" role="presentation" align="center" style="background-color: #f3f4f6; border-radius: 8px;">
<tr>
<td align="center" style="padding: 14px 28px;">
<span style="display: inline-block; font-size: 36px; font-weight: 700; letter-spacing: 12px; line-height: 1.2; font-family: 'Courier New', Courier, ui-monospace, monospace; color: #111827;">
{{ $otp }}
</span>
</td>
</tr>
</table>
</td>
</tr>
</table>

This code expires in {{ $minutes }} minutes.

If you did not request a password reset, no further action is required.
</x-mail::message>
