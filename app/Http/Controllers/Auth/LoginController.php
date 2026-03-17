<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Matrix\Exception;
use \Laravel\Socialite\Facades\Socialite;
use App\Traits\HasSocialLoginFeatures;

class LoginController extends Controller
{
    use HasSocialLoginFeatures;
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/user/profile';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function redirectTo()
    {
        if(Auth::user()->hasPermission('dashboard_access')){
            return '/admin';
        }else{
            return $this->redirectTo;
        }
    }

    public function showLoginForm()
    {
        return view('auth.login',['page_title'=> __("Login")]);
    }

    public function socialCallBack($provider)
    {
        $for_api = !!session('for_api');
        $routerForResponse = $for_api ?  'social.callback.token': 'login';

        try {
            $this->initConfigs($provider);

            $user = Socialite::driver($provider)->user();

            $redirectTo = $this->getRedirectTo();
            session()->forget('url.intended');

            $existUser = $this->authenSocialUser($provider,$user);

            if(!empty($existUser)){

                // For API
                // Generate access Token with scantum and redirect
                if($for_api){
                    $token = $existUser->createToken('social_'.$provider)->plainTextToken;
                    return redirect()->route($routerForResponse,['provider'=>$provider,'token'=>$token]);
                }

                Auth::login($existUser);

                return redirect($redirectTo);
            }else{
                return redirect()->route($routerForResponse,['provider'=>$provider,'error'=>'Can not authorize'])->with('error',__('Can not authorize'));
            }

        }catch (\Exception $exception)
        {
            $message = $exception->getMessage();
            if(empty($message) and request()->get('error_message')) $message = request()->get('error_message');
            if(empty($message)) $message = $exception->getCode();
            if(empty($message)) $message = get_class($exception);

            return redirect()->route($routerForResponse,['provider'=>$provider, 'error'=>$message])->with('error',$message);
        }
    }

    public function getRedirectTo(){
        $url = session()->get('url.intended', url('/'));
        session()->forget('url.intended');
        if($url == url('/') or $url ==route('login') or $url == route('auth.register')){
            $url = url('/');
        }
        return $url;
    }


    public function emptyWithToken(){
        return view('admin.message');
    }
}
