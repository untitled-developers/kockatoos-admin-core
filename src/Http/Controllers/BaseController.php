<?php


namespace UntitledDevelopers\KockatoosAdminCore\Http\Controllers;


use App\Facades\Auth;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BaseController
{
    /**
     * @param string $permission
     * @return array|null
     */
    protected function authenticate(string $permission){
        /**
         * @var $user User
         */
        $user = Auth::user();
        if(!$user->hasPermission($permission))
            return ["message"=>"Permission Denied"];
        return null;
    }

    public function lockDown(){

        Log::info("Locking app by " . Auth::user()->name);
        Log::info("Creating locking file");
        try{
            Storage::put('lockDown.txt', '');
        }catch(Exception $exception){
            Log::error($exception->getMessage());
            return response(['message' => $exception->getMessage()], 500);
        }
        Log::info("Locking file created");
        return response('', 200);
    }

    public function removeLockDown(){

        Storage::delete('lockDown.txt');
        return response('', 200);
    }
}
