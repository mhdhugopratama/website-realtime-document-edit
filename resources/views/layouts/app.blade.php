<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'GoDocs')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    @stack('head')
    @yield('styles')
</head>

<body>
    @auth
        <nav class="navbar">
            <a href="{{ route('dashboard') }}" class="navbar-brand">GoDocs</a>

            <div class="navbar-user">
                <div class="nav-dropdown" id="nav-dropdown">
                    <div class="nav-dropdown-trigger" onclick="toggleNavDropdown()">
                        <div class="nav-avatar">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                        <span>{{ Auth::user()->name }}</span>
                        <span class="nav-arrow">&#9660;</span>
                    </div>

                    <div class="nav-dropdown-menu">
                        <div class="dd-user-info">
                            <div class="dd-name">{{ Auth::user()->name }}</div>
                            <div class="dd-email">{{ Auth::user()->email }}</div>
                        </div>

                        <a href="{{ route('profile') }}" class="dd-item">
                            Lihat Profil
                        </a>

                        <div class="dd-divider"></div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dd-item danger">
                                Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>

        <script>
            function toggleNavDropdown() {
                document.getElementById('nav-dropdown').classList.toggle('open');
            }

            document.addEventListener('click', function(e) {
                const dd = document.getElementById('nav-dropdown');
                if (dd && !dd.contains(e.target)) {
                    dd.classList.remove('open');
                }
            });
        </script>
    @endauth

    @yield('content')

    @yield('scripts')
</body>

</html>
