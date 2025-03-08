<?php
require_once 'Axios.php';
class EasyAxios {
    private $axios;
    private static $instance = null;
    private $baseUrl;
    private static $sslVerification = true;

    // Singleton pattern for easier usage
    public static function create($baseUrl = null) {
        if (self::$instance === null || $baseUrl !== null) {
            self::$instance = new self($baseUrl);
        }
        return self::$instance;
    }

    private function __construct($baseUrl = null) {
        $this->baseUrl = $baseUrl;
        $this->axios = new Axios($baseUrl ?? '');
        
        // Set default SSL options - disable SSL verification for development
        $this->axios->addRequestInterceptor(function($endpoint, $method, $body, $headers) {
            curl_setopt_array($this->axios->getCurlHandle(), [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_CAINFO => null,
                CURLOPT_CAPATH => null
            ]);
            return [$endpoint, $method, $body, $headers];
        });
    }

    // Method untuk mengatur SSL verification
    public static function disableSSL() {
        self::$sslVerification = false;
        if (self::$instance !== null) {
            self::$instance->axios->addRequestInterceptor(function($endpoint, $method, $body, $headers) {
                curl_setopt_array(self::$instance->axios->getCurlHandle(), [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => 0
                ]);
                return [$endpoint, $method, $body, $headers];
            });
        }
        return self::$instance;
    }

    public static function enableSSL() {
        self::$sslVerification = true;
        if (self::$instance !== null) {
            self::$instance->axios->addRequestInterceptor(function($endpoint, $method, $body, $headers) {
                curl_setopt_array(self::$instance->axios->getCurlHandle(), [
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2
                ]);
                return [$endpoint, $method, $body, $headers];
            });
        }
        return self::$instance;
    }

    private function processResponse($response) {
        try {
            // Debug: print input response
            error_log("Processing response: " . print_r($response, true));
            
            // Jika response sudah berupa object, langsung kembalikan
            if (is_object($response)) {
                return $response;
            }
            
            // Jika response berupa string JSON, decode
            if (is_string($response) && $this->isJson($response)) {
                return json_decode($response);
            }
            
            // Jika response adalah array
            if (is_array($response)) {
                return json_decode(json_encode($response));
            }
            
            // Untuk kasus lainnya, kembalikan response apa adanya
            return $response;
            
        } catch (Exception $e) {
            error_log("Error processing response: " . $e->getMessage());
            throw $e;
        }
    }

    // Helper untuk mengecek string JSON valid
    private function isJson($string) {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    // Simple GET request method
    public static function get($url, $data = [], $headers = []) {
        if (self::$instance === null) {
            throw new Exception('Please call EasyAxios::create() first');
        }
        
        try {
            // Set response type to array dan dapatkan response
            $response = self::$instance->axios->asArray()->get($url, $data, $headers);
            
            // Debug: print response structure
            error_log("Raw Response Type: " . gettype($response));
            error_log("Raw Response: " . print_r($response, true));
            
            // Pastikan response valid dan sukses
            if (!is_array($response) || !isset($response['success']) || !$response['success']) {
                throw new Exception("Request failed: " . ($response['error'] ?? 'Unknown error'));
            }
            
            // Pastikan ada data dalam response
            if (!isset($response['data'])) {
                throw new Exception("No data in response");
            }
            
            // Ambil data dari response
            $responseData = $response['data'];
            
            // Debug: print response data
            error_log("Response Data Type: " . gettype($responseData));
            error_log("Response Data: " . print_r($responseData, true));
            
            // Konversi ke object dan kembalikan
            if (is_array($responseData)) {
                return json_decode(json_encode($responseData));
            } elseif (is_string($responseData)) {
                $decoded = json_decode($responseData);
                if ($decoded !== null) {
                    return $decoded;
                }
            }
            
            return $responseData;
            
        } catch (Exception $e) {
            error_log("Error in GET request: " . $e->getMessage());
            throw $e;
        }
    }

    // Simple POST request method
    public static function post($url, $data = [], $headers = []) {
        if (self::$instance === null) {
            throw new Exception('Please call EasyAxios::create() first');
        }
        $response = self::$instance->axios->post($url, $data, $headers);
        return self::$instance->processResponse($response);
    }

    // Simple PUT request method
    public static function put($url, $data = [], $headers = []) {
        if (self::$instance === null) {
            throw new Exception('Please call EasyAxios::create() first');
        }
        $response = self::$instance->axios->put($url, $data, $headers);
        return self::$instance->processResponse($response);
    }

    // Simple DELETE request method
    public static function delete($url, $headers = []) {
        if (self::$instance === null) {
            throw new Exception('Please call EasyAxios::create() first');
        }
        $response = self::$instance->axios->delete($url, $headers);
        return self::$instance->processResponse($response);
    }

    // Method to get full response with additional info
    public static function getFullResponse() {
        if (self::$instance === null) {
            throw new Exception('Please call EasyAxios::create() first');
        }
        return self::$instance->axios;
    }

    // Easy timeout configuration
    public static function timeout($seconds) {
        if (self::$instance === null) {
            throw new Exception('Please call EasyAxios::create() first');
        }
        self::$instance->axios->setTimeout($seconds);
        return self::$instance;
    }

    // Automatic retry configuration
    public static function retry($times = 3) {
        if (self::$instance === null) {
            throw new Exception('Please call EasyAxios::create() first');
        }
        self::$instance->axios->setRetry($times);
        return self::$instance;
    }

    // Easy header addition
    public static function withHeaders($headers) {
        if (self::$instance === null) {
            throw new Exception('Please call EasyAxios::create() first');
        }
        self::$instance->axios->addRequestInterceptor(function($endpoint, $method, $body, $existingHeaders) use ($headers) {
            return [$endpoint, $method, $body, array_merge($existingHeaders, $headers)];
        });
        return self::$instance;
    }

    // Easy token addition
    public static function withToken($token, $type = 'Bearer') {
        return self::withHeaders(['Authorization: ' . $type . ' ' . $token]);
    }

    // Easy file upload
    public static function upload($url, $filePath, $fileFieldName = 'file', $additionalData = []) {
        if (!file_exists($filePath)) {
            throw new Exception('File not found: ' . $filePath);
        }

        $data = $additionalData;
        $data[$fileFieldName] = new CURLFile($filePath);

        return self::post($url, $data);
    }

    // Easy file download
    public static function download($url, $savePath) {
        if (self::$instance === null) {
            throw new Exception('Please call EasyAxios::create() first');
        }
        
        try {
            // Buat instance baru khusus untuk download
            $downloadAxios = new Axios('');
            
            // Set opsi untuk download
            $downloadAxios->asRaw();
            
            // Tambahkan interceptor untuk SSL
            $downloadAxios->addRequestInterceptor(function($endpoint, $method, $body, $headers) {
                curl_setopt_array(self::$instance->axios->getCurlHandle(), [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => 0
                ]);
                return [$endpoint, $method, $body, $headers];
            });
            
            // Download file
            $response = $downloadAxios->get($url);
            
            // Jika response kosong
            if (empty($response)) {
                throw new Exception('Empty response received from server');
            }
            
            // Pastikan directory ada
            $dir = dirname($savePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            
            // Tulis file
            $result = file_put_contents($savePath, $response);
            if ($result === false) {
                throw new Exception('Failed to write file to: ' . $savePath);
            }
            
            // Verifikasi file telah dibuat
            if (!file_exists($savePath)) {
                throw new Exception('File was not created at: ' . $savePath);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error downloading file: " . $e->getMessage());
            // Hapus file jika ada error dan file sudah dibuat
            if (file_exists($savePath)) {
                unlink($savePath);
            }
            throw $e;
        }
    }
} 