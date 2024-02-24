<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Staff;
use App\Models\User;
use App\Models\Role;
use App\Models\Driver;
use Hash;
use Auth;

class DriversController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

         $staffs = User
    ::where("company_id",Auth::user()->id)
        ->paginate(10);
        return view('frontend.user.drivers.index', compact('staffs'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $roles = Role::all();
        return view('frontend.user.drivers.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if(User::where('email', $request->email)->first() == null){
            $user = new User;
            $user->name = $request->name;
            $user->email = $request->email;
            $user->phone = $request->mobile;
            $user->company_id = Auth::user()->id;
            $user->user_type = "driver";
            $user->password = Hash::make($request->password);
            if($user->save()){
                $driver=new Driver;
                $driver->user_id=$user->id;
                $driver->PermitNumber=$request->permit_no;
                $driver->NIN=$request->nin;
                $driver->Nationalid_photo = $request->nin_photos;
                $driver->photo = $request->photo;
                $driver->Permit_photo = $request->permit_photos;
                $driver->save();
                /*$staff = new Staff;
                $staff->user_id = $user->id;
                $staff->role_id = $request->role_id;
                if($staff->save()){*/
                    flash(translate('Driver has been inserted successfully'))->success();
                    return redirect()->route('drivers.index');
               // }
            }
        }
        flash(translate('Email already used'))->error();
        return back();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $staff = User::findOrFail(decrypt($id));
        $roles = Role::all();
        return view('frontend.user.drivers.edit', compact('staff', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //$staff = Staff::findOrFail($id);
        $user =User::findOrFail($id);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->mobile;
        if(strlen($request->password) > 0){
            $user->password = Hash::make($request->password);
        }
        if($user->save()){
           /* $staff->role_id = $request->role_id;
            if($staff->save()){*/
                flash(translate('User has been updated successfully'))->success();
                return redirect()->route('drivers.index');
           // }
        }

        flash(translate('Something went wrong'))->error();
        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
       
        Driver::where("user_id",$id)->delete();
        User::destroy($id);
        /*if(Staff::destroy($id)){*/
            flash(translate('Driver has been deleted successfully'))->success();
            return redirect()->route('drivers.index');
        /*}
        flash(translate('Something went wrong'))->error();
        return back();*/
    }
}
