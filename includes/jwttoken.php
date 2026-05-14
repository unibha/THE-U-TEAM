<?php
/**
 * JWT Utility - Manages JSON Web Tokens for Academic Management System
 */

define('JWT_SECRET', 'ams_super_secret_signature_key');

/**
 * Generates a JSON Web Token (JWT)
 */
function createJWT($userId, $username, $role) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    
    $payload = json_encode([
        'user_id' => $userId,
        'username' => $username,
        'role' => $role,
        'iat'  => time(),                      // Issued at time
        'exp'  => time() + (60 * 60 * 2)       // Expires in 2 hours
    ]);
    
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

/**
 * Validates a JWT Token
 * Returns the decoded payload if valid, false otherwise
 */
function validateJWT($jwt) {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return false;

    list($header, $payload, $signature) = $parts;

    // Verify Signature
    $validSig = hash_hmac('sha256', "$header.$payload", JWT_SECRET, true);
    $validSigEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($validSig));

    if ($signature !== $validSigEncoded) return false;

    // Decode Payload
    $data = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);

    // Check Expiration
    if (isset($data['exp']) && $data['exp'] < time()) {
        return 'expired';
    }

    return $data;
}

/**
 * Refresh JWT if near expiry (e.g. less than 30 mins left)
 */
function refreshTokenIfNeeded($jwt) {
    $data = validateJWT($jwt);
    if ($data && is_array($data)) {
        $timeLeft = $data['exp'] - time();
        if ($timeLeft < (30 * 60)) { // 30 mins
            return createJWT($data['user_id'], $data['username'], $data['role']);
        }
    }
    return $jwt;
}
?>
