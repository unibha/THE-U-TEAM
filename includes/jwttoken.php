<?php

function createSession($username, $role, $user_id = null) {
    $_SESSION['user'] = $username;
    $_SESSION['role'] = $role;
    $_SESSION['user_id'] = $user_id;
    $_SESSION['login_time'] = time();
}

/**
 * Generates a JSON Web Token (JWT) manually without external libraries
 */
function createJWT($username, $role, $user_id = null, $permissions = []) {
    // Define a secret key to sign the token (Keep this safe!)
    $secret_key = 'ams_super_secret_signature_key';
    
    // 1. Create token header (algorithm & token type)
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    
    // 2. Create token payload (user data & expiration time)
    $payload = json_encode([
        'user' => $username,
        'role' => $role,
        'user_id' => $user_id,
        'permissions' => $permissions,
        'iat'  => time(),                      // Issued at time
        'exp'  => time() + (60 * 60 * 8),      // Expires in 8 hours for API usage
        'iss'  => 'ams_system',                // Issuer
        'aud'  => 'ams_users'                  // Audience
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
    setcookie("jwt_token", $jwt, time() + (60 * 60 * 8), "/", "", false, true);

    return $jwt;
}

/**
 * Validates and decodes JWT token
 */
function validateJWT($token = null) {
    $secret_key = 'ams_super_secret_signature_key';
    
    // Get token from header or cookie
    if (!$token) {
        $token = $_COOKIE['jwt_token'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        if ($token && strpos($token, 'Bearer ') === 0) {
            $token = substr($token, 7);
        }
    }
    
    if (!$token) {
        return null;
    }
    
    // Split token into parts
    $tokenParts = explode('.', $token);
    if (count($tokenParts) !== 3) {
        return null;
    }
    
    list($header, $payload, $signature) = $tokenParts;
    
    // Verify signature
    $validSignature = hash_hmac('sha256', $header . "." . $payload, $secret_key, true);
    $validSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($validSignature));
    
    if ($signature !== $validSignature) {
        return null;
    }
    
    // Decode payload
    $payloadData = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
    
    // Check expiration
    if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
        return null;
    }
    
    return $payloadData;
}

/**
 * Check if user has specific permission
 */
function hasPermission($permission, $user = null) {
    if (!$user) {
        $user = validateJWT();
    }
    
    if (!$user) {
        return false;
    }
    
    // Admin has all permissions
    if ($user['role'] === 'Admin') {
        return true;
    }
    
    // Check role-based permissions
    $rolePermissions = [
        'Teacher' => ['create_exam', 'manage_marks', 'view_students', 'create_notice'],
        'Student' => ['view_exams', 'view_marks', 'view_notices']
    ];
    
    $userPermissions = $user['permissions'] ?? $rolePermissions[$user['role']] ?? [];
    
    return in_array($permission, $userPermissions);
}

/**
 * Get user permissions based on role
 */
function getUserPermissions($role) {
    $permissions = [
        'Admin' => ['create_user', 'manage_users', 'create_course', 'manage_courses', 'create_exam', 'manage_marks', 'view_students', 'create_notice', 'manage_notices', 'view_reports'],
        'Teacher' => ['create_exam', 'manage_marks', 'view_students', 'create_notice', 'view_attendance', 'manage_attendance'],
        'Student' => ['view_exams', 'view_marks', 'view_notices', 'view_attendance', 'submit_assignment']
    ];
    
    return $permissions[$role] ?? [];
}
?>
