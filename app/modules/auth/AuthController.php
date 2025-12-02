<?php

/**
 * Auth Controller
 * 
 * Handles authentication (login, logout, password reset)
 */

class AuthController extends Controller
{

    /**
     * Show login form
     */
    public function login()
    {
        // Redirect if already logged in
        if (Auth::check()) {
            $this->redirect('dashboard');
        }

        $data = [
            'title' => 'Login - SIMRS',
            'timeout' => isset($_GET['timeout']) ? true : false
        ];

        $this->view('auth/views/login', $data, false);
    }

    /**
     * Process login
     */
    public function doLogin()
    {
        if (!isPost()) {
            $this->redirect('auth/login');
        }

        // Validate CSRF
        $this->requireCsrf();

        // Get input
        $username = $this->post('username');
        $password = $this->post('password');
        $remember = $this->post('remember');

        // Validation
        $errors = $this->validate([
            'username' => $username,
            'password' => $password
        ], [
            'username' => 'required',
            'password' => 'required'
        ]);

        if (!empty($errors)) {
            flashOld($_POST);
            $this->setFlash('error', 'Username dan password wajib diisi');
            $this->redirect('auth/login');
        }

        // Attempt login
        $result = Auth::attempt($username, $password);

        if ($result['success']) {
            // Check for intended URL
            $intendedUrl = Session::get('intended_url', 'dashboard');
            Session::remove('intended_url');

            $this->setFlash('success', 'Login berhasil');
            $this->redirect($intendedUrl);
        } else {
            flashOld($_POST);
            $this->setFlash('error', $result['message']);
            $this->redirect('auth/login');
        }
    }

    /**
     * Logout
     */
    public function logout()
    {
        Auth::logout();
        $this->setFlash('success', 'Anda telah logout');
        $this->redirect('auth/login');
    }

    /**
     * Show forgot password form
     */
    public function forgotPassword()
    {
        if (Auth::check()) {
            $this->redirect('dashboard');
        }

        $data = [
            'title' => 'Lupa Password - SIMRS'
        ];

        $this->view('auth/views/forgot-password', $data, false);
    }

    /**
     * Process forgot password
     */
    public function doForgotPassword()
    {
        if (!isPost()) {
            $this->redirect('auth/forgot-password');
        }

        $this->requireCsrf();

        $email = $this->post('email');

        // Validation
        $errors = $this->validate([
            'email' => $email
        ], [
            'email' => 'required|email'
        ]);

        if (!empty($errors)) {
            flashOld($_POST);
            $this->setFlash('error', 'Email tidak valid');
            $this->redirect('auth/forgot-password');
        }

        // Generate reset token
        $token = Auth::generatePasswordResetToken($email);

        if ($token) {
            // In production, send email with reset link
            // For now, just show success message

            // Example reset link:
            $resetLink = url('auth/reset-password?token=' . $token);

            // TODO: Send email
            // Mail::send($email, 'Password Reset', $resetLink);

            $this->setFlash('success', 'Link reset password telah dikirim ke email Anda');
        } else {
            $this->setFlash('error', 'Email tidak ditemukan');
        }

        $this->redirect('auth/forgot-password');
    }

    /**
     * Show reset password form
     */
    public function resetPassword()
    {
        $token = $this->get('token');

        if (empty($token)) {
            $this->setFlash('error', 'Token tidak valid');
            $this->redirect('auth/forgot-password');
        }

        // Verify token
        $user = Auth::verifyPasswordResetToken($token);

        if (!$user) {
            $this->setFlash('error', 'Token tidak valid atau sudah kadaluarsa');
            $this->redirect('auth/forgot-password');
        }

        $data = [
            'title' => 'Reset Password - SIMRS',
            'token' => $token
        ];

        $this->view('auth/views/reset-password', $data, false);
    }

    /**
     * Process reset password
     */
    public function doResetPassword()
    {
        if (!isPost()) {
            $this->redirect('auth/forgot-password');
        }

        $this->requireCsrf();

        $token = $this->post('token');
        $password = $this->post('password');
        $passwordConfirm = $this->post('password_confirm');

        // Validation
        $errors = $this->validate([
            'password' => $password,
            'password_confirm' => $passwordConfirm
        ], [
            'password' => 'required|min:8',
            'password_confirm' => 'required|matches:password'
        ]);

        if (!empty($errors)) {
            flashOld($_POST);
            $this->setFlash('error', 'Password minimal 8 karakter dan harus sama');
            $this->redirect('auth/reset-password?token=' . $token);
        }

        // Reset password
        $result = Auth::resetPassword($token, $password);

        if ($result) {
            $this->setFlash('success', 'Password berhasil direset. Silakan login');
            $this->redirect('auth/login');
        } else {
            $this->setFlash('error', 'Token tidak valid atau sudah kadaluarsa');
            $this->redirect('auth/forgot-password');
        }
    }

    /**
     * Change password (for logged in users)
     */
    public function changePassword()
    {
        $this->requireAuth();

        $data = [
            'title' => 'Ubah Password - SIMRS'
        ];

        $this->view('auth/views/change-password', $data);
    }

    /**
     * Process change password
     */
    public function doChangePassword()
    {
        $this->requireAuth();

        if (!isPost()) {
            $this->redirect('auth/change-password');
        }

        $this->requireCsrf();

        $currentPassword = $this->post('current_password');
        $newPassword = $this->post('new_password');
        $newPasswordConfirm = $this->post('new_password_confirm');

        // Validation
        $errors = $this->validate([
            'current_password' => $currentPassword,
            'new_password' => $newPassword,
            'new_password_confirm' => $newPasswordConfirm
        ], [
            'current_password' => 'required',
            'new_password' => 'required|min:8',
            'new_password_confirm' => 'required|matches:new_password'
        ]);

        if (!empty($errors)) {
            $this->setFlash('error', 'Password tidak valid');
            $this->redirect('auth/change-password');
        }

        // Get current user
        $userId = Auth::id();
        $user = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);

        // Verify current password
        if (!Auth::verifyPassword($currentPassword, $user['password'])) {
            $this->setFlash('error', 'Password lama tidak sesuai');
            $this->redirect('auth/change-password');
        }

        // Update password
        $hashedPassword = Auth::hashPassword($newPassword);
        Database::update('users', [
            'password' => $hashedPassword,
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $userId]);

        // Log audit
        $this->logAudit('update', 'auth', 'users', $userId, 'Ubah password');

        $this->setFlash('success', 'Password berhasil diubah');
        $this->redirect('dashboard');
    }
}
