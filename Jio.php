<?php

// Channel ID from URL (example: ?id=123)
$id = @$_GET['id'];
if (!$id) {
    die("Missing channel ID.");
}

// Configuration
$user_ip = $_SERVER['REMOTE_ADDR'];
$portal = "jiotv.be";
$mac = "00:1A:79:00:00:66";
$deviceid = "47F3A273E26F05402CDA481556671D04EBA4A0257CF0AAB8C7FE0DDA82960FEB"; // Your device ID
$serial = "5786291F7E177";

// Step 1: Handshake
$handshake_url = "http://$portal/stalker_portal/server/load.php?type=stb&action=handshake&prehash=false&JsHttpRequest=1-xml";

$headers = [
    "Cookie: mac=$mac; stb_lang=en; timezone=GMT",
    "X-Forwarded-For: $user_ip",
    "Referer: http://$portal/stalker_portal/c/",
    "User-Agent: Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 250 Safari/533.3",
    "X-User-Agent: Model: MAG250; Link:",
];

$ch1 = curl_init();
curl_setopt_array($ch1, [
    CURLOPT_URL => $handshake_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$res1 = curl_exec($ch1);
curl_close($ch1);

$data1 = json_decode($res1, true);
if (!isset($data1['js']['token'], $data1['js']['random'])) {
    die("Handshake failed.\n$res1");
}
$token = $data1['js']['token'];
$random = $data1['js']['random'];

// Step 2: Get Profile
$headers[] = "Authorization: Bearer $token";
$timestamp = time();
$metrics = urlencode(json_encode([
    "mac" => $mac,
    "sn" => $serial,
    "model" => "MAG254",
    "type" => "STB",
    "uid" => $deviceid,
    "random" => $random
]));

$profile_url = "http://$portal/stalker_portal/server/load.php?type=stb&action=get_profile"
    . "&hd=1"
    . "&ver=ImageDescription%3A%200.2.18-r14-pub-250"
    . "&num_banks=2"
    . "&sn=$serial"
    . "&stb_type=MAG270"
    . "&image_version=218"
    . "&video_out=hdmi"
    . "&device_id=$deviceid"
    . "&device_id2=$deviceid"
    . "&signature="
    . "&auth_second_step=1"
    . "&hw_version=1.7-BD-00"
    . "&not_valid_token=0"
    . "&client_type=STB"
    . "&hw_version_2=7ec5a49802e4a011344ed3049250f50d"
    . "&timestamp=$timestamp"
    . "&api_signature=263"
    . "&metrics=$metrics"
    . "&JsHttpRequest=1-xml";

$ch2 = curl_init();
curl_setopt_array($ch2, [
    CURLOPT_URL => $profile_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$res2 = curl_exec($ch2);
curl_close($ch2);

// Step 3: Get Stream Link
$cmd = "ffrt http://localhost/ch/$id";
$cmd_encoded = urlencode($cmd);
$stream_url = "http://$portal/stalker_portal/server/load.php?type=itv&action=create_link&cmd=$cmd_encoded&JsHttpRequest=1-xml";

$ch3 = curl_init();
curl_setopt_array($ch3, [
    CURLOPT_URL => $stream_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$res3 = curl_exec($ch3);
curl_close($ch3);

$data3 = json_decode($res3, true);
if (!isset($data3['js']['cmd'])) {
    die("Failed to get stream URL.\n$res3");
}

$final_stream = $data3['js']['cmd'];

// Redirect to stream URL
header("Location: $final_stream");
exit;
