<?php
/**
 * File: auth/oauth.php
 * Purpose: OAuth 2.0 redirect initiator for Google, Facebook, Twitter/X
 *
 * ── SETUP INSTRUCTIONS ───────────────────────────────────
 * 1. Create your app on each provider's developer console (links below).
 * 2. Set the Redirect URI on each provider to:
 *       http://localhost/finance-tracker/auth/oauth_callback.php
 * 3. Copy your Client ID & Secret into the $providers array below.
 * ─────────────────────────────────────────────────────────
 *
 * Provider developer consoles:
 *   Google:   https://console.cloud.google.com/apis/credentials
 *   Facebook: https://developers.facebook.com/apps/
 *   Twitter:  https://developer.twitter.com/en/portal/projects-and-apps
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/functions.php';

// ── Provider credentials ─────────────────────────────────
$providers = [
    'google' => [
        'client_id'     => 'YOUR_GOOGLE_CLIENT_ID',
        'client_secret' => 'YOUR_GOOGLE_CLIENT_SECRET',
        'auth_url'      => 'https://accounts.google.com/o/oauth2/v2/auth',
        'scope'         => 'openid email profile',
        'response_type' => 'code',
        // Official setup page — opened when credentials are missing
        'setup_url'     => 'https://console.cloud.google.com/apis/credentials',
    ],
    'facebook' => [
        'client_id'     => 'YOUR_FACEBOOK_APP_ID',
        'client_secret' => 'YOUR_FACEBOOK_APP_SECRET',
        'auth_url'      => 'https://www.facebook.com/v19.0/dialog/oauth',
        'scope'         => 'email,public_profile',
        'response_type' => 'code',
        'setup_url'     => 'https://developers.facebook.com/apps/',
    ],
    'twitter' => [
        'client_id'     => 'YOUR_TWITTER_CLIENT_ID',
        'client_secret' => 'YOUR_TWITTER_CLIENT_SECRET',
        'auth_url'      => 'https://twitter.com/i/oauth2/authorize',
        'scope'         => 'tweet.read users.read offline.access',
        'response_type' => 'code',
        'setup_url'     => 'https://developer.twitter.com/en/portal/projects-and-apps',
    ],
];

$provider = $_GET['provider'] ?? '';

if (!array_key_exists($provider, $providers)) {
    $_SESSION['oauth_notice'] = 'Invalid OAuth provider.';
    redirect(APP_URL . '/auth/login.php');
}

$cfg         = $providers[$provider];
$redirectUri = APP_URL . '/auth/oauth_callback.php';

// ── Credentials not configured → redirect to official setup page ──
if (str_starts_with($cfg['client_id'], 'YOUR_')) {
    // Navigate directly to the provider's official developer console
    header('Location: ' . $cfg['setup_url']);
    exit;
}

// ── CSRF state token ─────────────────────────────────────
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state']    = $state;
$_SESSION['oauth_provider'] = $provider;

// ── Build authorization URL ──────────────────────────────
$params = [
    'client_id'     => $cfg['client_id'],
    'redirect_uri'  => $redirectUri,
    'response_type' => $cfg['response_type'],
    'scope'         => $cfg['scope'],
    'state'         => $state,
];

// Twitter requires PKCE
if ($provider === 'twitter') {
    $verifier  = bin2hex(random_bytes(32));
    $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    $_SESSION['oauth_pkce_verifier'] = $verifier;
    $params['code_challenge']        = $challenge;
    $params['code_challenge_method'] = 'S256';
}

$authUrl = $cfg['auth_url'] . '?' . http_build_query($params);

header('Location: ' . $authUrl);
exit;
