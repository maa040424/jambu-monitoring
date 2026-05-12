@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-6 col-lg-5">

        {{-- Flash Messages --}}
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="background: rgba(34, 197, 94, 0.12); border: 1px solid rgba(34, 197, 94, 0.3); color: #4ade80;">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5;">
            @foreach ($errors->all() as $error)
                <div><i class="bi bi-exclamation-circle me-1"></i>{{ $error }}</div>
            @endforeach
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <div class="card" style="background: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 16px;">
            <div class="card-header d-flex align-items-center gap-2" style="border-bottom: 1px solid var(--border-color);">
                <i class="bi bi-key-fill text-warning"></i>
                <span class="fw-semibold">Ubah Password</span>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-4">
                    <i class="bi bi-info-circle me-1"></i>
                    Pastikan menggunakan password yang kuat dan mudah diingat.
                </p>

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    @method('PUT')

                    {{-- Current Password --}}
                    <div class="mb-3">
                        <label for="current_password" class="form-label small fw-semibold" style="color: var(--text-secondary);">
                            <i class="bi bi-lock me-1"></i>Password Lama
                        </label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="current_password" name="current_password"
                                   required placeholder="Masukkan password saat ini"
                                   style="background: var(--form-bg); border-color: var(--form-border); color: var(--form-text); border-right: none;">
                            <button class="btn border-start-0" type="button" id="toggle-current"
                                    onclick="togglePw('current_password', 'toggle-current')"
                                    style="background: var(--form-bg); border-color: var(--form-border); color: var(--text-muted);">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <hr style="border-color: var(--border-color); opacity: 0.3;" class="my-4">

                    {{-- New Password --}}
                    <div class="mb-3">
                        <label for="password" class="form-label small fw-semibold" style="color: var(--text-secondary);">
                            <i class="bi bi-key me-1"></i>Password Baru
                        </label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password"
                                   required placeholder="Minimal 8 karakter"
                                   style="background: var(--form-bg); border-color: var(--form-border); color: var(--form-text); border-right: none;">
                            <button class="btn border-start-0" type="button" id="toggle-new"
                                    onclick="togglePw('password', 'toggle-new')"
                                    style="background: var(--form-bg); border-color: var(--form-border); color: var(--text-muted);">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Confirm New Password --}}
                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label small fw-semibold" style="color: var(--text-secondary);">
                            <i class="bi bi-key me-1"></i>Konfirmasi Password Baru
                        </label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation"
                                   required placeholder="Ulangi password baru"
                                   style="background: var(--form-bg); border-color: var(--form-border); color: var(--form-text); border-right: none;">
                            <button class="btn border-start-0" type="button" id="toggle-confirm"
                                    onclick="togglePw('password_confirmation', 'toggle-confirm')"
                                    style="background: var(--form-bg); border-color: var(--form-border); color: var(--text-muted);">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn w-100 fw-semibold" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; border: none; border-radius: 10px; padding: 0.6rem;">
                        <i class="bi bi-check2-circle me-1"></i> Simpan Password Baru
                    </button>
                </form>
            </div>
        </div>

        {{-- Help Card --}}
        <div class="card mt-3" style="background: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 12px;">
            <div class="card-body py-3">
                <p class="small text-muted mb-2">
                    <i class="bi bi-question-circle me-1"></i><strong>Lupa password lama?</strong>
                </p>
                <p class="small text-muted mb-0">
                    Hubungi admin via WhatsApp untuk mereset password akun Anda ke pengaturan default.
                </p>
                <a href="https://wa.me/6283143759920?text={{ urlencode('Halo Admin Jambu Monitor 🌿' . chr(10) . chr(10) . 'Saya lupa password akun saya.' . chr(10) . 'Nama: ' . auth()->user()->name . chr(10) . 'Email: ' . auth()->user()->email . chr(10) . chr(10) . 'Mohon direset ke password default. Terima kasih 🙏') }}"
                   target="_blank" class="btn btn-sm mt-2 fw-semibold" style="background: #25d366; color: #fff; border: none; border-radius: 8px;">
                    <i class="bi bi-whatsapp me-1"></i> Chat Admin untuk Reset
                </a>
            </div>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
function togglePw(inputId, btnId) {
    const input = document.getElementById(inputId);
    const btn = document.getElementById(btnId);
    if (!input || !btn) return;
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>
@endsection
