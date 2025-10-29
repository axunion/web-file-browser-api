<?php

declare(strict_types=1);

/**
 * Helper functions for API testing
 */
final class ApiTestHelpers
{
    /**
     * Base URL for API tests. Can be overridden with setBaseUrl() or the
     * environment variable API_TEST_BASE_URL.
     */
    private static string $baseUrl;
    /**
     * Keep track of temp files created during tests so we can remove them
     * at the end of the test run.
     *
     * @var string[]
     */
    private static array $tempFiles = [];

    // Ensure shutdown handler is registered once
    private static bool $shutdownRegistered = false;

    /** Default timeout (seconds) for HTTP requests. */
    private static int $defaultTimeout = 5;

    // Initialize configurable defaults
    public static function __init(): void
    {
        if (isset(self::$baseUrl)) {
            return;
        }

        $env = getenv('API_TEST_BASE_URL');
        self::$baseUrl = $env !== false ? $env : 'http://127.0.0.1:8000';
    }

    /**
     * Send GET request
     */
    public static function get(string $endpoint, array $params = []): array
    {
        self::__init();
        $url = self::$baseUrl . $endpoint;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Don't follow redirects
        curl_setopt($ch, CURLOPT_TIMEOUT, self::$defaultTimeout);

        $response = curl_exec($ch);

        if ($response === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('cURL GET error: ' . $err);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        return [
            'code' => $httpCode,
            'headers' => self::parseHeaders($headers),
            'body' => $body,
            'json' => self::safeJsonDecode($body),
        ];
    }

    /**
     * Send POST request with form data
     */
    public static function post(string $endpoint, array $data = []): array
    {
        self::__init();
        $url = self::$baseUrl . $endpoint;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Don't follow redirects
        curl_setopt($ch, CURLOPT_TIMEOUT, self::$defaultTimeout);

        $response = curl_exec($ch);

        if ($response === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('cURL POST error: ' . $err);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        return [
            'code' => $httpCode,
            'headers' => self::parseHeaders($headers),
            'body' => $body,
            'json' => self::safeJsonDecode($body),
        ];
    }

    /**
     * Send POST request with multipart/form-data (for file uploads)
     */
    public static function postMultipart(string $endpoint, array $fields = [], array $files = []): array
    {
        self::__init();
        $url = self::$baseUrl . $endpoint;
        $postData = $fields;

        // Add files using CURLFile and validate existence
        foreach ($files as $fieldName => $filePath) {
            if (is_array($filePath)) {
                foreach ($filePath as $index => $path) {
                    if (!is_readable($path) || !is_file($path)) {
                        throw new RuntimeException("Upload file not found or not readable: $path");
                    }
                    $postData[$fieldName . '[' . $index . ']'] = new CURLFile($path);
                }
            } else {
                if (!is_readable($filePath) || !is_file($filePath)) {
                    throw new RuntimeException("Upload file not found or not readable: $filePath");
                }
                $postData[$fieldName] = new CURLFile($filePath);
            }
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Don't follow redirects
        curl_setopt($ch, CURLOPT_TIMEOUT, self::$defaultTimeout);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException("cURL multipart error: $error");
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        return [
            'code' => $httpCode,
            'headers' => self::parseHeaders($headers),
            'body' => $body,
            'json' => self::safeJsonDecode($body),
        ];
    }

    /**
     * Parse HTTP headers into associative array
     */
    private static function parseHeaders(string $headerString): array
    {
        $headers = [];
        $lines = preg_split('/\r?\n/', $headerString);

        foreach ($lines as $line) {
            if ($line === '' || strpos($line, ':') === false) {
                continue;
            }

            [$key, $value] = explode(':', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Support duplicate header names by collecting values in an array
            if (isset($headers[$key])) {
                if (is_array($headers[$key])) {
                    $headers[$key][] = $value;
                } else {
                    $headers[$key] = [$headers[$key], $value];
                }
            } else {
                $headers[$key] = $value;
            }
        }

        return $headers;
    }

    /**
     * Assert that response is successful
     */
    public static function assertSuccess(array $response, string $message = ''): void
    {
        $prefix = $message ? "$message: " : '';

        assert(
            $response['code'] === 200,
            $prefix . "Expected HTTP 200, got {$response['code']}"
        );

        // Be defensive: json may be null if response body wasn't valid JSON
        if (!is_array($response['json']) || !array_key_exists('status', $response['json'])) {
            assert(false, $prefix . "Response JSON missing or invalid; cannot assert 'status'");
            return;
        }

        assert(
            $response['json']['status'] === 'success',
            $prefix . "Expected status 'success', got '" . $response['json']['status'] . "'"
        );
    }

    /**
     * Assert that response is an error
     */
    public static function assertError(array $response, int $expectedCode = 400, string $message = ''): void
    {
        $prefix = $message ? "$message: " : '';

        assert(
            $response['code'] === $expectedCode,
            $prefix . "Expected HTTP {$expectedCode}, got {$response['code']}"
        );

        if (!is_array($response['json']) || !array_key_exists('status', $response['json'])) {
            assert(false, $prefix . "Response JSON missing or invalid; cannot assert 'status'");
            return;
        }

        assert(
            $response['json']['status'] === 'error',
            $prefix . "Expected status 'error', got '" . $response['json']['status'] . "'"
        );
    }

    /**
     * Assert that value equals expected
     */
    public static function assertEquals($expected, $actual, string $message = ''): void
    {
        $prefix = $message ? "$message: " : '';
        assert(
            $expected === $actual,
            $prefix . "Expected " . var_export($expected, true) . ", got " . var_export($actual, true)
        );
    }

    /**
     * Assert that array contains key
     */
    public static function assertArrayHasKey(string $key, array $array, string $message = ''): void
    {
        $prefix = $message ? "$message: " : '';
        assert(
            array_key_exists($key, $array),
            $prefix . "Expected array to have key '$key'"
        );
    }

    /**
     * Create a temporary test file
     */
    public static function createTempFile(string $content, string $extension = 'txt'): string
    {
        // Use uniqid to avoid leaving the extra file that tempnam() creates
        $tmpFile = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . 'api_test_' . uniqid('', true) . '.' . $extension;

        file_put_contents($tmpFile, $content);
        self::registerTempFile($tmpFile);

        return $tmpFile;
    }

    /**
     * Create a temporary image file
     */
    public static function createTempImage(int $width = 100, int $height = 100, string $format = 'jpeg'): string
    {
        $ext = $format === 'jpeg' ? 'jpg' : $format;
        $tmpFile = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . 'api_test_' . uniqid('', true) . '.' . $ext;

        if ($format === 'jpeg') {
            // Create minimal valid JPEG
            $jpeg = "\xFF\xD8\xFF\xE0\x00\x10\x4A\x46\x49\x46\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00"
                  . "\xFF\xDB\x00\x43\x00\x08\x06\x06\x07\x06\x05\x08\x07\x07\x07\x09\x09\x08\x0A\x0C"
                  . "\x14\x0D\x0C\x0B\x0B\x0C\x19\x12\x13\x0F\x14\x1D\x1A\x1F\x1E\x1D\x1A\x1C\x1C\x20"
                  . "\x24\x2E\x27\x20\x22\x2C\x23\x1C\x1C\x28\x37\x29\x2C\x30\x31\x34\x34\x34\x1F\x27"
                  . "\x39\x3D\x38\x32\x3C\x2E\x33\x34\x32\xFF\xC0\x00\x0B\x08\x00\x01\x00\x01\x01\x01"
                  . "\x11\x00\xFF\xC4\x00\x1F\x00\x00\x01\x05\x01\x01\x01\x01\x01\x01\x00\x00\x00\x00"
                  . "\x00\x00\x00\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\xFF\xC4\x00\xB5\x10"
                  . "\x00\x02\x01\x03\x03\x02\x04\x03\x05\x05\x04\x04\x00\x00\x01\x7D\x01\x02\x03\x00"
                  . "\x04\x11\x05\x12\x21\x31\x41\x06\x13\x51\x61\x07\x22\x71\x14\x32\x81\x91\xA1\x08"
                  . "\x23\x42\xB1\xC1\x15\x52\xD1\xF0\x24\x33\x62\x72\x82\x09\x0A\x16\x17\x18\x19\x1A"
                  . "\x25\x26\x27\x28\x29\x2A\x34\x35\x36\x37\x38\x39\x3A\x43\x44\x45\x46\x47\x48\x49"
                  . "\x4A\x53\x54\x55\x56\x57\x58\x59\x5A\x63\x64\x65\x66\x67\x68\x69\x6A\x73\x74\x75"
                  . "\x76\x77\x78\x79\x7A\x83\x84\x85\x86\x87\x88\x89\x8A\x92\x93\x94\x95\x96\x97\x98"
                  . "\x99\x9A\xA2\xA3\xA4\xA5\xA6\xA7\xA8\xA9\xAA\xB2\xB3\xB4\xB5\xB6\xB7\xB8\xB9\xBA"
                  . "\xC2\xC3\xC4\xC5\xC6\xC7\xC8\xC9\xCA\xD2\xD3\xD4\xD5\xD6\xD7\xD8\xD9\xDA\xE1\xE2"
                  . "\xE3\xE4\xE5\xE6\xE7\xE8\xE9\xEA\xF1\xF2\xF3\xF4\xF5\xF6\xF7\xF8\xF9\xFA\xFF\xDA"
                  . "\x00\x08\x01\x01\x00\x00\x3F\x00\xFE\xFE\xFF\xD9";
            file_put_contents($tmpFile, $jpeg);
            self::registerTempFile($tmpFile);
        } elseif ($format === 'png') {
            // Create minimal valid PNG (1x1 red pixel)
            $png = "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A"
                 . "\x00\x00\x00\x0D\x49\x48\x44\x52\x00\x00\x00\x01\x00\x00\x00\x01\x08\x02\x00\x00\x00\x90\x77\x53\xDE"
                 . "\x00\x00\x00\x0C\x49\x44\x41\x54\x08\xD7\x63\xF8\xCF\xC0\x00\x00\x03\x01\x01\x00\x18\xDD\x8D\xB4"
                 . "\x00\x00\x00\x00\x49\x45\x4E\x44\xAE\x42\x60\x82";
            file_put_contents($tmpFile, $png);
            self::registerTempFile($tmpFile);
        }

        return $tmpFile;
    }

    /**
     * Register a temp file so it can be removed later.
     */
    private static function registerTempFile(string $path): void
    {
        if (!in_array($path, self::$tempFiles, true)) {
            self::$tempFiles[] = $path;
            // Register shutdown handler lazily when first temp file is added
            if (!self::$shutdownRegistered) {
                register_shutdown_function([self::class, 'cleanupTempFiles']);
                self::$shutdownRegistered = true;
            }
        }
    }

    /**
     * Remove all registered temp files. Safe to call multiple times.
     */
    public static function cleanupTempFiles(): void
    {
        foreach (self::$tempFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        // Reset the list so repeated calls do nothing
        self::$tempFiles = [];
    }

    /**
     * Clean up test files in data directory
     */
    public static function cleanupTestFiles(string $dataDir, string $pattern): void
    {
        // Basic safety checks to avoid accidental deletion
        if (empty($dataDir) || !is_dir($dataDir)) {
            throw new InvalidArgumentException("dataDir must be an existing directory");
        }

        // Prevent patterns that try to escape the dataDir
        if (strpos($pattern, '..') !== false) {
            throw new InvalidArgumentException('Pattern contains invalid traversal');
        }

        $glob = $dataDir . DIRECTORY_SEPARATOR . $pattern;
        $files = glob($glob);
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    /**
     * Set the base URL for API requests (useful for tests that run on different ports)
     */
    public static function setBaseUrl(string $url): void
    {
        self::$baseUrl = rtrim($url, '/');
    }

    /**
     * Set default timeout (seconds) for HTTP requests.
     */
    public static function setTimeout(int $seconds): void
    {
        if ($seconds <= 0) {
            throw new InvalidArgumentException('Timeout must be positive');
        }

        self::$defaultTimeout = $seconds;
    }

    /**
     * Decode JSON safely and return null on failure.
     * Also preserves the behavior of returning associative arrays.
     *
     * @return mixed|null
     */
    private static function safeJsonDecode(string $body)
    {
        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        return $decoded;
    }

    /**
     * Return list of registered temp files (useful for debugging)
     *
     * @return string[]
     */
    public static function listTempFiles(): array
    {
        return self::$tempFiles;
    }
}
