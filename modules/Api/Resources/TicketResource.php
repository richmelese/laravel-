<?php

namespace Modules\Api\Resources;

use App\Resources\BaseJsonResource;

class TicketResource extends BaseJsonResource
{
    public function toArray($request)
    {

        $qrUrl = route('api.user.my-ticket.qr-image',['ticket_id'=>$this->id]);
                    
        return [
            'id' => $this->id,
            'index'=>$this->index,
            'email' => $this->email,
            'first_name'=>$this->first_name,
            'last_name'=>$this->last_name,
            'phone'=>$this->phone,
            'dob'=>$this->dob,
            'id_card'=>$this->id_card,
            'meta'=>$this->meta,
            'is_scanned'=>$this->is_scanned,
            'scanned_by'=>$this->scanned_by,
            'scanned_at'=>$this->scanned_at,
            'seat_type'=>$this->seat_type,
            'qr_code_url'=>$this->whenNeed('qr_code_url',$qrUrl)
        ];
    }
}
