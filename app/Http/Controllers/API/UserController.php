<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index($id=null)
    {
        if ($id == '') {
            $users = User::get();
        }else if($id != ''){
            $users = User::find($id);
        }

        return response()->json(['users'=>$users], 200);
    }

    public function store(Request $request){
        $data = $request->all();

        // validate rules
        $validateRules = [
            'name'     => 'required',
            'email'    => 'required|unique:users,email',
            'password' => 'required'
        ];

        // validate message
        $validateMessage = [
            'name.required'     => 'The name field is required',
            'email.required'    => 'The email field is required',
            'password.required' => 'The password field is required',
        ];

        $validated = Validator::make($data,$validateRules,$validateMessage);
        if ($validated->fails()) {
            return response()->json($validated->errors(), 422);
        }else{
            User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password)
            ]);

            return response()->json([
                'message'=>'User added successfull'
            ],200);
        }
    }
}
