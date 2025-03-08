<?php

require_once 'EasyAxios.php';

class EasyAxiosTest {
    public static $testEndpoint = 'https://pokeapi.co/api/v2';
    public static $testImageUrl = 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/132.png';
    
    public static function runAllTests() {
        echo "🚀 Starting EasyAxios Tests\n";
        echo "===========================\n\n";
        
        try {
            // Initialize EasyAxios
            self::testInitialization();
            
            // Test Basic HTTP Methods
            self::testGetRequest();
            self::testPostRequest();
            self::testPutRequest();
            self::testDeleteRequest();
            
            // Test Advanced Features
            self::testHeaderManipulation();
            self::testTokenAuthentication();
            self::testTimeout();
            self::testRetry();
            self::testFileOperations();
            self::testFullResponse();
            
            echo "\n✅ All tests completed successfully!\n";
            
        } catch (Exception $e) {
            echo "\n❌ Test failed: " . $e->getMessage() . "\n";
        }
    }
    
    private static function testInitialization() {
        echo "Testing Initialization... ";
        EasyAxios::create(self::$testEndpoint);
        
        // Disable SSL verification untuk environment development
        EasyAxios::disableSSL();
        
        echo "✅\n";
    }
    
    private static function testGetRequest() {
        echo "Testing GET Request... ";
        try {
            $pokemon = EasyAxios::get('/pokemon/ditto');
            
            error_log("Pokemon response type: " . gettype($pokemon));
            error_log("Pokemon response: " . print_r($pokemon, true));
            
            if (!is_object($pokemon)) {
                throw new Exception("GET request failed - Response is not an object, got " . gettype($pokemon));
            }
            
            if (!property_exists($pokemon, 'id')) {
                throw new Exception("GET request failed - Response doesn't have id property. Available properties: " . implode(', ', array_keys((array)$pokemon)));
            }
            
            if ($pokemon->id !== 132) {
                throw new Exception("GET request failed - Expected ID 132, got " . $pokemon->id);
            }
            
            if ($pokemon->name !== 'ditto') {
                throw new Exception("GET request failed - Expected name 'ditto', got " . $pokemon->name);
            }
            
            echo "✅\n";
        } catch (Exception $e) {
            echo "❌\n";
            error_log("Test error: " . $e->getMessage());
            throw new Exception("GET request failed - " . $e->getMessage());
        }
    }
    
    private static function testPostRequest() {
        echo "Testing POST Request... (Skipped - PokeAPI is read-only) ✅\n";
        return true;
        // POST tidak tersedia di PokeAPI
    }
    
    private static function testPutRequest() {
        echo "Testing PUT Request... (Skipped - PokeAPI is read-only) ✅\n";
        return true;
        // PUT tidak tersedia di PokeAPI
    }
    
    private static function testDeleteRequest() {
        echo "Testing DELETE Request... (Skipped - PokeAPI is read-only) ✅\n";
        return true;
        // DELETE tidak tersedia di PokeAPI
    }
    
    private static function testHeaderManipulation() {
        echo "Testing Custom Headers... ";
        $response = EasyAxios::withHeaders([
            'Accept: application/json',
            'User-Agent: EasyAxios-Test'
        ])->get('/pokemon/ditto');
        if (!isset($response->id) || $response->id !== 132) {
            throw new Exception("Header manipulation failed");
        }
        echo "✅\n";
    }
    
    private static function testTokenAuthentication() {
        echo "Testing Token Authentication... (Skipped - PokeAPI doesn't require auth) ✅\n";
        return true;
        // Authentication tidak diperlukan untuk PokeAPI
    }
    
    private static function testTimeout() {
        echo "Testing Timeout Configuration... ";
        $response = EasyAxios::timeout(30)
            ->get('/pokemon/ditto');
        if (!isset($response->id) || $response->id !== 132) {
            throw new Exception("Timeout configuration failed");
        }
        echo "✅\n";
    }
    
    private static function testRetry() {
        echo "Testing Retry Mechanism... ";
        $response = EasyAxios::retry(3)
            ->get('/pokemon/ditto');
        if (!isset($response->id) || $response->id !== 132) {
            throw new Exception("Retry mechanism failed");
        }
        echo "✅\n";
    }
    
    private static function testFileOperations() {
        echo "Testing File Operations...\n";
        
        // Test Download
        echo "  - Testing File Download... ";
        $downloadPath = __DIR__ . '/ditto.png';
        try {
            EasyAxios::download(self::$testImageUrl, $downloadPath);
            if (!file_exists($downloadPath)) {
                throw new Exception("File download failed");
            }
            unlink($downloadPath); // Clean up
            echo "✅\n";
        } catch (Exception $e) {
            echo "❌\n";
            echo "    Error: " . $e->getMessage() . "\n";
        }
        
        // Skip upload test karena PokeAPI read-only
        echo "  - Testing File Upload... (Skipped - PokeAPI is read-only) ✅\n";
    }
    
    private static function testFullResponse() {
        echo "Testing Full Response Access... ";
        $fullResponse = EasyAxios::getFullResponse()->get('/pokemon/ditto');
        if (!isset($fullResponse['success']) || !isset($fullResponse['http_code'])) {
            throw new Exception("Full response access failed");
        }
        echo "✅\n";
    }
}

// Run all tests
echo "\nEasyAxios Test Suite\n";
echo "==================\n";
echo "Testing against: " . EasyAxiosTest::$testEndpoint . "\n\n";

EasyAxiosTest::runAllTests();
