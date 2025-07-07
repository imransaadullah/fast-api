<?php

namespace FASTAPI\Token;

use \Firebase\JWT\JWT;
use FASTAPI\CustomTime\CustomTime;

/**
 * Class Token
 * Handles JWT generation and verification.
 */
class Token extends JWT
{
    // Properties

    /**
     * @var string Path to the private key file.
     */
    private $private_key_file;

    /**
     * @var string Path to the public key file.
     */
    private $public_key_file;

    /**
     * @var string Timezone used for token generation.
     */
    private $timezone;

    /**
     * @var int Token not-before timestamp.
     */
    private $not_before;

    /**
     * @var int Token expiry timestamp.
     */
    private $expiry;

    /**
     * @var string Token issuer.
     */
    private $issuer;

    /**
     * @var string Token audience.
     */
    private $audience;

    /**
     * @var CustomTime Custom time handler for token generation.
     */
    private $custom_time;

    /**
     * @var string The generated token.
     */
    private $token;

    /**
     * @var mixed Decoded token data.
     */
    private $data;

    /**
     * @var string Secret key used for HMAC encryption.
     */
    private $secret_key;

    /**
     * @var string Algorithm used for token encryption.
     */
    private $algo = 'RS256';

    /**
     * @var bool Whether to use SSL for token encryption.
     */
    private $use_ssl = false;

    // Methods
    /**
     * Constructor.
     * @param string $audience The audience for the token.
     * @param CustomTime|null $timeHandler Custom time handler.
     * @param bool $use_ssl Whether to use SSL.
     * @throws \Exception If secret key is not set or private key file is missing.
     */
    public function __construct($audience, ?CustomTime $timeHnadler = null, bool $use_ssl = true)
    {
        if (empty($audience)) {
            throw new \InvalidArgumentException('Audience cannot be empty.');
        }

        $this->use_ssl = $use_ssl;

        if (!$use_ssl) {
            $this->secret_key = $_ENV['SECRET_KEY'] ?? null;
        }

        $this->private_key_file = $_ENV['SECRETS_DIR'] . "private.pem";
        $this->public_key_file = $_ENV['SECRETS_DIR'] . "public.pem";

        $this->timezone = $_ENV['TIMEZONE'];
        $this->issuer = $_ENV['TOKEN_ISSUER'];
        $this->audience = $audience;
        $this->custom_time = $timeHnadler ?? new CustomTime();
        $this->not_before = $this->custom_time->get_timestamp();
        $this->expiry = ($timeHnadler ?? new CustomTime())->add_minutes(5)->get_timestamp();
    }

    /**
     * Retrieves the public key resource using OpenSSL.
     * @return resource|false Returns the public key resource or false on failure.
     */
    protected function get_public_key_openssl()
    {
        return openssl_get_publickey($this->public_key_file);
    }

    /**
     * Retrieves the private key resource using OpenSSL.
     * @return resource|false Returns the private key resource or false on failure.
     */
    protected function get_private_key_openssl()
    {
        return openssl_get_privatekey($this->private_key_file);
    }

    /**
     * Sets the path to the private key file for OpenSSL.
     * @param string|null $file The path to the private key file.
     * @return $this
     */
    public function set_private_key_file_openssl($file = null)
    {
        $this->private_key_file = $file ?? $_ENV['SECRETS_DIR'] . 'private.pem';
        return $this;
    }

    /**
     * Sets the path to the public key file for OpenSSL.
     * @param string|null $file The path to the public key file.
     * @return $this
     */
    public function set_public_key_file_openssl($file = null)
    {
        $this->public_key_file = $file ?? $_ENV['SECRETS_DIR'] . 'public.pem';
        return $this;
    }

    /**
     * Sets the secret key for HMAC-based encryption.
     * @param string $secret_key The secret key.
     * @return $this
     */
    public function set_secret_key(string $secret_key)
    {
        $this->secret_key = $secret_key;
        return $this;
    }

    /**
     * Retrieves the secret key for HMAC-based encryption.
     * @return string|null The secret key or null if not set.
     */
    protected function get_secret_key()
    {
        return $this->secret_key;
    }

    /**
     * Sets the algorithm for token generation.
     * @param string $algo The algorithm name.
     * @return $this
     */
    public function set_algo($algo)
    {
        $this->algo = $algo;
        return $this;
    }

    /**
     * Retrieves the algorithm for token generation.
     * @return string The algorithm name.
     */
    protected function get_algo()
    {
        return $this->algo;
    }

    /**
     * Generates a JWT token with the provided data and expiry.
     * @param array $data The data payload for the token.
     * @param string $expiry The expiry timestamp for the token.
     * @return string The generated JWT token.
     * @throws \Exception If secret key is not set or private key file is missing.
     */
    public function make(array $data = [], ?int $expiry = null): self
    {
        if (!$this->use_ssl && !$this->secret_key)
            throw new \Exception('Secret Key was not set');
        if ($this->use_ssl && !is_file($this->private_key_file))
            throw new \Exception('Private key is missing or invalid');

        $token = [
            "iss" => $this->issuer,
            "aud" => $this->audience,
            "iat" => $this->custom_time->get_timestamp(),
            "nbf" => $this->custom_time->get_timestamp(),
            "exp" => $expiry ?? $this->expiry,
            "data" => $data
        ];

        if (!$this->use_ssl && $this->secret_key)
            $this->token = $this->encode($token, $this->secret_key);
        else if ($this->use_ssl && is_file($this->private_key_file)) {
            $this->token = $this->encode($token, $this->get_private_key_openssl(), $this->algo);
        } else {
            throw new \Exception("Unable to generate token due to bad configuration");
        }

        return $this;
    }

    /**
     * Verifies a JWT token and returns the decoded data.
     * @param string $token The JWT token to verify.
     * @return $this The Token instance.
     * @throws \Exception If secret key is not set or private key file is missing.
     */
    public function verify($token): self
    {
        if (!$this->use_ssl && !$this->secret_key)
            throw new \Exception('Secret Key was not set');
        if ($this->use_ssl && !is_file($this->private_key_file))
            throw new \Exception('Private key can not be located');

        if (!$this->use_ssl && $this->secret_key)
            $this->data = $this->decode($token, $this->secret_key);
        else if ($this->use_ssl && is_file($this->public_key_file)) {
            $this->data = $this->decode($token, $this->get_public_key_openssl(), [$this->algo]);
        } else {
            throw new \Exception("Unable to verify token due to bad configuration");
        }

        return $this;
    }

    /**
     * Encrypts the token payload.
     * @param string|array|object $payload The payload to encrypt.
     * @param string $encryptionKey The encryption key.
     * @param string $algorithm The encryption algorithm (e.g., AES-256-CBC).
     * @return string|null The encrypted payload or null on failure.
     */
    public function encrypt_token_payload($payload, string $encryptionKey, string $algorithm = 'AES-256-CBC'): ?string {
        $ivLength = openssl_cipher_iv_length($algorithm);
        $iv = openssl_random_pseudo_bytes($ivLength);
        $encryptedPayload = openssl_encrypt(json_encode($payload), $algorithm, $encryptionKey, OPENSSL_RAW_DATA, $iv);
        if ($encryptedPayload === false) {
            return null;
        }
        return base64_encode($iv . $encryptedPayload);
    }

    /**
     * Decrypts the encrypted token payload.
     * @param string $encryptedPayload The encrypted payload to decrypt.
     * @param string $encryptionKey The encryption key.
     * @param string $algorithm The encryption algorithm (e.g., AES-256-CBC).
     * @return array|object|null The decrypted payload as an array or object, or null on failure.
     */
    public function decrypt_token_payload(string $encryptedPayload, string $encryptionKey, string $algorithm = 'AES-256-CBC') {
        $encryptedPayload = base64_decode($encryptedPayload);
        $ivLength = openssl_cipher_iv_length($algorithm);
        $iv = substr($encryptedPayload, 0, $ivLength);
        $payload = substr($encryptedPayload, $ivLength);
        $decryptedPayload = openssl_decrypt($payload, $algorithm, $encryptionKey, OPENSSL_RAW_DATA, $iv);
        if ($decryptedPayload === false) {
            return null;
        }
        return json_decode($decryptedPayload, true);
    }

    /**
     * Sets the issuer claim (`iss`) in the JWT payload.
     * @param string $issuer The issuer value.
     * @return $this
     */
    public function set_issuer(string $issuer) {
        $this->issuer = $issuer;
        return $this;
    }

    /**
     * Sets the audience claim (`aud`) in the JWT payload.
     * @param string $audience The audience value.
     * @return $this
     */
    public function set_audience(string $audience) {
        $this->audience = $audience;
        return $this;
    }

    /**
     * Sets the not-before claim (`nbf`) in the JWT payload.
     * @param int $timestamp The not-before timestamp.
     * @return $this
     */
    public function set_not_before(int $timestamp) {
        $this->not_before = $timestamp;
        return $this;
    }

    /**
     * Adds a custom claim to the JWT payload.
     * @param string $name The name of the custom claim.
     * @param mixed $value The value of the custom claim.
     */
    public function add_claim(string $name, $value) {
        $this->data[$name] = $value;
        return $this;
    }

    /**
     * Checks whether the token has expired based on its expiry claim (`exp`).
     * @param string $token The JWT token to check.
     * @return bool True if the token has expired, false otherwise.
     */
    public function is_expired(string $token): bool {
        if ($this->verify($token)) {
            return time() > $this->data->exp;
        }
        return false;
    }

    /**
     * Refreshes the token by extending its expiry without changing its contents.
     * @param string $token The JWT token to refresh.
     * @param int $expiryDelta The amount of time (in seconds) to extend the expiry.
     * @return string The refreshed JWT token.
     */
    public function refresh(string $token, int $expiryDelta): self {
        if ($this->verify($token)) {
            $this->make((array) $this->data->data, $expiryDelta);
            return $this;
        }

        throw new \Exception("Token does not contain expiry claim.");
    }

    /**
     * Retrieves the decoded data from the verified JWT token.
     * @return array|null The decoded data payload or null if token not verified.
     */
    public function get_data()
    {
        return $this->data ? $this->data->data : $this->data;
    }

    /**
     * Retrieves the generated JWT token.
     * @return string|null .
     */
    public function get_token() : string
    {
        return $this->token;
    }

    /**
     * Sets the expiry timestamp for token generation.
     * @param int $timestamp The expiry timestamp.
     * @return $this
     */
    public function set_expiry($timestamp)
    {
        $this->expiry = $timestamp;
        return $this;
    }

    /**
     * Sets the expiry timestamp for token generation.
     * @param int $timestamp The expiry timestamp.
     */
    public function get_expiry()
    {
        return $this->expiry;
    }
}
