@extends('layouts.app')

@section('content')

{{-- Flash Messages --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="background: rgba(34, 197, 94, 0.12); border: 1px solid rgba(34, 197, 94, 0.3); color: #4ade80;">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5;">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-4">
    {{-- Left Column: User List --}}
    <div class="col-12 col-lg-8">
        <div class="card" style="background: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-primary);">
            <div class="card-header d-flex align-items-center justify-content-between" style="border-bottom: 1px solid var(--border-color);">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-people-fill text-success"></i>
                    <span class="fw-semibold">Daftar User</span>
                </div>
                <span class="badge bg-success bg-opacity-25 text-success">{{ $users->count() }} user</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="color: var(--text-primary);">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th class="ps-3">Nama</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Login Terakhir</th>
                                <th class="text-end pe-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: {{ $user->isAdmin() ? 'rgba(239, 68, 68, 0.15)' : 'rgba(56, 189, 248, 0.15)' }};">
                                            <i class="bi {{ $user->isAdmin() ? 'bi-shield-lock text-danger' : 'bi-person text-info' }}" style="font-size: 0.85rem;"></i>
                                        </div>
                                        <span class="fw-medium">{{ $user->name }}</span>
                                    </div>
                                </td>
                                <td style="color: var(--text-secondary);">{{ $user->email }}</td>
                                <td>
                                    <span class="badge {{ $user->isAdmin() ? 'bg-danger' : 'bg-info' }} bg-opacity-75" style="font-size: 0.75rem;">
                                        {{ ucfirst($user->role) }}
                                    </span>
                                </td>
                                <td style="color: var(--text-secondary); font-size: 0.85rem;">
                                    @if($user->last_login_at)
                                        <i class="bi bi-clock me-1"></i>{{ $user->last_login_at->diffForHumans() }}
                                        <div class="text-muted" style="font-size: 0.75rem;">
                                            {{ $user->last_login_at->format('d M Y, H:i') }}
                                        </div>
                                    @else
                                        <span class="text-muted">Belum pernah login</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    @if($user->id !== auth()->id())
                                    <div class="d-flex gap-1 justify-content-end">
                                        <form method="POST" action="{{ route('users.resetPassword', $user) }}" class="d-inline"
                                              onsubmit="return confirm('Reset password {{ $user->name }} ke default (jambu123)?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Reset Password">
                                                <i class="bi bi-key"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('users.destroy', $user) }}" class="d-inline"
                                              onsubmit="return confirm('Yakin ingin menghapus akun {{ $user->name }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    </div>
                                    @else
                                    <span class="badge bg-secondary bg-opacity-25 text-secondary" style="font-size: 0.7rem;">Anda</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Belum ada user terdaftar.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Right Column: Add User Form --}}
    <div class="col-12 col-lg-4">
        <div class="card" style="background: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-primary);">
            <div class="card-header" style="border-bottom: 1px solid var(--border-color);">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-person-plus-fill text-success"></i>
                    <span class="fw-semibold">Tambah Akun Petani</span>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('users.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label small fw-semibold" style="color: var(--text-secondary);">Nama</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                               value="{{ old('name') }}" required placeholder="Nama petani"
                               style="background: var(--form-bg, #0f172a); border-color: var(--form-border, #374151); color: var(--form-text, #f9fafb);">
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label small fw-semibold" style="color: var(--text-secondary);">Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                               value="{{ old('email') }}" required placeholder="petani@jambu.local"
                               style="background: var(--form-bg, #0f172a); border-color: var(--form-border, #374151); color: var(--form-text, #f9fafb);">
                        @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="add-password" class="form-label small fw-semibold" style="color: var(--text-secondary);">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="add-password" name="password"
                                   required placeholder="Minimal 8 karakter"
                                   style="background: var(--form-bg, #0f172a); border-color: var(--form-border, #374151); color: var(--form-text, #f9fafb); border-right: none;">
                            <button class="btn border-start-0" type="button" id="toggle-add-pw"
                                    onclick="togglePw('add-password', 'toggle-add-pw')"
                                    style="background: var(--form-bg, #0f172a); border-color: var(--form-border, #374151); color: var(--text-muted, #94a3b8);">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="add-password-confirm" class="form-label small fw-semibold" style="color: var(--text-secondary);">Konfirmasi Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="add-password-confirm" name="password_confirmation"
                                   required placeholder="Ulangi password"
                                   style="background: var(--form-bg, #0f172a); border-color: var(--form-border, #374151); color: var(--form-text, #f9fafb); border-right: none;">
                            <button class="btn border-start-0" type="button" id="toggle-add-pw-confirm"
                                    onclick="togglePw('add-password-confirm', 'toggle-add-pw-confirm')"
                                    style="background: var(--form-bg, #0f172a); border-color: var(--form-border, #374151); color: var(--text-muted, #94a3b8);">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn w-100 fw-semibold" style="background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; border: none; border-radius: 10px; padding: 0.6rem;">
                        <i class="bi bi-person-plus me-1"></i> Tambah Akun Petani
                    </button>
                </form>

                <div class="mt-3 small text-muted text-center">
                    <i class="bi bi-info-circle me-1"></i>Akun yang dibuat otomatis memiliki role <strong>Petani</strong>.
                </div>
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
