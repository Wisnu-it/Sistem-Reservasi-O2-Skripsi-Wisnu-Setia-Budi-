<?php
/**
 * File: config/midtrans.php
 * Deskripsi: Konfigurasi API Midtrans Gateway (Sandbox Mode)
 */

// Kredensial Midtrans Sandbox milik O2 Futsal
define('MIDTRANS_SERVER_KEY', 'ISI_DENGAN_SERVER_KEY_ANDA');
define('MIDTRANS_CLIENT_KEY', 'ISI_DENGAN_SERVER_KEY_ANDA');
define('MIDTRANS_IS_PRODUCTION', false); // false = Sandbox (Mode Uji Coba)

/**
 * Fungsi dasar untuk memanggil API Midtrans (Request Token)
 * Kita menggunakan cURL bawaan PHP agar tidak perlu menginstal library eksternal (Composer) yang berat.
 */
function getMidtransSnapToken($order_id, $gross_amount, $customer_details, $item_details) {
    $url = MIDTRANS_IS_PRODUCTION ? 
           'https://app.midtrans.com/snap/v1/transactions' : 
           'https://app.sandbox.midtrans.com/snap/v1/transactions';

    $server_key = MIDTRANS_SERVER_KEY;

    $data = [
        'transaction_details' => [
            'order_id' => $order_id,
            'gross_amount' => (int) $gross_amount,
        ],
        'customer_details' => $customer_details,
        'item_details' => $item_details
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($server_key . ':')
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 201) {
        $result = json_decode($response, true);
        return $result['token'];
    } else {
        // Jika gagal, kembalikan pesan error untuk debugging
        return false;
    }
}
?>