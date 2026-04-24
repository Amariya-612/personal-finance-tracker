<?php
/**
 * File: controllers/AuthController.php
 * Purpose: Handle login, registration, and logout business logic
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/validator.php';
require_once __DIR__ . '/../utils/functions.php';

class AuthController
{
    private User $userModel;

    public function __construct(PDO $pdo)
    {
        $this->userModel = new User($pdo);
    }

    /**
     * Process login — accepts username.
     */
    public function login(array $post): array
    {
        if (!verifyCsrf($post['csrf_token'] ?? '')) {
            return ['success' => false, 'errors' => ['general' => 'Invalid request. Please try again.']];
        }

        $v = new Validator($post);
        $v->required('username', 'Username')
          ->required('password', 'Password');

        if ($v->fails()) {
            return ['success' => false, 'errors' => $v->errors()];
        }

        $user = $this->userModel->findByUsername($post['username']);

        if (!$user || !$this->userModel->verifyPassword($post['password'], $user['password'])) {
            return ['success' => false, 'errors' => ['general' => 'Invalid username or password.']];
        }

        session_regenerate_id(true);

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['email']     = $user['email'];
        $_SESSION['currency']  = $user['currency'];
        $_SESSION['avatar']    = $user['avatar'] ?? null;

        return ['success' => true, 'errors' => []];
    }

    /**
     * Process registration — requires username field.
     */
    public function register(array $post): array
    {
        if (!verifyCsrf($post['csrf_token'] ?? '')) {
            return ['success' => false, 'errors' => ['general' => 'Invalid request. Please try again.']];
        }

        $v = new Validator($post);
        $v->required('name',             'Full Name')
          ->maxLength('name', 100,        'Full Name')
          ->required('username',          'Username')
          ->maxLength('username', 60,     'Username')
          ->required('email',             'Email')
          ->email('email',                'Email')
          ->required('password',          'Password')
          ->minLength('password', 8,      'Password')
          ->required('confirm_password',  'Confirm Password')
          ->matches('confirm_password', 'password', 'Passwords')
          ->inList('currency', CURRENCIES, 'Currency');

        if ($v->fails()) {
            return ['success' => false, 'errors' => $v->errors()];
        }

        if ($this->userModel->usernameExists($post['username'])) {
            return ['success' => false, 'errors' => ['username' => 'This username is already taken.']];
        }

        if ($this->userModel->emailExists($post['email'])) {
            return ['success' => false, 'errors' => ['email' => 'This email is already registered.']];
        }

        $currency = in_array($post['currency'] ?? '', CURRENCIES) ? $post['currency'] : 'ETB';
        $this->userModel->create($post['name'], $post['username'], $post['email'], $post['password'], $currency);

        return ['success' => true, 'errors' => []];
    }

    /** Destroy the session and log the user out. */
    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
