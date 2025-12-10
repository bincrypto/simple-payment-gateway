<?php
$api_id = ""; // input api_id 
$api_key = ""; // input api_key 

$kode_bank = $_POST['kode_bank'] ?? null;
$nominal = $_POST['nominal'] ?? null;

if (!$kode_bank || !$nominal) {
    die("Semua field wajib diisi!");
}
$reff_id = 'INV' . substr(time(), -7) . rand(10, 99);

$signature = md5($api_id . $api_key . $reff_id);

$postData = [
    "api_id" => $api_id,
    "api_key" => $api_key,
    "signature" => $signature,
    "reff_id" => $reff_id,
    "kode_bank" => $kode_bank,
    "nominal" => (int) $nominal
];

$jsonData = json_encode($postData);

$ch = curl_init("https://topupku.com/api/payment");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Content-Length: " . strlen($jsonData)
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    die("Curl error: " . curl_error($ch));
}
curl_close($ch);

$result = json_decode($response, true);

$kodePembayaran = $result['data']['kode_pembayaran'] ?? null;
$kodeBank = strtoupper($kode_bank ?? '');

if ($kodePembayaran) {
    // Jika QRIS
    if (in_array($kodeBank, ["QRCODE", "SP"])) {
        echo "<h3>Scan QRIS untuk bayar</h3>";
        echo '<img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data='
            . urlencode($kodePembayaran) . '" alt="QRIS">';
    }
    // Jika e-wallet berupa link
    elseif (in_array($kodeBank, ["DA", "OV", "SA", "LA"])) {
        $namaMetode = [
            "DA" => "DANA",
            "OV" => "OVO",
            "SA" => "ShopeePay",
            "LA" => "linkaja"
        ];
        $metode = $namaMetode[$kodeBank] ?? "E-Wallet";

        echo "<h3>Bayar via $metode</h3>";
        echo '<a href="' . htmlspecialchars($kodePembayaran) . '" target="_blank" 
                 style="display:inline-block;padding:12px 20px;background:#007bff;
                        color:#fff;text-decoration:none;border-radius:8px;margin-top:10px;">
                 Bayar Sekarang
              </a>';
    }
} else {
    echo "=kode pembayaran tidak tersedia.";
}