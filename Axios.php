<?php

class Axios {
    private $baseUrl;
    private $defaultHeaders;
    private $timeout = 30;
    private $maxRetries = 0;
    private $retryDelay = 1000; // dalam milidetik
    private $requestInterceptors = [];
    private $responseInterceptors = [];
    private $responseType = 'array'; // Default response type
    private $curl;

    // Konstanta untuk tipe response
    const RESPONSE_TYPE_ARRAY = 'array';
    const RESPONSE_TYPE_OBJECT = 'object';
    const RESPONSE_TYPE_JSON = 'json';
    const RESPONSE_TYPE_RAW = 'raw';

    public function __construct($baseUrl, $defaultHeaders = []) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->defaultHeaders = array_merge([
            'Accept: application/json',
            'User-Agent: PHP-Axios/1.0'
        ], $defaultHeaders);
        
        // Initialize curl handle
        $this->curl = curl_init();
    }

    // Method untuk mendapatkan curl handle
    public function getCurlHandle() {
        return $this->curl;
    }

    // Method untuk mengatur tipe response
    public function setResponseType($type) {
        $validTypes = [self::RESPONSE_TYPE_ARRAY, self::RESPONSE_TYPE_OBJECT, self::RESPONSE_TYPE_JSON, self::RESPONSE_TYPE_RAW];
        if (!in_array($type, $validTypes)) {
            throw new InvalidArgumentException("Invalid response type. Valid types are: " . implode(', ', $validTypes));
        }
        $this->responseType = $type;
        return $this;
    }

    // Helper methods untuk mengatur tipe response
    public function asArray() {
        return $this->setResponseType(self::RESPONSE_TYPE_ARRAY);
    }

    public function asObject() {
        return $this->setResponseType(self::RESPONSE_TYPE_OBJECT);
    }

    public function asJson() {
        return $this->setResponseType(self::RESPONSE_TYPE_JSON);
    }

    public function asRaw() {
        return $this->setResponseType(self::RESPONSE_TYPE_RAW);
    }

    // Helper methods untuk HTTP verbs umum
    public function get($endpoint, $params = [], $headers = []) {
        if (!empty($params)) {
            $endpoint .= '?' . http_build_query($params);
        }
        return $this->request($endpoint, 'GET', null, $headers);
    }

    public function post($endpoint, $data = null, $headers = []) {
        return $this->request($endpoint, 'POST', $data, $headers);
    }

    public function put($endpoint, $data = null, $headers = []) {
        return $this->request($endpoint, 'PUT', $data, $headers);
    }

    public function patch($endpoint, $data = null, $headers = []) {
        return $this->request($endpoint, 'PATCH', $data, $headers);
    }

    public function delete($endpoint, $headers = []) {
        return $this->request($endpoint, 'DELETE', null, $headers);
    }

    // Setter untuk konfigurasi
    public function setTimeout($seconds) {
        $this->timeout = $seconds;
        return $this;
    }

    public function setRetry($maxRetries, $delayMs = 1000) {
        $this->maxRetries = $maxRetries;
        $this->retryDelay = $delayMs;
        return $this;
    }

    // Interceptors
    public function addRequestInterceptor($callback) {
        $this->requestInterceptors[] = $callback;
        return $this;
    }

    public function addResponseInterceptor($callback) {
        $this->responseInterceptors[] = $callback;
        return $this;
    }

    private function executeRequestInterceptors(&$endpoint, &$method, &$body, &$headers) {
        foreach ($this->requestInterceptors as $interceptor) {
            $result = $interceptor($endpoint, $method, $body, $headers);
            if (is_array($result)) {
                list($endpoint, $method, $body, $headers) = $result;
            }
        }
    }

    private function executeResponseInterceptors(&$response) {
        foreach ($this->responseInterceptors as $interceptor) {
            $response = $interceptor($response) ?? $response;
        }
    }

    public function request($endpoint, $method = 'GET', $body = null, $headers = []) {
        // Execute request interceptors
        $this->executeRequestInterceptors($endpoint, $method, $body, $headers);

        // Jika response type adalah RAW, langsung return response dari curl
        if ($this->responseType === self::RESPONSE_TYPE_RAW) {
            return $this->executeRawRequest($endpoint, $method, $body, $headers);
        }

        $retries = 0;
        do {
            $response = $this->executeRequest($endpoint, $method, $body, $headers);
            
            if ($response['success'] || $retries >= $this->maxRetries) {
                break;
            }

            usleep($this->retryDelay * 1000); // Convert to microseconds
            $retries++;
        } while ($retries < $this->maxRetries);

        // Execute response interceptors
        $this->executeResponseInterceptors($response);

        return $response;
    }

    private function executeRawRequest($endpoint, $method, $body, $headers) {
        // Handle absolute URLs
        $url = filter_var($endpoint, FILTER_VALIDATE_URL) 
            ? $endpoint 
            : $this->baseUrl . '/' . ltrim($endpoint, '/');

        $this->curl = curl_init();

        $method = strtoupper($method);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method);

        if ($body) {
            $jsonBody = is_string($body) ? $body : json_encode($body);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $jsonBody);
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: ' . strlen($jsonBody);
        }

        $headers = array_merge($this->defaultHeaders, $headers);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);

        // Basic settings
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, $this->timeout);
        
        // Security settings - default to false for development
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
        
        // Additional settings for better performance
        curl_setopt($this->curl, CURLOPT_TCP_FASTOPEN, true);
        curl_setopt($this->curl, CURLOPT_ENCODING, ''); // Accept all encodings

        $response = curl_exec($this->curl);
        
        if (curl_errno($this->curl)) {
            $error = curl_error($this->curl);
            curl_close($this->curl);
            throw new Exception($error);
        }

        curl_close($this->curl);
        return $response;
    }

    private function formatResponse($response, $httpCode, $error, $info, $startTime, $endTime) {
        $requestTime = $endTime - $startTime;
        
        if ($error) {
            return $this->formatErrorResponse($error, $httpCode, $info, $requestTime);
        }

        // Jika response type adalah RAW, langsung kembalikan response mentah
        if ($this->responseType === self::RESPONSE_TYPE_RAW) {
            return $response;
        }

        return $this->formatSuccessResponse($response, $httpCode, $info, $requestTime);
    }

    private function formatSuccessResponse($response, $httpCode, $info, $requestTime) {
        $baseResponse = [
            'success' => true,
            'http_code' => $httpCode,
            'request_time' => $requestTime,
            'info' => $info
        ];

        // Process response based on type
        switch ($this->responseType) {
            case self::RESPONSE_TYPE_ARRAY:
                $baseResponse['data'] = is_string($response) ? json_decode($response, true) : $response;
                break;
            
            case self::RESPONSE_TYPE_OBJECT:
                $baseResponse['data'] = is_string($response) ? json_decode($response) : $response;
                break;
            
            case self::RESPONSE_TYPE_JSON:
                return json_encode(array_merge($baseResponse, ['data' => is_string($response) ? json_decode($response, true) : $response]));
            
            default:
                $baseResponse['data'] = is_string($response) ? json_decode($response, true) : $response;
        }

        return $baseResponse;
    }

    private function formatErrorResponse($error, $httpCode, $info, $requestTime) {
        $errorResponse = [
            'success' => false,
            'error' => $error,
            'http_code' => $httpCode,
            'request_time' => $requestTime,
            'info' => $info
        ];

        switch ($this->responseType) {
            case self::RESPONSE_TYPE_JSON:
                return json_encode($errorResponse);
            case self::RESPONSE_TYPE_RAW:
                return $error;
            default:
                return $errorResponse;
        }
    }

    private function executeRequest($endpoint, $method, $body, $headers) {
        // Handle absolute URLs
        $url = filter_var($endpoint, FILTER_VALIDATE_URL) 
            ? $endpoint 
            : $this->baseUrl . '/' . ltrim($endpoint, '/');

        $this->curl = curl_init();

        $method = strtoupper($method);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method);

        if ($body) {
            $jsonBody = is_string($body) ? $body : json_encode($body);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $jsonBody);
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: ' . strlen($jsonBody);
        }

        $headers = array_merge($this->defaultHeaders, $headers);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);

        // Basic settings
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, $this->timeout);
        
        // Security settings - default to false for development
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
        
        // Additional settings for better performance
        curl_setopt($this->curl, CURLOPT_TCP_FASTOPEN, true);
        curl_setopt($this->curl, CURLOPT_ENCODING, ''); // Accept all encodings

        $startTime = microtime(true);
        $response = curl_exec($this->curl);
        $endTime = microtime(true);

        $httpCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        $error = curl_error($this->curl);
        $info = curl_getinfo($this->curl);
        curl_close($this->curl);

        return $this->formatResponse($response, $httpCode, $error, $info, $startTime, $endTime);
    }
}
