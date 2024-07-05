@include('email.inc.header')

<div class="content-body">
    <img class="header-logo" src="{{ asset('logo.png') }}" alt="Logo">

    <p>Dear {{ $name }},</p>

    <p>We received a request to reset your password. If you made this request, please click the button below to reset your password:</p>
    <a href="{{ $link }}" class="button">Reset Password</a>
</div>

@include('email.inc.footer')
