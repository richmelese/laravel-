<?php
namespace Modules\Booking\Admin;

use App\BaseComponent;
use Modules\Booking\Models\Booking;
use Livewire\Attributes\Computed;

class EditBookingPage extends BaseComponent
{
    public $id;

    // Form
    public $status;
    public $object_model;
    public $object_id;


    public function mount($id)
    {
        $this->id = $id;

        if(!$this->booking){
            return redirect()->route('booking.admin.index');
        }

        $this->setupFormData();
    }

    public function render()
    {
        $data = [
            'booking' => $this->booking,
            'page_title'=>__("Edit Booking #:index",['index'=>$this->booking->id]),
            'breadcrumbs'=>[
                ['name'=>__("All Bookings"), 'url'=>route('booking.admin.index')],
                ['name'=>__("Edit Booking #:index",['index'=>$this->booking->id])],
            ],
            'statues'=>config('booking.statuses'),
        ];
        return view('Booking::admin.booking.edit', $data)->extends('Layout::admin.app', $data);
    }

    #[Computed]
    public function booking()
    {
        return Booking::find($this->id);
    }

    #[Computed]
    public function service()
    {
        return $this->booking->service ?? null;
    }

    protected function setupFormData()
    {
        $this->status = $this->booking->status;
        $this->object_model = $this->booking->object_model;
        $this->object_id = $this->booking->object_id;
    }


    public function submit()
    {
        $rules = [
            'status' => 'required',
            'object_model' => 'required',
            'object_id' => 'required',
        ];
        $dataToValidate = [
            'status' => $this->status,
            'object_model' => $this->object_model,
            'object_id' => $this->object_id,
        ];
        // Validate
        $this->validate($rules, $dataToValidate);

        $this->booking->update([
            'status' => $this->status,
            'object_model' => $this->object_model,
            'object_id' => $this->object_id,
        ]);
        $this->dispatch('alert', ['type' => 'success', 'message' => __('Booking updated successfully')]);
        $this->redirect(route('booking.admin.index'));
    }
}
