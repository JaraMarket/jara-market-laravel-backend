@extends('emails.layouts.app')

@section('content')
<h3 style="font-size:20px; color:#1a1a1a; margin-bottom:10px;">Verify Your JaraMarket Account</h3>

<p style="color:#555; font-size:15px; margin-bottom:6px;">Hello {{ $user->firstname }},</p>
<p style="color:#555; font-size:15px;">Use the 4-digit code below to complete your registration. This code expires in <strong>15 minutes</strong>.</p>

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 28px 0; text-align: center;">
    <tr>
        <td>
            <div style="
                display: inline-block;
                background: #f4f7ff;
                border: 2px dashed #4f6ef7;
                border-radius: 12px;
                padding: 18px 40px;
                font-size: 36px;
                font-weight: 800;
                letter-spacing: 10px;
                color: #2d3be8;
                font-family: 'Courier New', monospace;
            ">
                {{ $otp }}
            </div>
        </td>
    </tr>
</table>

<p style="font-size:13px; color:#888; background:#fff8e1; border-left:4px solid #ffc107; padding: 10px 14px; border-radius:4px;">
    <strong>Important:</strong> Never share this OTP with anyone. JaraMarket staff will never ask for your OTP.
</p>

<p style="color:#555; font-size:14px; margin-top:20px;">If you did not create an account, please ignore this email.</p>

<p style="color:#555; font-size:14px;">Thanks,<br>
<strong>The {{ config('app.name') }} Team</strong></p>
@endsection
