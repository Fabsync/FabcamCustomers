<?php

class AuthController extends Controller {

    public function showLogin(): void {
        if (isLoggedIn()) {
            $this->redirect('/dashboard');
        }
        $this->render('auth/login', ['pageTitle' => 'Login'], 'auth');
    }

    public function processLogin(): void {
        $this->validateCsrf();

        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');
        $errors   = [];

        if (!$email || !$password) {
            $errors[] = 'Email and password are required.';
        }

        $ip      = LoginGuard::clientIp();
        $tooMany = 'Too many login attempts. Please try again in ' . LoginGuard::LOCK_MINUTES . ' minutes.';

        if (!$errors && LoginGuard::ipBlocked($ip)) {
            http_response_code(429);
            $errors[] = $tooMany;
        }

        if (!$errors) {
            $model  = new UserModel();
            $user   = $model->findByEmail($email);
            $userId = $user ? (int) $user['id'] : null;

            if (LoginGuard::isLocked($userId, $email)) {
                LoginGuard::log($email, $ip, false);
                http_response_code(429);
                $errors[] = $tooMany;
            }
        }

        if (!$errors) {
            // Always run password_verify (dummy hash for unknown emails) so the
            // response time doesn't reveal which emails exist.
            $ok = password_verify($password, $user['password_hash'] ?? LoginGuard::DUMMY_HASH) && $user;
            LoginGuard::log($email, $ip, (bool) $ok);

            if ($ok) {
                LoginGuard::success($userId, $ip);
                session_regenerate_id(true);
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user']      = [
                    'id'   => $user['id'],
                    'name' => $user['name'],
                    'role' => $user['role'],
                ];
                $model->updateLastLogin($user['id']);
                $this->redirect('/dashboard');
            } else {
                if ($userId !== null) {
                    LoginGuard::fail($userId);
                }
                $errors[] = 'Invalid email or password.';
            }
        }

        $this->render('auth/login', [
            'pageTitle' => 'Login',
            'errors'    => $errors,
            'email'     => $email,
        ], 'auth');
    }

    public function logout(): void {
        session_destroy();
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
}
