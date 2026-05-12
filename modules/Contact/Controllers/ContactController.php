<?php
namespace Modules\Contact\Controllers;

use App\Helpers\ReCaptchaEngine;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Matrix\Exception;
use Modules\Contact\Emails\NotificationToAdmin;
use Modules\Contact\Models\Contact;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:30,1')->only('store');
    }

    public function index(Request $request)
    {
        $data = [
            'page_title' => __("Contact Page"),
            'header_transparent'=>true,
            'breadcrumbs'       => [
                [
                    'name'  => __('Contact'),
                    'url'  => route('contact.index'),
                    'class' => 'active'
                ],
            ],
        ];
        return view('Contact::index', $data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'   => [
                'required',
                'max:255',
                'email',
            ],
            'name' => ['required'],
            'phone' => ['required'],
            'message' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        /**
         * Google ReCapcha
         */
        if(ReCaptchaEngine::isEnable()){
            $codeCapcha = $request->input('g-recaptcha-response');
            if(!$codeCapcha or !ReCaptchaEngine::verify($codeCapcha)){
                $data = [
                    'status'    => 0,
                    'message'    => __('Please verify the captcha'),
                ];
                return response()->json($data, 200);
            }
        }
        $row = new Contact($request->input());
        $row->status = 'sent';
        if ($row->save()) {
            $this->sendEmail($row);
            $data = [
                'status'    => 1,
                'message'    => __('Thank you for contacting us! We will get back to you soon'),
            ];
            return response()->json($data, 200);
        }

        return response()->json([
            'status' => 0,
            'message' => __('Something went wrong — please try again.'),
        ], 500);
    }

    protected function sendEmail($contact){
        if($admin_email = setting_item('admin_email')){
            try {
                Mail::to($admin_email)->send(new NotificationToAdmin($contact));
            }catch (Exception $exception){
                Log::warning("Contact Send Mail: ".$exception->getMessage());
            }
        }
    }

    public function t(){
        return new NotificationToAdmin(Contact::find(1));
    }
}
