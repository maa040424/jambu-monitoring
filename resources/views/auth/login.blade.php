<x-guest-layout>
    {{-- Session Status --}}
    @if (session('status'))
        <div class="alert alert-success small mb-3">{{ session('status') }}</div>
    @endif

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="alert alert-danger mb-3">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        {{-- Email --}}
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email"
                   value="{{ old('email') }}" required autofocus autocomplete="username"
                   placeholder="admin@jambu.local">
        </div>

        {{-- Password --}}
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
                <input type="password" class="form-control" id="password" name="password"
                       required autocomplete="current-password" placeholder="••••••••"
                       style="border-right: none;">
                <button class="btn btn-outline-secondary border-start-0" type="button" id="toggle-login-pw"
                        onclick="togglePasswordVisibility('password', 'toggle-login-pw')"
                        style="background: #0f172a; border-color: #374151; color: #94a3b8; border-radius: 0 10px 10px 0;">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>

        {{-- Remember Me --}}
        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" id="remember" name="remember">
            <label class="form-check-label" for="remember">Ingat saya</label>
        </div>

        {{-- Submit --}}
        <button type="submit" class="btn btn-login">
            <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
        </button>
    </form>

    {{-- Contact Admin --}}
    <div class="contact-admin">
        Belum punya akun?
        <a href="https://wa.me/6283143759920?text=Halo%20Admin%20Jambu%20Monitor%20%F0%9F%8C%BF%0A%0ASaya%20ingin%20dibuatkan%20akun%20untuk%20mengakses%20dashboard%20monitoring.%0A%0ANama%3A%20%5Bisi%20nama%20Anda%5D%0AEmail%3A%20%5Bisi%20email%20Anda%5D%0A%0ATerima%20kasih%20%F0%9F%99%8F" target="_blank" rel="noopener">
            <i class="bi bi-whatsapp"></i> Minta Akun Baru
        </a>
    </div>

    {{-- Forgot Password --}}
    <div class="contact-admin" style="margin-top: 0.75rem;">
        Lupa password?
        <a href="https://wa.me/6283143759920?text=Halo%20Admin%20Jambu%20Monitor%20%F0%9F%8C%BF%0A%0ASaya%20lupa%20password%20akun%20saya.%0AEmail%3A%20%5Bisi%20email%20Anda%5D%0A%0AMohon%20direset%20ke%20password%20default.%20Terima%20kasih%20%F0%9F%99%8F" target="_blank" rel="noopener">
            <i class="bi bi-whatsapp"></i> Reset Password
        </a>
    </div>

</x-guest-layout>
