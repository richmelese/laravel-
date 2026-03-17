<?php

namespace Modules\Sms\Core\Drivers;

use Illuminate\Support\Facades\Log;

class NullDriver extends Driver
{
    /**
     * {@inheritdoc}
    */
    public function send()
    {
    	$data = [
    		'to'=>$this->recipient,
		    'message'=>$this->message,
		    'time'=>time()
	    ];
    	Log::info("Email Content: ".json_encode($data));
        return [];
    }
}