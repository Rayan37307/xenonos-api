<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Dashboard - {{ config('app.name') }}">
  <title>Dashboard - {{ config('app.name') }}</title>
  <link href="https://fonts.googleapis.com/css?family=Inter&display=swap" rel="stylesheet">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ time() }}">
  <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}?v={{ time() }}">
  <link rel="stylesheet" href="{{ asset('css/footer.css') }}?v={{ time() }}">
  <link rel="stylesheet" href="{{ asset('css/topbar.css') }}?v={{ time() }}">
</head>
<body>
<div class="dashbord-v2-1">
<div class="node-2"></div>
@include('partials.sidebar')
<div class="main-content-area-57">
@include('partials.topbar')

@yield('content')

@include('partials.footer')
</div>
</div>

</body>
</html>
