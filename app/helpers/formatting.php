<?php
// app/helpers/formatting.php

function format_currency(float|int|string|null $amount, bool $withSymbol = true): string {
    $num = (float)($amount ?? 0);
    $formatted = number_format($num, 0, '.', ',');
    return $withSymbol ? "TZS {$formatted}" : $formatted;
}

function format_compact_currency(float|int|string|null $amount): string {
    $num = (float)($amount ?? 0);
    if ($num >= 1000000000) {
        return 'TZS ' . round($num / 1000000000, 1) . 'B';
    }
    if ($num >= 1000000) {
        return 'TZS ' . round($num / 1000000, 1) . 'M';
    }
    if ($num >= 1000) {
        return 'TZS ' . round($num / 1000, 0) . 'K';
    }
    return 'TZS ' . number_format($num, 0);
}

function format_date(?string $dateStr): string {
    if (!$dateStr) return 'N/A';
    $time = strtotime($dateStr);
    return date('d M Y', $time);
}

function format_datetime(?string $dateStr): string {
    if (!$dateStr) return 'N/A';
    $time = strtotime($dateStr);
    return date('d M Y, H:i', $time);
}

function format_phone(string $phone): string {
    $digits = preg_replace('/\D/', '', $phone);
    if (str_starts_with($digits, '255')) {
        $local = substr($digits, 3);
    } else {
        $local = ltrim($digits, '0');
    }
    if (strlen($local) !== 9) return $phone;
    return "+255 " . substr($local, 0, 3) . " " . substr($local, 3, 3) . " " . substr($local, 6);
}

function get_initials(string $name): string {
    $words = explode(' ', trim($name));
    $in = '';
    foreach (array_slice($words, 0, 2) as $w) {
        if (!empty($w)) {
            $in .= strtoupper($w[0]);
        }
    }
    return $in ?: '?';
}

/**
 * Decode Google Plus Code / Open Location Code to [lat, lon]
 */
function decode_plus_code(string $code): ?array {
    $code = strtoupper(trim($code));
    // Remove reference locations if passed (e.g., "6756+9F DAR ES SALAAM" -> "6756+9F")
    $parts = preg_split('/\s+/', $code);
    $cleanCode = $parts[0] ?? '';

    // If coordinates format directly passed (e.g., "-6.823512, 39.269504")
    if (preg_match('/^(-?\d+\.\d+),\s*(-?\d+\.\d+)$/', $code, $m)) {
        return ['lat' => (float)$m[1], 'lon' => (float)$m[2]];
    }

    $codeAlphabet = "23456789CFGHJMPQRVWX";
    $paddingChar = '0';
    $separatorChar = '+';

    if (strpos($cleanCode, $separatorChar) === false) {
        return null;
    }

    $cleanCode = str_replace($separatorChar, '', $cleanCode);
    $cleanCode = rtrim($cleanCode, $paddingChar);

    if (strlen($cleanCode) < 2) return null;

    $latVal = 0.0;
    $lonVal = 0.0;
    $gridSize = 20.0;

    $latPlaceValues = [20.0, 1.0, 0.05, 0.0025, 0.000125];
    $lonPlaceValues = [20.0, 1.0, 0.05, 0.0025, 0.000125];

    $len = min(strlen($cleanCode), 10);
    for ($i = 0; $i < $len; $i += 2) {
        $c1 = $cleanCode[$i];
        $c2 = $cleanCode[$i + 1] ?? '2';

        $p1 = strpos($codeAlphabet, $c1);
        $p2 = strpos($codeAlphabet, $c2);

        if ($p1 === false || $p2 === false) return null;

        $idx = (int)($i / 2);
        if ($idx < count($latPlaceValues)) {
            $latVal += $p1 * $latPlaceValues[$idx];
            $lonVal += $p2 * $lonPlaceValues[$idx];
        }
    }

    // Convert from OLC origin (-90, -180)
    $lat = $latVal - 90.0;
    $lon = $lonVal - 180.0;

    // Adjust for Dar es Salaam / Tanzania sector if short code without full prefix
    if ($lat > 0 && $lon < 0) {
        $lat = -$lat;
        $lon = -$lon;
    }

    return [
        'lat' => round($lat, 6),
        'lon' => round($lon, 6)
    ];
}
