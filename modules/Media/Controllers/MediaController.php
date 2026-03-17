<?php
namespace Modules\Media\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Media\Helpers\FileHelper;
use Modules\Media\Traits\HasUpload;
use Illuminate\Support\Facades\Crypt;
use App\Helpers\SimpleHashids;

class MediaController extends Controller
{
    use HasUpload;

    public function preview($id, $size = 'thumb')
    {
        return redirect(FileHelper::url($id, $size));
    }

    public function privateFileStore(Request $request)
    {
        $hashids = app(SimpleHashids::class);
        $fileName = 'file';
        try{
            $request->merge([
                'is_private'=>1
            ]);

            $fileObj = $this->uploadFile($request,$fileName,'private_verification',0,['should_hash_file_name'=>true,'folder_subfix'=>'private']);

            $data = [
                'name'=>$fileObj->file_name,
                'code'=>$hashids->encode($fileObj->id),
                'mime'=>$fileObj->mime_type,
                'size'=>$fileObj->file_size,
                'file_extension'=>$fileObj->file_extension,
                'download'=>route('media.private.view',['code'=>$hashids->encode($fileObj->id)])
            ];
            return $this->sendSuccess(['data' => $data]);

        } catch (\Exception $exception) {
            return $this->sendError($exception->getMessage());
        }
    }


    public function privateFileView($code = '')
    {
        if(!$code){
            abort(404);
        }
        $fileId = app(SimpleHashids::class)->decode($code);

        $url = get_file_url($fileId,'full');
        
        if(!$url){
            abort(404);
        }

        return redirect($url);
    }

}
