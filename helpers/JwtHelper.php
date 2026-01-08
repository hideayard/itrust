<?php 

namespace app\helpers;

class JwtHelper
{
    /**
     * Encode payload to JWT token
     */
    public static function encode($payload, $secret)
    {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];

        $headerEncoded = self::base64urlEncode(json_encode($header));
        $payloadEncoded = self::base64urlEncode(json_encode($payload));
        
        // Generate signature
        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secret, true);
        $signatureEncoded = self::base64urlEncode($signature);

        return "$headerEncoded.$payloadEncoded.$signatureEncoded";
    }

    /**
     * Decode JWT token and verify signature
     */
    public static function decode($token, $secret)
    {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            throw new \Exception('Invalid token format');
        }
        
        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
        
        // Verify signature
        $signature = self::base64urlDecode($signatureEncoded);
        $expectedSignature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secret, true);
        
        if (!hash_equals($signature, $expectedSignature)) {
            throw new \Exception('Invalid token signature');
        }
        
        // Decode payload
        $payloadJson = self::base64urlDecode($payloadEncoded);
        $payload = json_decode($payloadJson, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid payload encoding: ' . json_last_error_msg());
        }
        
        return $payload;
    }

    /**
     * Validate JWT token with expiration checks
     */
    public static function validate($token, $secret)
    {
        try {
            $payload = self::decode($token, $secret);
            
            // Check expiration (exp must be in seconds since epoch)
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                throw new \Exception('Token has expired');
            }
            
            // Check issued at (iat should not be in future)
            if (isset($payload['iat']) && $payload['iat'] > time() + 60) {
                throw new \Exception('Token issued in the future');
            }
            
            // Check not before (nbf)
            if (isset($payload['nbf']) && $payload['nbf'] > time()) {
                throw new \Exception('Token not yet valid');
            }
            
            return $payload;
            
        } catch (\Exception $e) {
            throw new \Exception('Token validation failed: ' . $e->getMessage());
        }
    }

    /**
     * Base64Url encode
     */
    private static function base64urlEncode($data)
    {
        // Ensure data is string
        if (!is_string($data)) {
            $data = json_encode($data);
        }
        
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64Url decode with proper padding
     */
    private static function base64urlDecode($data)
    {
        // Add padding if needed
        $padding = strlen($data) % 4;
        if ($padding > 0) {
            $data .= str_repeat('=', 4 - $padding);
        }
        
        // Replace URL-safe characters
        $data = strtr($data, '-_', '+/');
        
        // Decode
        $decoded = base64_decode($data, true);
        
        if ($decoded === false) {
            throw new \Exception('Base64 decode failed');
        }
        
        return $decoded;
    }

    /**
     * Simple verify token without full validation (just signature check)
     */
    public static function verify($token, $secret)
    {
        try {
            $payload = self::decode($token, $secret);
            return $payload;
        } catch (\Exception $e) {
            return false;
        }
    }
}