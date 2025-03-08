# EasyAxios PHP Library

Library PHP sederhana untuk melakukan HTTP request dengan mudah, terinspirasi oleh Axios JavaScript.

## Fitur

- ✨ Interface yang sederhana dan mudah digunakan
- 🔄 Mendukung semua HTTP method (GET, POST, PUT, DELETE, dll)
- 🛡️ Penanganan error yang baik
- 🔒 Konfigurasi SSL yang fleksibel
- 📦 Format response yang konsisten
- 🔄 Sistem retry otomatis
- ⚡ Interceptor untuk request dan response
- 📁 Dukungan untuk upload dan download file
- 🎯 Timeout dan header kustom

## Instalasi

1. Copy file `axios.php` dan `EasyAxios.php` ke project Anda
2. Include file EasyAxios di project Anda:
```php
require_once 'EasyAxios.php';
```

## Penggunaan Dasar

### Inisialisasi
```php
// Inisialisasi dengan base URL (opsional)
EasyAxios::create('https://api.example.com');

// Atau tanpa base URL
EasyAxios::create();
```

### GET Request
```php
// GET request sederhana
$response = EasyAxios::get('/users');

// GET dengan parameter
$response = EasyAxios::get('/users', [
    'page' => 1,
    'limit' => 10
]);
```

### POST Request
```php
// POST request dengan data
$response = EasyAxios::post('/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);
```

### PUT Request
```php
// PUT request untuk update data
$response = EasyAxios::put('/users/1', [
    'name' => 'John Updated'
]);
```

### DELETE Request
```php
// DELETE request
$response = EasyAxios::delete('/users/1');
```

## Fitur Lanjutan

### Konfigurasi SSL
```php
// Nonaktifkan SSL verification (untuk development)
EasyAxios::disableSSL();

// Aktifkan SSL verification (untuk production)
EasyAxios::enableSSL();
```

### Custom Headers
```php
// Tambahkan custom headers
EasyAxios::withHeaders([
    'Authorization: Bearer token123',
    'Custom-Header: Value'
])->get('/protected-endpoint');
```

### Token Authentication
```php
// Tambahkan Bearer token
EasyAxios::withToken('your-token')->get('/protected-endpoint');

// Atau dengan tipe token kustom
EasyAxios::withToken('your-token', 'Basic')->get('/protected-endpoint');
```

### Timeout
```php
// Set timeout dalam detik
EasyAxios::timeout(30)->get('/slow-endpoint');
```

### Retry Otomatis
```php
// Set jumlah retry maksimum
EasyAxios::retry(3)->get('/unreliable-endpoint');
```

### Upload File
```php
// Upload file sederhana
EasyAxios::upload('/upload', '/path/to/file.jpg');

// Upload dengan field name kustom dan data tambahan
EasyAxios::upload('/upload', '/path/to/file.jpg', 'custom_field', [
    'description' => 'My file'
]);
```

### Download File
```php
// Download file
EasyAxios::download('https://example.com/file.pdf', '/path/to/save/file.pdf');
```

### Response Lengkap
```php
// Dapatkan response lengkap termasuk headers dan info tambahan
$fullResponse = EasyAxios::getFullResponse()->get('/endpoint');
```

## Format Response

Response dari request akan memiliki format berikut:

```php
[
    'success' => true,          // Status request
    'data' => [...],           // Data response
    'http_code' => 200,        // HTTP status code
    'request_time' => 0.234,   // Waktu request dalam detik
    'info' => [...]           // Informasi tambahan tentang request
]
```

## Error Handling

Library ini menggunakan sistem Exception untuk error handling:

```php
try {
    $response = EasyAxios::get('/endpoint');
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Tips Penggunaan

1. Untuk development, gunakan `disableSSL()` untuk menghindari masalah sertifikat
2. Untuk production, pastikan SSL verification diaktifkan
3. Selalu gunakan try-catch untuk menangani error
4. Manfaatkan sistem retry untuk endpoint yang tidak stabil
5. Gunakan timeout yang sesuai dengan kebutuhan aplikasi

## Catatan Penting

- Library ini membutuhkan ekstensi PHP CURL
- Pastikan direktori memiliki permission yang tepat untuk operasi file
- Untuk production, selalu aktifkan SSL verification
- Response dalam format object untuk kemudahan penggunaan

## Kontribusi

Silakan buat issue atau pull request jika Anda menemukan bug atau ingin menambahkan fitur.

## Lisensi

MIT License - Silakan gunakan dan modifikasi sesuai kebutuhan Anda. 