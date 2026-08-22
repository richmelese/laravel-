<?php


	namespace Modules\User\Controllers\Auth;


	use App\Helpers\ReCaptchaEngine;
    use Illuminate\Auth\Events\Registered;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Support\Facades\Validator;
    use Illuminate\Support\MessageBag;
    use Illuminate\Validation\Rules\Password;
    use Modules\User\Events\SendMailUserRegistered;
    use Throwable;

    class RegisterController extends \App\Http\Controllers\Auth\RegisterController
	{

    public function register(Request $request)
        {
            if(!is_enable_registration()){
                return $this->sendError(__("You are not allowed to register"));
            }
            $rules = [
                'first_name' => [
                    'required',
                    'string',
                    'min:2',
                    'max:255',
                    'regex:/^[\p{L}\s\-\'\.]+$/u'
                ],
                'last_name'  => [
                    'required',
                    'string',
                    'min:2',
                    'max:255',
                    'regex:/^[\p{L}\s\-\'\.]+$/u'
                ],
                'email'      => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    'unique:users,email'
                ],
                'password'   => [
                    'required',
                    'string',
                    Password::min(8)
                        ->mixedCase()
                        ->numbers()
                        ->symbols()
                        ->uncompromised(),
                ],
                'phone'       => [
                    'required',
                    'string',
                    'unique:users,phone',
                    'regex:/^\+?[1-9]\d{6,14}$/'
                ],
                'term'       => ['required'],
            ];
            $messages = [
                'phone.required'      => __('Phone is required field'),
                'phone.unique'        => __('The phone number has already been taken'),
                'phone.regex'         => __('Please enter a valid phone number (7-15 digits, optional leading +)'),
                'email.required'      => __('Email is required field'),
                'email.email'         => __('Email invalidate'),
                'email.unique'        => __('The email address has already been taken'),
                'password.required'   => __('Password is required field'),
                'first_name.required' => __('The first name is required field'),
                'first_name.min'      => __('The first name must be at least 2 characters'),
                'first_name.regex'    => __('The first name format is invalid'),
                'last_name.required'  => __('The last name is required field'),
                'last_name.min'       => __('The last name must be at least 2 characters'),
                'last_name.regex'     => __('The last name format is invalid'),
                'term.required'       => __('The terms and conditions field is required'),
            ];
            if (ReCaptchaEngine::isEnable() and setting_item("user_enable_register_recaptcha")) {
                $codeCapcha = $request->input('g-recaptcha-response');
                if (!$codeCapcha or !ReCaptchaEngine::verify($codeCapcha)) {
                    $errors = new MessageBag(['message_error' => __('Please verify the captcha')]);
                    return response()->json([
                        'error'    => true,
                        'messages' => $errors
                    ], 200);
                }
            }
            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                return response()->json([
                    'error'    => true,
                    'messages' => $validator->errors()
                ], 200);
            } else {

                $user = \App\User::create([
                    'first_name' => trim($request->input('first_name')),
                    'last_name'  => trim($request->input('last_name')),
                    'email'      => strtolower(trim($request->input('email'))),
                    'password'   => Hash::make($request->input('password')),
                    'status'     => 'publish',
                    'phone'      => trim($request->input('phone')),
                ]);
                // Assign role before mail / verification events so a mail failure cannot leave role_id null
                $user->assignRole('customer');
                Auth::loginUsingId($user->id);
                try {
                    event(new Registered($user));
                } catch (Throwable $e) {
                    Log::warning('Registered event failed (e.g. verification email): '.$e->getMessage(), ['exception' => $e]);
                }
                try {
                    event(new SendMailUserRegistered($user));
                } catch (Throwable $e) {
                    Log::warning('SendMailUserRegistered: '.$e->getMessage(), ['exception' => $e]);
                }
                $user->refresh();

                return response()->json([
                    'error'    => false,
                    'messages' => false,
                    'redirect' => $request->input('redirect') ?? $request->headers->get('referer') ?? url(app_get_locale(false, '/'))
                ], 200);
            }
        }
    }
