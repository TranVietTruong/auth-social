<?php

namespace App\Http\Controllers;

use Validator;
use Illuminate\Support\Facades\DB;
use App\Helpers\Response;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function url($request)
    {
        $validation = Validator::make($request, [
            'driver'    => 'required',
            'google_client_id' => 'required',
            'google_client_secret' => 'required',
            'google_redirect_url' => 'required',
        ]);

        if ($validation->fails())
        {
            return Response::dataError($validation->messages()->first());
        }

        $url = Socialite::driver($request['driver'])->with([
                   ['client_id' => $request['google_client_id']],
                   ['client_secret' => $request['google_client_secret']],
                   ['redirect' => $request['google_redirect_url']]
               ])->stateless()->redirect()->getTargetUrl();

        return Response::data($url,1);
    }

    public function authentication($request) {
        $validation = Validator::make($request, [
            'driver' => 'required',
            'token'    => 'required',
        ]);

        if ($validation->fails()){
            return Response::dataError($validation->messages()->first());
        }

        try
        {
            $user_social = Socialite::driver($request['driver'])->stateless()->userFromToken($request['token']);
            $exist_user = User::where('provider_id', $user_social->id)->first();
            if ($exist_user)
            {
                $token = auth()->login($exist_user);
                $data = $this->respondWithToken($token);
                return Response::data($data, 1);
            }
            else
            {
                \DB::beginTransaction();
                $user = new User();
                $fill_data = [
                    'name' => $user_social->name,
                    'avatar' => $user_social->avatar,
                    'email' => $user_social->email,
                    'provider_name' => 'google',
                    'provider_id' => $user_social->id
                ];
                $user->fill($fill_data)->save();
                \DB::commit();
                $token = auth()->login($user);
                $data = $this->respondWithToken($token);
                return Response::data($data, 1);
            }
        }
        catch (\Exception $exception)
        {
            \DB::rollback();
            Response::dataError();
        }
    }


    public function user($request)
    {
        if(!$this->checkToken($request))
        {
            return Response::dataError('Unauthorized', 401);
        }
        $data = auth()->setToken($request['token'])->user();
        return Response::data($data, 1);
    }


    public function logout($request)
    {
        if(!$this->checkToken($request))
        {
            return Response::dataError('Unauthorized', 401);
        }
        auth()->setToken($request['token'])->logout();
        return Response::data('Successfully', 1);
    }


    public function refresh($request)
    {
        if(!$this->checkToken($request))
        {
            return Response::dataError('Unauthorized', 401);
        }
        $token = auth()->setToken($request['token'])->refresh();
        $data = $this->respondWithToken($token);
        return Response::data($data, 1);
    }

    public function checkToken($request)
    {
        $validation = Validator::make($request, [
            'token'    => 'required',
        ]);

        if ($validation->fails())
        {
            return false;
        }
        if(!auth()->setToken($request['token'])->check())
        {
            return false;
        }
        return true;
    }

    protected function respondWithToken($token)
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ];
    }
}
