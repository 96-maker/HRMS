<?php
// app/helpers/sanitize.php

function sanitize_string(?string $value): string {
    if ($value === null) return '';
    return trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
}

function sanitize_email(?string $email): string {
    if (!$email) return '';
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

function process_env(string $key, $default = null) {
    $val = getenv($key);
    return $val !== false ? $val : $default;
}
