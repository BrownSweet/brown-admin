<?php
/**
 *   Author:Brown
 *   Email: 455764041@qq.com
 *   Time: 2024-11-04 14:08
 */

namespace app\controller\core;

use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtManager
{
    private static $instance;
    private $secret='zhikuan_secret';
    private $issuer = 'zhikuan';
    private $audience = 'zhikuan_client';
    private $accessTokenExpiresIn;
    private $refreshTokenExpiresIn;

    // 私有构造函数，防止外部实例化
    private function __construct( )
    {

    }

    // 防止克隆
    private function __clone() {}

    // 防止反序列化


    // 获取唯一实例
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // 生成 access token
    public function generateAccessToken($payload, $expireIn)
    {
        $this->accessTokenExpiresIn = $expireIn;
        $issuedAt = time();
        $notBefore = $issuedAt; // 添加 10 秒的缓冲时间
        $expire = $notBefore + $this->accessTokenExpiresIn;

        $token = [
            'iss' => $this->issuer, // 发行者
            'aud' => $this->audience, // 接收者
            'iat' => $issuedAt, // 签发时间
            'nbf' => $notBefore, // 在此之前不可用
            'exp' => $expire, // 过期时间
            'data' => $payload // 自定义数据
        ];

        return JWT::encode($token, $this->secret, 'HS256');
    }

    // 验证 access token
    public function verifyAccessToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            return ['valid' => true, 'payload' => (array) $decoded];
        } catch (BeforeValidException $e) {
            return ['valid' => false, 'error' => $e->getMessage()];
        }
    }

    // 生成 refresh token
    public function generateRefreshToken($payload, $refreshTokenexpireIn)
    {
        $this->refreshTokenExpiresIn = $refreshTokenexpireIn;
        $issuedAt = time();
        $notBefore = $issuedAt; // 添加 10 秒的缓冲时间
        $expire = $notBefore + $this->refreshTokenExpiresIn;

        $token = [
            'iss' => $this->issuer, // 发行者
            'aud' => $this->audience, // 接收者
            'iat' => $issuedAt, // 签发时间
            'nbf' => $notBefore, // 在此之前不可用
            'exp' => $expire, // 过期时间
            'data' => $payload // 自定义数据
        ];

        return JWT::encode($token, $this->secret, 'HS256');
    }

    // 验证 refresh token
    public function verifyRefreshToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            return ['valid' => true, 'payload' => (array) $decoded];
        } catch (Exception $e) {
            return ['valid' => false, 'error' => $e->getMessage()];
        }
    }

    // 刷新 token
    public function refreshToken($refreshToken)
    {
        $verificationResult = $this->verifyRefreshToken($refreshToken);

        if ($verificationResult['valid']) {
            $payload = (array)$verificationResult['payload']['data'];
            if ($payload['is_refresh']){
                return true;
            }
            return false;
        } else {
            return null;
        }
    }
}
