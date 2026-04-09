<?php
function createSession($username, $role) {
    $_SESSION['user'] = $username;
    $_SESSION['role'] = $role;
    $_SESSION['login_time'] = time();
}

function createJWT($username, $role) {
    // Define a secret key to sign the token (Keep this safe!)
    $secret_key = 'ams_super_secret_signature_key';
    
    // 1. Create token header (algorithm & token type)
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    
    // 2. Create token payload (user data & expiration time)
    $payload = json_encode([
        'user' => $username,
        'role' => $role,
        'iat'  => time(),                      // Issued at time
        'exp'  => time() + (60 * 60 * 2)       // Expires in 2 hours
    ]);
    
    // 3. Encode Header & Payload to Base64Url
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

    // 4. Create Security Signature
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret_key, true);
    
    // 5. Encode Signature to Base64Url
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    // 6. Combine all three pieces to form the final JWT Token!
    $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

    // Optional: Store the JWT inside a secured HttpOnly cookie right away
    setcookie("jwt_token", $jwt, time() + (60 * 60 * 2), "/", "", false, true);

    return $jwt;
}
?>
