<?php
namespace App\Services;

use Firebase\JWT\JWT;

class Apple
{
 
    public function generate()
    {
        $teamId = setting_item('apple_team_id');
        $clientId = setting_item('apple_client_id');
        $keyId = setting_item('apple_key_id');
        $keyName = setting_item('apple_private_key');
       
        $privateKey = file_get_contents(storage_path('apple/'.$keyName));

        return JWT::encode([
            'iss' => $teamId,
            'iat' => time(),
            'exp' => time() + 86400*180,
            'aud' => 'https://appleid.apple.com',
            'sub' => $clientId,
        ], $privateKey, 'ES256', $keyId);
    }
}
