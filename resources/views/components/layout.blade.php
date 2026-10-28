@props(['title'])
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
    <div class="container">
        <div class="page-notices">
            @if (session('status'))
                <p class="status" role="status">{{ session('status') }}</p>
            @endif
            @if ($errors->any())
                <ul class="errors" role="alert">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
        {{ $slot }}
    </div>
</body>
</html>
