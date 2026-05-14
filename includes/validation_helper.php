<?php
/**
 * Global Input Validation & Sanitization Helper
 */

/**
 * Sanitize input to prevent XSS
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate Email Format
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate Password Strength (Min 8 chars, 1 letter, 1 number)
 */
function validate_password($password) {
    if (strlen($password) < 8) return false;
    if (!preg_match('/[A-Za-z]/', $password)) return false;
    if (!preg_match('/[0-9]/', $password)) return false;
    return true;
}

/**
 * Validate Phone Number (Numeric only)
 */
function validate_phone($phone) {
    return preg_match('/^[0-9]+$/', $phone);
}

/**
 * Validate Date Format (YYYY-MM-DD)
 */
function validate_date($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Validate Number Range
 */
function validate_range($num, $min, $max) {
    return is_numeric($num) && $num >= $min && $num <= $max;
}

/**
 * Collect and display validation errors
 */
function format_errors($errors) {
    if (empty($errors)) return "";
    $output = '<div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 12px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #f43f5e;">';
    $output .= '<p style="margin-bottom: 10px;"><i class="fas fa-exclamation-triangle"></i> Please correct the following errors:</p>';
    $output .= '<ul style="margin-left: 25px; font-size: 0.9rem;">';
    foreach ($errors as $e) {
        $output .= '<li>' . $e . '</li>';
    }
    $output .= '</ul></div>';
    return $output;
}
?>
