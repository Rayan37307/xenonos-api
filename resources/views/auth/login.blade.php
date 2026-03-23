<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - {{ config('app.name') }}</title>
    @vite(['resources/css/auth.css'])
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Login to Your Account</h1>
            <p>Get your credentials from officials then login here</p>
        </div>

        @if($errors->any())
            <div class="error-message">
                {{ $errors->first() }}
            </div>
        @endif

        @if(session('error'))
            <div class="error-message">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" style="display: flex; flex-direction: column; gap: 20px;">
            @csrf

            <x-input
                type="email"
                name="email"
                placeholder="email"
                :value="old('email')"
                autofocus
            />

            <x-input
                type="password"
                name="password"
                placeholder="password"
            />

            <div style="display: flex; justify-content: center;">
                <x-button type="submit">
                    Login
                </x-button>
            </div>
        </form>
    </div>
</body>
</html>
