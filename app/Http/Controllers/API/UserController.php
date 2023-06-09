<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use GuzzleHttp\Promise\Create;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index(int $id = null){
        if ($id == '') {
            $data  = User::all();
        }else{
            $data = User::find($id);
            if (empty($data)) {
                $data = 'Not Found!';
            }
        }

        return response()->json(['users'=>$data],200);
    }

    public function store(Request $request){
        if ($request->isMethod('post')) {
            $data = $request->all();
            // validation rules
            $rules = [
                'name'     => ['required','string'],
                'email'    => ['required','email','unique:users,email'],
                'password' => ['required']
            ];

            // validation message
            $message = [
                'name.required'=>'The name field is required.',
                'email.required'=>'The email field is required.',
                'password.required'=>'The password field is required.'
            ];

            $validator = Validator::make($data,$rules,$message);

            if ($validator->fails()) {
                return response()->json(['validation'=>$validator->errors()],422);
            }else{
                User::create([
                    'name'     => $request->name,
                    'email'    => $request->email,
                    'password' => $request->password
                ]);

                return response()->json(['message'=>'Data has been saved successfull.'],201);
            }
        }
    }

    public function multiUser(Request $request){
        $data = $request->all();
        // validator rules
        $rules = [
            'users.*.name'=>'required|string',
            'users.*.email'=>'required|email|unique:users,email',
            'users.*.password'=>'required'
        ];

        // validator message
        $message = [
            'users.*.name.required'=>'The name field is required.',
            'users.*.email.required'=>'The email field is required.',
            'users.*.password.required'=>'The password field is required.'
        ];

        $validator = Validator::make($data,$rules,$message);
        if ($validator->fails()) {
            return response()->json(['validation'=>$validator->errors()],422);
        }else{
            if (!empty($data['users'])) {
                foreach ($data['users'] as $value) {
                    User::create([
                        'name'     => $value['name'],
                        'email'    => $value['email'],
                        'password' => $value['password']
                    ]);
                }
                return response()->json(['message'=>'Data has been saved successfull.'],201);
            }else{
                return response()->json(['Error'=>'User data empty'],422);
            }
        }
    }

    public function update(Request $request, $id){
       if ($request->isMethod('put')) {
            $data = $request->all();
            // validation rules
            $rules = [
                'name'     => ['required','string'],
                'email'    => ['required','email','unique:users,email,'.$id],
                'password' => ['required']
            ];

            // validation message
            $message = [
                'name.required'     => 'The name field is required.',
                'email.required'    => 'The email field is required.',
                'password.required' => 'The password field is required.'
            ];

            $validator = Validator::make($data,$rules,$message);

            if ($validator->fails()) {
                return response()->json(['validation'=>$validator->errors()],422);
            }else{
                User::findOrFail($id)->update([
                    'name'     => $request->name,
                    'email'    => $request->email,
                    'password' => $request->password
                ]);

                return response()->json(['message'=>'Data has been updated successfull.'],202);
            }
       }
    }

    public function delete(Request $request, $id){
        if ($request->isMethod('delete')) {
            User::findOrFail($id)->delete();
            return response()->json(['message'=>'Data has been deleted successfull.'],200);
        }
    }

    public function multiDelete(Request $request){
        if ($request->isMethod('post')) {
            $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IldlYiBUb2tlbiIsImlhdCI6MTUxNjIzOTAyMn0.nryRkCTM8eXsBNEW7AXphuouFdSSqjgGP0qxI4XrzUM';

            $header = $request->header('Authorization');
            if ($header == '') {
                return response()->json(['message'=>'Authorization not empty!'],422);
            }else{
                if ($header == $token) {
                    $data = $request->all();
                    User::destroy($data['ids']);
                    return response()->json(['message'=>'Select data has been deleted successfull.'],200);
                }else{
                    return response()->json(['message'=>'Authorization doesn\'t match!'],422);
                }
            }
        }
    }

    public function register(Request $request){
        if ($request->isMethod('post')) {
            $data = $request->all();

            // validate rules
            $rules = [
                'name'     => 'required|string',
                'email'    => 'required|email|unique:users,email',
                'password' => 'required|',
            ];
            // validate message
            $message = [
                'name.required'     => 'The name field is required.',
                'email.required'    => 'The email field is required.',
                'password.required' => 'The password field is required.'
            ];

            $validator = Validator::make($data,$rules,$message);
            if ($validator->fails()) {
                return response()->json(['error'=>$validator->errors()],422);
            }else{
                DB::beginTransaction();
                try {
                    User::create($data);
                    if (Auth::attempt(['email' => $data['email'], 'password' => $data['password']])) {
                        $user = User::where('email',$data['email'])->first();
                        $access_token = $user->createToken($data['email'])->accessToken;
                        $user->update(['access_token'=>$access_token]);
                        DB::commit();
                        return response()->json(['message'=>'User Successfull Registerd.','token'=>$access_token],200);
                    }else{
                        return response()->json(['message'=>'OPPS! Somthing went wrong.'],422);
                    }

                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json(['error'=>$e->getMessage()],422);
                }
            }
        }
    }

    public function login(Request $request){
        $data = $request->only(['email','password']);
        // validate rules
        $rules = [
            'email'    => 'required|email',
            'password' => 'required|',
        ];
        // validate message
        $message = [
            'email.required'    => 'The email field is required.',
            'password.required' => 'The password field is required.'
        ];

        $validator = Validator::make($data,$rules,$message);
        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()],422);
        }else{
            if (Auth::attempt(['email'=>$data['email'],'password'=>$data['password']])) {
                $user = User::where('email',$data['email'])->first();
                $access_token = $user->createToken($data['email'])->accessToken;
                $user->update(['access_token'=>$access_token]);

                return response()->json(['message'=>'User Successfull Login.','token'=>$access_token],200);
            }else{
                return response()->json(['message'=>'Invalid email or password.'],422);
            }
        }
    }
}
