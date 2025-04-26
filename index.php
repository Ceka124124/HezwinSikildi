<?php
header('Content-Type: application/json');

$user_key = $_POST['user_key'] ?? null;
$uuid = $_POST['serial'] ?? null;

if (!$user_key || !$uuid) {
    exit(json_encode(["status" => false, "reason" => "Eksik bilgi gönderildi!"]));
}

$keys_file = 'keys.json';

if (!file_exists($keys_file)) {
    file_put_contents($keys_file, '{}');
}

$keys = json_decode(file_get_contents($keys_file), true);

// Key var mı kontrol
if (!isset($keys[$user_key])) {
    exit(json_encode(["status" => false, "reason" => "Key geçersiz!"]));
}

$key_data = $keys[$user_key];

// Süre kontrol
if (strtotime($key_data['expire_date']) < time()) {
    exit(json_encode(["status" => false, "reason" => "Key süresi dolmuş!"]));
}

// Cihaz limiti ve uuid kontrol
if (!in_array($uuid, $key_data['devices'])) {
    if (count($key_data['devices']) >= $key_data['max_devices']) {
        exit(json_encode(["status" => false, "reason" => "Cihaz limiti aşıldı!"]));
    } else {
        // Yeni cihazı kaydet
        $keys[$user_key]['devices'][] = $uuid;
        file_put_contents($keys_file, json_encode($keys, JSON_PRETTY_PRINT));
    }
}

// Token üret (basit MD5 ile örnek)
$secret = 'PUBG'; // oyun ismi ya da sabit bir gizli kelime
$static = 'Vm8Lk7Uj2JmsjCPVPVjrLa7zgfx3uz9E'; // sabit string (sen ayarla)

$auth_string = $secret . '-' . $user_key . '-' . $uuid . '-' . $static;
$generated_token = md5($auth_string);

// Token + RNG (timestamp göndereceğiz)
$response = [
    "status" => true,
    "data" => [
        "token" => $generated_token,
        "rng" => time()
    ]
];

echo json_encode($response);
?>
