<?php
namespace App\Helpers;

class SimpleHashids
{

    protected $salt;
    public function __construct()
    {
        $salt = config('bc.simple_salt');
        $this->salt = $salt;
    }

    public function encode($num): string
    {
        $data = (string)$num;

        $sig = substr(hash_hmac('sha256', $data, $this->salt), 0, 16);

        $token = rtrim(strtr(base64_encode($data.'|'.$sig), '+/', '-_'), '=');

        return str_pad($token, 32, 'x');
    }

    public function decode(string $token)
    {
        $decoded = base64_decode(strtr(rtrim($token, 'x'), '-_', '+/'));
        $exploded = explode('|', $decoded);
        if(count($exploded)!=2){
            return null;
        }
        [$id, $sig] = $exploded;

        $check = substr(hash_hmac('sha256', $id, $this->salt), 0, 16);
        if (!hash_equals($check, $sig)) {
            return null; // invalid
        }

        return $id;
    }
}
