<?php
namespace Apo100l\Libraries;

use Illuminate\Encryption\Encrypter;

class OpenSslEncrypter extends Encrypter
{
    /** @var string */
    protected $opensslCipher;

    public function __construct($key, $cipher = 'rijndael-256')
    {
        $this->opensslCipher = $this->mapCipher($cipher);
        parent::__construct($key, $cipher);
    }

    protected function mapCipher($cipher)
    {
        $c = strtolower((string)$cipher);

        // Laravel 4 defaults (mcrypt names) -> OpenSSL names
        if ($c === 'rijndael-256') return 'AES-256-CBC';
        if ($c === 'rijndael-128') return 'AES-128-CBC';

        // If someone already set AES-256-CBC etc.
        return $cipher;
    }

    protected function getIvSize()
    {
        return openssl_cipher_iv_length($this->opensslCipher);
    }

    protected function createIv()
    {
        return openssl_random_pseudo_bytes($this->getIvSize());
    }
}