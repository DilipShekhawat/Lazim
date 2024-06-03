<?php

namespace App\Http\Controllers;
use App\User;
use Validator;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\RegisterRequest;

class AuthController extends Controller
{
    /**
     * @param Request
     * @return Http Response
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:users,email',
                'name' => 'required|string',
                'password' => [
                    'required',
                    'min:6',
                    'max:64',
                    'regex:/[a-z]/', // must contain at least one lowercase letter
                    'regex:/[A-Z]/', // must contain at least one uppercase letter
                    'regex:/[0-9]/', // must contain at least one digit
                    'regex:/[-@$!%*#?&]/',
                ],
            ]);
            if ($validator->fails()) {
                $response = [
                    'success' => false,
                    'message' => $validator->errors()
                ];
                return response()->json($response, 400);
            }
            $result = DB::transaction(function () use ($request) {
                $email = trim($request->input('email'));
                $name = trim($request->input('name'));
                $verificationCode = Str::random(30); //Generate verification code
                $user = User::create([
                    'email' => $email,
                    'password' => bcrypt(trim($request->input('password'))),
                    'name' => $name,
                    'remember_token' => $verificationCode,
                ]);
                return response()->json(setResponse([], ['message' => 'Register Successfully!']))->setStatusCode(Response::HTTP_CREATED);
            });
            return $result;
        } catch (\Exception $e) {
            return response()->json(setErrorResponse(__($e->getMessage()),null,true))->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

      /**
     * @param Request
     * @return Http Response
     */
    public function login (Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => [
                'required',
                'min:6',
                'max:64',
                'regex:/[a-z]/', // must contain at least one lowercase letter
                'regex:/[A-Z]/', // must contain at least one uppercase letter
                'regex:/[0-9]/', // must contain at least one digit
                'regex:/[-@$!%*#?&]/',
            ],
        ]);
        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        $user = User::where('email', $request->email)->first();
        if ($user) {
            if (Hash::check($request->password, $user->password)) {
                $token = $user->createToken('Laravel Password Grant Client')->accessToken;
                $response = ['token' => $token];
                return response($response, 200);
            } else {
                $response = ["message" => "Password mismatch"];
                return response($response, 422);
            }
        } else {
            $response = ["message" =>'User does not exist'];
            return response($response, 422);
        }
    }
}
