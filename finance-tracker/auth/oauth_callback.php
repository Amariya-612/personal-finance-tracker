<?php
/**
 * File: auth/oauth_callback.php
 * Purpose: OAuth 2.0 callback — exchange code for token, fetch user profile,
 *          then log in or register the user automatically.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/functions.php';
require_once __DIR__ . '/../models/User.php';

// ── Validate state (CSRF) ────────────────────────────────
$state    = $_GET['state']    ?? '';
$code     = $_GET['code']     ?? '';
$provider = $_SESSION['oauth_provider'] ?? '';

if (!$state || $state !== ($_SESSION['oauth_state'] ?? '')) {
    $_SESSION['oauth_notice'] = 'OAuth state mismatch. Please try again.';
    redirect(APP_URL . '/auth/login.php');
}

unset($_SESSION['oauth_state'], $_SESSION['oauth_provider']);

if (!$code) {
    $_SESSION['oauth_notice'] = 'OAuth authorisation was cancelled or failed.';
    redirect(APP_URL . '/auth/login.php');
}

// ── Provider token endpoints & profile endpoints ─────────
$providerConfig = [
    'google' => [
        'client_id'     => 'YOUR_GOOGLE_CLIENT_ID',
        'client_secret' => 'YOUR_GOOGLE_CLIENT_SECRET',
        'token_url'     => 'https://oauth2.googleapis.com/token',
        'profile_url'   => 'https://www.googleapis.com/oauth2/v3/userinfo',
        'name_field'    => 'name',
        'email_field'   => 'email',
    ],
    'facebook' => [
        'client_id'     => 'YOUR_FACEBOOK_APP_ID',
        'client_secret' => 'YOUR_FACEBOOK_APP_SECRET',
        'token_url'     => 'https://graph.facebook.com/v19.0/oauth/access_token',
        'profile_url'   => 'https://graph.facebook.com/me?fields=id,name,email',
        'name_field'    => 'name',
        'email_field'   => 'email',
    ],
    'twitter' => [
        'client_id'     => 'YOUR_TWITTER_CLIENT_ID',
        'client_secret' => 'YOUR_TWITTER_CLIENT_SECRET',
        'token_url'     => 'https://api.twitter.com/2/oauth2/token',
        'profile_url'   => 'https://api.twitter.com/2/users/me?user.fields=name,username',
        'name_field'    => 'name',
        'email_field'   => null, // Twitter v2 basic scope has no email
    ],
];

if (!isset($providerConfig[$provider])) {
    $_SESSION['oauth_notice'] = 'Unknown OAuth provider.';
    redirect(APP_URL . '/auth/login.php');
}

$cfg         = $providerConfig[$provider];
$redirectUri = APP_URL . '/auth/oauth_callback.php';

// ── Exchange code for access token ───────────────────────
$tokenParams = [
    'grant_type'   => 'authorization_code',
    'code'         => $code,
    'redirect_uri' => $redirectUri,
    'client_id'    => $cfg['client_id'],
    'client_secret'=> $cfg['client_secret'],
];

// Twitter PKCE
if ($provider === 'twitter' && !empty($_SESSION['oauth_pkce_verifier'])) {
    $tokenParams['code_verifier'] = $_SESSION['oauth_pkce_verifier'];
    unset($_SESSION['oauth_pkce_verifier']);
}

$tokenResponse = oauthPost($cfg['token_url'], $tokenParams, $provider);

if (empty($tokenResponse['access_token'])) {
    $_SESSION['oauth_notice'] = 'Failed to obtain access token from ' . ucfirst($provider) . '.';
    redirect(APP_URL . '/auth/login.php');
}

$accessToken = $tokenResponse['access_token'];

// ── Fetch user profile ────────────────────────────────────
$profile = oauthGet($cfg['profile_url'], $accessToken);

// Twitter wraps data in a 'data' key
if ($provider === 'twitter' && isset($profile['data'])) {
    $profile = $profile['data'];
}

$oauthName  = $profile[$cfg['name_field']] ?? '';
$oauthEmail = $cfg['email_field'] ? ($profile[$cfg['email_field']] ?? '') : '';

// Twitter has no email — use a placeholder so the account can be created
if (!$oauthEmail) {
    $oauthEmail = strtolower(str_replace(' ', '.', $oauthName))
                  . '+' . $provider . '@oauth.placeholder';
}

if (!$oauthName || !$oauthEmail) {
    $_SESSION['oauth_notice'] = 'Could not retrieve profile from ' . ucfirst($provider) . '.';
    redirect(APP_URL . '/auth/login.php');
}

// ── Find or create user ───────────────────────────────────
$userModel = new User($pdo);
$user      = $userModel->findByEmail($oauthEmail);

if (!$user) {
    // Auto-register with a random secure password (user can never log in with it directly)
    $randomPassword = bin2hex(random_bytes(16));
    $userId = $userModel->create($oauthName, $oauthEmail, $randomPassword, 'USD');

    if (!$userId) {
        $_SESSION['oauth_notice'] = 'Account creation failed. Please try again.';
        redirect(APP_URL . '/auth/login.php');
    }

    $user = $userModel->findById($userId);
}

// ── Start session ─────────────────────────────────────────
session_regenerate_id(true);
$_SESSION['user_id']   = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['email']     = $user['email'];
$_SESSION['currency']  = $user['currency'];

redirect(APP_URL . '/dashboard/index.php');

// ── Helpers ───────────────────────────────────────────────

/**
 * POST request to OAuth token endpoint.
 */
function oauthPost(string $url, array $params, string $provider): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($params),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    // Twitter requires Basic Auth for token exchange
    if ($provider === 'twitter') {
        curl_setopt($ch, CURLOPT_USERPWD,
            $params['client_id'] . ':' . $params['client_secret']);
    }

    $body = curl_exec($ch);
    curl_close($ch);

    return json_decode($body ?: '{}', true) ?? [];
}

/**
 * GET request to OAuth profile endpoint with Bearer token.
 */
function oauthGet(string $url, string $token): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $body = curl_exec($ch);
    curl_close($ch);

    return json_decode($body ?: '{}', true) ?? [];
}
