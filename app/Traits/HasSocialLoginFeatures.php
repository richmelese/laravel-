<?php
namespace App\Traits;

use Laravel\Socialite\Facades\Socialite;
use App\User;
use App\UserMeta;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Exception;
use Illuminate\Support\Facades\Log;
use Modules\User\Events\SendMailUserRegistered;


trait HasSocialLoginFeatures
{
    public function socialLogin($provider)
    {
        $check = $this->initConfigs($provider);
        if(!$check){
            abort(403, __('No driver ready'));
        }
        $redirectTo = request()->server('HTTP_REFERER',url('/'));
        session()->put('url.intended',$redirectTo);
        session()->put('for_api',request('for_api'));

        return Socialite::driver($provider)->redirect();
    }

    protected function authenSocialUser($provider, $socialUser){
        
        $existUser = app(User::class)->getUserBySocialId($provider, $socialUser->getId());

        if (empty($existUser)) {

            $meta = app(UserMeta::class)->query()->where('name', 'social_' . $provider . '_id')->where('val', $socialUser->getId())->first();
            if (!empty($meta)) {
                $meta->delete();
            }

            // if we can not get email, then fake email will be generated
            $email = $socialUser->getEmail();
            $email = $email?:$socialUser->getId().'@'.$provider;

            $userByEmail = app(User::class)->query()->where('email', $email)->first();
            if (!empty($userByEmail)) {
                throw new \Exception(__('Email :email exists. Can not register new account with your social email', ['email' => $email]));
            }

            // Create New User
            $realUser = new User();
            $realUser->email = $email;
            $realUser->password = Hash::make(uniqid() . time());
            $realUser->name = $socialUser->getName();
            $realUser->first_name = $socialUser->getName();
            $realUser->status = 'publish';
            $realUser->email_verified_at = Carbon::now();

            $realUser->save();

            $realUser->addMeta('social_' . $provider . '_id', $socialUser->getId());
            $realUser->addMeta('social_' . $provider . '_email', $email);
            $realUser->addMeta('social_' . $provider . '_name', $socialUser->getName());
            $realUser->addMeta('social_' . $provider . '_avatar', $socialUser->getAvatar());
            $realUser->addMeta('social_meta_avatar', $socialUser->getAvatar());

            $realUser->assignRole(setting_item('user_role'));

            try {
                event(new SendMailUserRegistered($realUser));
            } catch (Exception $exception) {
                Log::warning("SendMailUserRegistered: " . $exception->getMessage());
            }
            
            return $realUser;

        } else {

            if ($existUser->deleted == 1) {
                throw new \Exception(__('User blocked'));
            }
            if (in_array($existUser->status, ['blocked'])) {
                throw new \Exception(__('Your account has been blocked'));
            }

            return $existUser;
        }


    }

    protected function initConfigs($provider)
    {
        switch($provider){
            case "facebook":
            case "google":
            case "twitter":
                config()->set([
                    'services.'.$provider.'.client_id'=>setting_item($provider.'_client_id'),
                    'services.'.$provider.'.client_secret'=>setting_item($provider.'_client_secret'),
                    'services.'.$provider.'.redirect'=>'/social-callback/'.$provider,
                ]);
            break;
        }
        // Extra for apple
        if($provider == 'apple'){
            $privateKeyPath = storage_path('apple/'.setting_item('apple_private_key'));
            if(!file_exists($privateKeyPath)){
                // Log error
                Log::error("Apple private key not found");
                return false;
            }

            $apple = app(\App\Services\Apple::class);
            config()->set([
                'services.apple.client_id'=>setting_item('apple_client_id'),
                'services.apple.redirect'=>'/social-callback/apple',
                'services.apple.key_id'=>setting_item('apple_key_id'),
                'services.apple.team_id'=>setting_item('apple_team_id'),
                'services.apple.client_secret'=>$apple->generate(),
            ]);

        }

        return true;
    }
}
