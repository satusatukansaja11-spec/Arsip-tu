<?php
declare(strict_types=1);

final class AuthController
{
    public function showLogin(): void
    {
        render('auth/login', ['title' => 'Masuk'], 'auth');
    }

    public function login(): void
    {
        $username = (string)($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '' || strlen($username) > 50 || strlen($password) > 200) {
            flash('danger', 'Nama pengguna atau kata sandi salah.');
            redirect('login');
        }

        $result = Auth::attempt($username, $password);
        if (!$result['ok']) {
            flash('danger', $result['error']);
            redirect('login');
        }

        if ((int)Auth::user()['must_change_password'] === 1) {
            flash('warning', 'Anda harus mengganti kata sandi sementara sebelum melanjutkan.');
            redirect('password');
        }
        redirect('dashboard');
    }

    public function logout(): void
    {
        Auth::logout();
        flash('success', 'Anda telah keluar.');
        redirect('login');
    }

    public function showChangePassword(): void
    {
        render('auth/password', [
            'title'  => 'Ubah Kata Sandi',
            'forced' => (int)Auth::user()['must_change_password'] === 1,
        ]);
    }

    public function changePassword(): void
    {
        $uid = Auth::id();
        $current = (string)($_POST['current_password'] ?? '');
        $new     = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        $row = Database::one('SELECT password_hash FROM users WHERE id = ?', [$uid]);

        if (!$row || !password_verify($current, $row['password_hash'])) {
            flash('danger', 'Kata sandi saat ini salah.');
            redirect('password');
        }
        if ($new !== $confirm) {
            flash('danger', 'Konfirmasi kata sandi baru tidak cocok.');
            redirect('password');
        }
        if (hash_equals($current, $new)) {
            flash('danger', 'Kata sandi baru harus berbeda dari yang lama.');
            redirect('password');
        }
        if (($err = password_policy_error($new)) !== null) {
            flash('danger', $err);
            redirect('password');
        }

        Database::query(
            'UPDATE users SET password_hash = ?, must_change_password = 0, password_changed_at = NOW() WHERE id = ?',
            [password_hash($new, PASSWORD_DEFAULT), $uid]
        );
        session_regenerate_id(true);
        AuditService::log($uid, 'ubah_kata_sandi', 'user', $uid);

        flash('success', 'Kata sandi berhasil diubah.');
        redirect('dashboard');
    }
}
