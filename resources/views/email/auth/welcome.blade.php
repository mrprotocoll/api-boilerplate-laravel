@include('email.inc.header')

<div class="content-body">
    <img class="header-logo" src="{{ asset('logo.png') }}" alt="Logo">

    <h1>Welcome</h1>
    <p>Dear {{ $name }},</p>
    <p>We welcome you</p>
    <a href="{{ $dashboardLink }}" class="button">Dashboard</a>
</div>

@include('email.inc.footer')
