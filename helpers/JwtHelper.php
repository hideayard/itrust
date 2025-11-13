<?php 

namespace app\helpers;

class JwtHelper
{
    public static function encode($payload, $secret)
    {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];

        $headerEncoded = self::base64urlEncode(json_encode($header));
        $payloadEncoded = self::base64urlEncode(json_encode($payload));
        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secret, true);
        $signatureEncoded = self::base64urlEncode($signature);

        return "$headerEncoded.$payloadEncoded.$signatureEncoded";
    }

    public static function decode($token, $secret)
    {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            throw new Exception('Invalid token format');
        }
        
        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
        
        // Verify signature
        $signature = self::base64urlDecode($signatureEncoded);
        $expectedSignature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secret, true);
        
        if (!hash_equals($signature, $expectedSignature)) {
            throw new Exception('Invalid token signature');
        }
        
        // Decode payload
        $payloadJson = self::base64urlDecode($payloadEncoded);
        $payload = json_decode($payloadJson, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid payload encoding');
        }
        
        return $payload;
    }

    public static function validate($token, $secret)
    {
        try {
            $payload = self::decode($token, $secret);
            
            // Check expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                throw new Exception('Token has expired');
            }
            
            // Check issued at
            if (isset($payload['iat']) && $payload['iat'] > time() + 60) {
                throw new Exception('Token issued in the future');
            }
            
            return $payload;
            
        } catch (Exception $e) {
            throw new Exception('Token validation failed: ' . $e->getMessage());
        }
    }

    private static function base64urlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64urlDecode($data)
    {
        $padding = strlen($data) % 4;
        if ($padding) {
            $data .= str_repeat('=', 4 - $padding);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}