@include('email.inc.header')

<div class="content-body">
    <img class="header-logo" src="{{ asset('logo.png') }}" alt="Logo">

    <h1>Email Verification</h1>
    <p>Dear {{ $name }},</p>
    <p>Thank you for registering with us. To ensure the security of your account. See your verification code below</p>
    <h1>{{$token}}</h1>
</div>

@include('email.inc.footer')
