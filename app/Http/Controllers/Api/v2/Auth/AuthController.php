<?php

namespace App\Http\Controllers\Api\v2\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ForgotPasswordMail;
use App\Models\Friend;
use App\Models\Notification;
use App\Models\Otp;
use App\Models\User;
use App\Traits\AppResponse;
use App\Traits\HttpAppResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Laravel\Jetstream\Jetstream;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use HttpAppResponse;
    // old user handaling,
    public function old_user_create(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => 'required',
            'password_confirmation' => 'required',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ]);
        $user = User::create([
            'user_type' => '2',
            'redrose_id' => $request['redrose_id'],
            'name' => $request['name'],
            'email' => $request['email'],
            'password' => Hash::make($request['password']),
            'date' => now()->toDateString(),
            'once' => 'no',
            'phone' => $request['phone'],
            'points' => $request['points'],
        ]);
        if ($user) {
            Friend::create([
                'user_id' => $user->id,
                'friend_id' => '2',
                'type' => '1',
            ]);
            Notification::create([
                'user_id' => $user->id,
                'name' => $request['name'] . ' User Create from app successful.!',
                'date' => now()->toDateString(),
                'time' => now()->toTimeString(),
                'type' => 'user',
                'status' => '1',
            ]);
            return $this->apiResponse($user, true, 'User Created Successfull.!', AppResponse::HTTP_OK);
        } else {
            return $this->apiResponse('', false, "User can't Create.!", AppResponse::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
    // old user handaling end
    // Login in function start
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);
        if (filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', $request->email)->first();
            if ($user && Hash::check($request->password, $user->password)) {
                Notification::create([
                    'user_id' => $user->id,
                    'name' => $user->name . ' Login by email.!',
                    'date' => now()->toDateString(),
                    'time' => now()->toTimeString(),
                    'type' => 'login',
                    'status' => '1',
                ]);
                return $this->apiResponse(
                    [
                        'user' => $user,
                        'token' => $user->createToken('API Token of ' . $user->name)->plainTextToken,
                    ],
                    true,
                    'You have successfully logged in.!',
                    AppResponse::HTTP_OK,
                );
            } else {
                return $this->apiResponse('', false, 'Credentials does not match', AppResponse::HTTP_UNAUTHORIZED);
            }
        }
    }
    // Register function start
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => 'required',
            'password_confirmation' => 'required',
            // 'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ]);
        $redrose_id = str_replace(' ', '', Str::lower($request['name']) . rand(100, 99999));
        $user = User::create([
            'user_type' => '2',
            'redrose_id' => $redrose_id,
            'name' => $request['name'],
            'email' => $request['email'],
            'password' => Hash::make($request['password']),
            'date' => now()->toDateString(),
            'once' => 'no',
            'phone' => $request['phone'],
            'points' => '10',
        ]);
        if ($user) {
            Friend::create([
                'user_id' => $user->id,
                'friend_id' => '2',
                'type' => '1',
            ]);
            Notification::create([
                'user_id' => $user->id,
                'name' => $request['name'] . ' User Create from web successful.!',
                'date' => now()->toDateString(),
                'time' => now()->toTimeString(),
                'type' => 'user',
                'status' => '1',
            ]);
            return $this->apiResponse(
                [
                    'user' => $user,
                    'token' => $user->createToken('API Token of ' . $user->name)->plainTextToken,
                ],
                true,
                'Congratulation, you have successfully created your account. Thank you for choosing RedRose Academy.!',
                AppResponse::HTTP_CREATED,
            );
        } else {
            return $this->apiResponse('', false, "User can't Create.!", 401);
        }
    }

    function forget_password(Request $request)
    {
        $data = [
            'email' => $request->input('email'),
        ];
        $roules = [
            'email' => 'required',
        ];

        if ($this->request_validator($data, $roules)) {
            return $this->apiresponse('', false, 'Make sure you need to fill all the required parametter.', AppResponse::HTTP_NOT_ACCEPTABLE);
        } else {
            if (filter_var($request->input('email'), FILTER_VALIDATE_EMAIL)) {
                $email = $request->input('email');
                $is_email = User::where('email', $email)->first();
                if ($is_email) {
                    $old_otp = Otp::where('email', $email)->first();
                    if ($old_otp != null) {
                        $random = rand(100000, 999999);
                        $content = [
                            'subject' => 'Forget Password OPT',
                            'name' => $email,
                            'otp' => $random,
                        ];
                        try {
                            Mail::to($email)->send(new ForgotPasswordMail($content));
                            Otp::updateStore($email, $random, $old_otp->id);
                            return $this->apiresponse(['email' => $email], true, 'An OTP has been changed and resend it your Email, Please check your mail box, (if need check spam flder)...!', AppResponse::HTTP_ALREADY_REPORTED);
                        } catch (\Exception $e) {
                            return $this->apiresponse('', false, $e->getMessage(), AppResponse::HTTP_NOT_ACCEPTABLE);
                        }
                    } else {
                        $random = rand(100000, 999999);
                        $content = [
                            'subject' => 'Forget Password OPT',
                            'name' => $email,
                            'otp' => $random,
                        ];
                        try {
                            Mail::to($email)->send(new ForgotPasswordMail($content));
                            Otp::createStore($email, $random);
                            return $this->apiresponse(['email' => $email], true, 'An OTP has been send your Email, Please check your mail box, (if need check spam flder)...!', AppResponse::HTTP_OK);
                        } catch (\Exception $e) {
                            return $this->apiresponse('', false, $e->getMessage(), AppResponse::HTTP_NOT_ACCEPTABLE);
                        }
                    }
                } else {
                    return $this->apiresponse('', false, 'Not found any account with this email, Please try again with Correct email address...!', AppResponse::HTTP_NOT_FOUND);
                }
            } else {
                return $this->apiresponse('', false, 'Make sure you need to input your valid email address or phone numbre.', AppResponse::HTTP_NOT_ACCEPTABLE);
            }
        }
    }
    function confirm_password_verify(Request $request)
    {
        $data = [
            'email' => $request->input('email'),
            'password' => $request->input('password'),
            'otp' => $request->input('otp'),
        ];
        $roules = [
            'email' => 'required',
            'password' => 'required',
            'otp' => 'required',
        ];

        if ($this->request_validator($data, $roules)) {
            return $this->apiresponse('', false, 'Make sure you need to fill all the required parametter.', AppResponse::HTTP_NOT_ACCEPTABLE);
        } else {
            $email = $request->input('email');

            $otp = Otp::where('email', $email)->first();
            if ($request->otp == $otp->otp) {
                $user = User::where('email', $email)->first();
                User::where('id', $user->id)->update(['password' => Hash::make($request->password)]);
                Otp::destroyStore($otp->id);
                return $this->apiresponse('',true,'You successfully change your account password with Email',AppResponse::HTTP_OK);
            } else {
                return $this->apiresponse('', false, 'OTP Dose not match, please try again latter.!', AppResponse::HTTP_UNPROCESSABLE_ENTITY);
            }
        }
    }

    // Logout start
    public function logout(Request $request)
    {
        $request
            ->user()
            ->currentAccessToken()
            ->delete();
        return $this->apiResponse('', true, 'You have successfully logout', 200);
    }
    public function refreshToken()
    {
        $token = $this->getToken();
        return $this->apiResponse(['token' => $token], true, 'Token refresh successful...', AppResponse::HTTP_OK);
    }
}
