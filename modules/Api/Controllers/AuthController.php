<?php
namespace Modules\Api\Controllers;

use App\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Api\Models\TelebirrAccount;
use Modules\Booking\Services\TelebirrService;
use Modules\User\Emails\ResetPasswordToken;
use Modules\User\Events\SendMailUserRegistered;
use Modules\User\Resources\UserResource;
use Validator;
use Illuminate\Validation\Rules\Password;
use App\Traits\HasSocialLoginFeatures;
use Throwable;

class AuthController extends Controller
{

    use HasSocialLoginFeatures;
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum', ['except' => ['login','register','forgotPassword','resetPassword','socialCallback',"refreshToken",'telebirrMiniApp']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     */
    public function login(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError('',['errors'=>$validator->errors()]);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->sendError(__("Password is not correct"),['code'=>'invalid_credentials']);
        }

        return [
            'access_token'=>$user->createToken($request->device_name)->plainTextToken,
            'user'=> new UserResource($user),
            'status'=>1
        ];
    }

    public function telebirrMiniApp(Request $request, TelebirrService $telebirr)
    {
        if (! config('telebirr.miniapp_login_enabled')) {
            return response()->json([
                'message' => __('Telebirr Mini App login is disabled.'),
                'status' => 0,
            ], 503);
        }

        $validated = $request->validate([
            'access_token' => ['required', 'string', 'max:4096', 'regex:/^[^\s\x00-\x1F\x7F]+$/'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $telebirrResponse = $telebirr->authenticateMiniAppToken($validated['access_token']);
        } catch (Throwable $exception) {
            Log::warning('Telebirr Mini App authentication failed', [
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => __('Unable to authenticate with Telebirr.'),
                'status' => 0,
                'code' => 'telebirr_auth_failed',
            ], 401);
        }

        $profile = data_get($telebirrResponse, 'biz_content', []);
        if (is_string($profile)) {
            $profile = json_decode($profile, true);
        }
        $profile = is_array($profile) ? $profile : [];
        $openId = trim((string) (
            $profile['open_id']
            ?? $profile['openId']
            ?? ''
        ));

        if ($openId === '' || strlen($openId) > 191) {
            Log::error('Telebirr Mini App response did not contain a valid open_id.');

            return response()->json([
                'message' => __('Telebirr did not return a valid customer identity.'),
                'status' => 0,
                'code' => 'telebirr_identity_missing',
            ], 502);
        }

        $phone = $this->telebirrPhone($profile['identifier'] ?? null);
        if ($phone === null) {
            Log::error('Telebirr Mini App response did not contain a valid customer phone number.', [
                'open_id_hash' => hash('sha256', $openId),
            ]);

            return response()->json([
                'message' => __('Telebirr did not return a valid customer phone number.'),
                'status' => 0,
                'code' => 'telebirr_phone_missing',
            ], 502);
        }

        $identityType = strtoupper(trim((string) (
            $profile['identityType']
            ?? $profile['identity_type']
            ?? ''
        )));
        if ($identityType !== '' && $identityType !== 'CUSTOMER') {
            return response()->json([
                'message' => __('The Telebirr identity is not a customer account.'),
                'status' => 0,
                'code' => 'telebirr_identity_not_customer',
            ], 403);
        }

        $telebirrStatus = strtoupper(trim((string) ($profile['status'] ?? '')));
        if (in_array($telebirrStatus, ['BLOCKED', 'SUSPENDED', 'INACTIVE', 'CLOSED', 'DISABLED'], true)) {
            return response()->json([
                'message' => __('The Telebirr account is not active.'),
                'status' => 0,
                'code' => 'telebirr_account_inactive',
            ], 403);
        }

        try {
            [$user, $account] = DB::transaction(function () use ($openId, $phone, $profile, $identityType, $telebirrStatus) {
                $account = TelebirrAccount::query()
                    ->where('open_id', $openId)
                    ->lockForUpdate()
                    ->first();

                if ($account) {
                    $user = User::withTrashed()->find($account->user_id);
                    if (! $user || $user->trashed() || $user->status !== 'publish') {
                        throw new \RuntimeException(__('Your local account is blocked or unavailable.'));
                    }
                } else {
                    $internalEmail = 'telebirr_'.hash('sha256', $openId).'@users.invalid';
                    $user = User::withTrashed()->where('email', $internalEmail)->first();

                    if ($user && ($user->trashed() || $user->status !== 'publish')) {
                        throw new \RuntimeException(__('Your local account is blocked or unavailable.'));
                    }

                    if (! $user) {
                        [$firstName, $lastName] = $this->telebirrNames(
                            (string) ($profile['nickName'] ?? $profile['nickname'] ?? '')
                        );

                        $user = User::create([
                            'first_name' => $firstName,
                            'last_name' => $lastName,
                            'email' => $internalEmail,
                            'password' => Hash::make(Str::random(64)),
                            'phone' => $phone,
                            'status' => 'publish',
                        ]);
                        $user->assignRole('customer');
                    }

                    $account = new TelebirrAccount([
                        'open_id' => $openId,
                        'user_id' => $user->id,
                    ]);
                }

                $account->fill([
                    'identity_id' => $profile['identityId'] ?? $profile['identity_id'] ?? null,
                    'identity_type' => $identityType ?: null,
                    'wallet_identity_id' => $profile['walletIdentityId'] ?? $profile['wallet_identity_id'] ?? null,
                    'identifier' => $phone,
                    'nickname' => $profile['nickName'] ?? $profile['nickname'] ?? null,
                    'status' => $telebirrStatus ?: null,
                    'profile' => $profile,
                    'last_login_at' => now(),
                ]);
                $account->user_id = $user->id;
                $account->save();

                $user->last_login_at = now();
                $user->phone = $phone;
                $user->save();

                return [$user->fresh(), $account->fresh()];
            }, 3);
        } catch (Throwable $exception) {
            Log::error('Unable to provision the Telebirr Mini App user', [
                'open_id_hash' => hash('sha256', $openId),
                'error' => $exception->getMessage(),
            ]);

            $localAccountUnavailable = $exception instanceof \RuntimeException
                && $exception->getMessage() === __('Your local account is blocked or unavailable.');

            return response()->json([
                'message' => $localAccountUnavailable
                    ? __('Your local account is blocked or unavailable.')
                    : __('Unable to create or access the local account.'),
                'status' => 0,
                'code' => $localAccountUnavailable
                    ? 'local_account_unavailable'
                    : 'telebirr_user_provisioning_failed',
            ], $localAccountUnavailable ? 403 : 500);
        }

        $deviceName = trim((string) ($validated['device_name'] ?? 'default'));
        $tokenName = 'telebirr-miniapp:'.($deviceName ?: 'default');
        $user->tokens()->where('name', $tokenName)->delete();
        $token = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
            'telebirr' => [
                'open_id' => $account->open_id,
                'phone' => $account->identifier,
                'identity_type' => $account->identity_type,
                'nickname' => $account->nickname,
                'status' => $account->status,
            ],
            'status' => 1,
        ]);
    }

    private function telebirrNames(string $nickname): array
    {
        $nickname = trim(preg_replace('/\s+/u', ' ', $nickname) ?? '');
        if ($nickname === '') {
            return ['Telebirr', 'Customer'];
        }

        $parts = preg_split('/\s+/u', $nickname, 2);

        return [
            mb_substr((string) ($parts[0] ?? 'Telebirr'), 0, 255),
            mb_substr((string) ($parts[1] ?? ''), 0, 255),
        ];
    }

    private function telebirrPhone($identifier): ?string
    {
        $phone = preg_replace('/[\s\-()]/', '', trim((string) $identifier));
        if (! is_string($phone) || $phone === '') {
            return null;
        }

        if (preg_match('/^09\d{8}$/', $phone)) {
            return '+251'.substr($phone, 1);
        }

        if (preg_match('/^2519\d{8}$/', $phone)) {
            return '+'.$phone;
        }

        return preg_match('/^\+2519\d{8}$/', $phone) ? $phone : null;
    }

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
                'max:64',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
            ],
            'phone'      => [
                'required',
                'string',
                'unique:users,phone',
                'regex:/^\+?[1-9]\d{6,14}$/',
                // Ethiopian mobiles: +251 then 9 digits starting with 9 (Ethio telecom) or 7 (Safaricom).
                function ($attribute, $value, $fail) {
                    if (preg_match('/^\+?251/', (string) $value) && !preg_match('/^\+?251[79]\d{8}$/', (string) $value)) {
                        $fail(__('Enter a valid Ethiopian mobile number: +251 followed by 9 digits starting with 9 or 7'));
                    }
                },
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
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        } else {
            $user = \App\User::create([
                'first_name' => trim($request->input('first_name')),
                'last_name'  => trim($request->input('last_name')),
                'email'      => strtolower(trim($request->input('email'))),
                'password'   => Hash::make($request->input('password')),
                'status'     => 'publish',
                'phone'      => trim($request->input('phone')),
            ]);
            $user->assignRole('customer');
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

            return $this->sendSuccess(__('Register successfully'));
        }
    }

    /**
     * Resend email verification link (JSON API, Sanctum Bearer).
     * Body may be empty JSON. Same mail rules as web (site setting + mail config).
     */
    public function resendEmailVerification(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->sendSuccess([
                'email_verified' => true,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            ], __('Your email is already verified.'));
        }

        if (! setting_verify_email_register_enabled()) {
            return $this->sendError(__('Email verification is not enabled on this site.'), [
                'code' => 'verification_disabled',
            ]);
        }

        try {
            $user->sendEmailVerificationNotification();
        } catch (Throwable $e) {
            Log::warning('resendEmailVerification: '.$e->getMessage(), ['exception' => $e]);
            $data = ['code' => 'mail_failed'];
            if (config('app.debug')) {
                $data['debug_message'] = $e->getMessage();
            }

            return $this->sendError(__('We could not send the verification email. Please try again later.'), $data);
        }

        $payload = [
            'email_verified' => false,
            'sent' => true,
            'mail_transport' => config('mail.default'),
        ];
        if (config('mail.default') === 'log') {
            $payload['inbox_delivery'] = false;
            $payload['notice'] = __(
                'Mail is using the "log" driver: the message is written to the application log file, not to a real mailbox. Set SMTP, Mailgun, etc. in .env or Admin → Settings → Email to receive real email.'
            );
        } else {
            $payload['inbox_delivery'] = true;
        }

        return $this->sendSuccess($payload, __('Verification link has been sent to your email address.'));
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $user = auth()->user();

        $user['profile_image'] = null;

        if(!empty($user['avatar_id'])){
            $user['avatar_url'] = get_file_url($user['avatar_id'],'full');
            $user['avatar_thumb_url'] = get_file_url($user['avatar_id']);
            $user['profile_image'] = $user['avatar_url'];
        }

        return $this->sendSuccess([
            'data'=>$user
        ]);
    }

    public function updateUser(Request $request){
        $user = Auth::user();
        $rules = [
            'first_name' => 'required|max:255',
            'last_name'  => 'required|max:255',
            'email'      => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id)
            ],
        ];
        $messages = [
            'first_name.required' => __('The first name is required field'),
            'last_name.required'  => __('The last name is required field'),
            'email.required'       => __('The email field is required'),
        ];
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $user->fill($request->input());
        $user->birthday = date("Y-m-d", strtotime($user->birthday));
        $user->save();
        return $this->sendSuccess(__('Update successfully'));
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->sendSuccess(__('Successfully logged out'));
    }

    public function changePassword(Request $request){

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return $this->sendError('',['errors'=>$validator->errors()]);
        }
        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->sendError(__("Current password is not correct"),['code'=>'invalid_current_password']);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        // Invalidate all Tokens
        $user->tokens()->delete();

        return $this->sendSuccess(['message'=>__("Password updated. Please re-login"),'code'=>"need_relogin"]);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'email' => 'required|email'
        ]);
        if($validator->fails()){
            return $this->sendError('',['errors'=>$validator->errors()]);
        }

        $user = User::where('email', $request->email)->first();

        if(is_null($user)){
            return $this->sendError(__("User not found!"));
        }

        $token = rand(100000, 999999);

        \App\Models\PasswordReset::create(['email'=>$user->email,'token'=>$token]);

        Mail::to($user->email)->send(new ResetPasswordToken($token, $user,true));
        return $this->sendSuccess(['message'=>__("We have e-mailed your password token")]);

    }

    public function resetPassword(Request $request)
    {

        $validator = Validator::make($request->all(),[
            'email' => 'required|email',
            'token' => 'required',
            // Same strength as registration.
            'password' => ['required', 'confirmed', 'string', 'max:64', Password::min(8)->mixedCase()->numbers()],
        ]);
        if($validator->fails()){
            return $this->sendError('',['errors'=>$validator->errors()]);
        }
        $user = User::where('email', $request->email)->first();

        if(is_null($user)){
            return $this->sendError(__("User not found"));
        }

        $passwordToken = \App\Models\PasswordReset::where('email',$user->email)->where('token',$request->input('token'))->first();
        if(is_null($passwordToken)){
            return $this->sendError(__('This password reset token is invalid.'));
        }

        if(Carbon::parse($passwordToken->created_at)->addMinutes(config('auth.passwords.users.expire'))->isPast()){
            \App\Models\PasswordReset::where('email',$user->email)->where('token',$request->input('token'))->delete();
            return $this->sendError(__('This password reset token is invalid.'));
        }


        $user->password = Hash::make($request->input('password'));
        if($user->save()){
            \App\Models\PasswordReset::where('email',$user->email)->where('token',$request->input('token'))->delete();
            return $this->sendSuccess(['message'=>"Reset password success!"]);
        }
        return $this->sendError(__('Reset password fail!'));

    }
}
