<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\User;
use App\Models\Order;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $sort_search = null;
        $users = User::where('user_type', 'customer')
        /*->where('email_verified_at', '!=', null)
        */->orderBy('created_at', 'desc');
        if ($request->has('search')){
            $sort_search = $request->search;
            $users->where(function ($q) use ($sort_search){
                $q->where('name', 'like', '%'.$sort_search.'%')->orWhere('email', 'like', '%'.$sort_search.'%');
            });
        }
        if($request->export != null)
        {
            //return $users->get();
          $this->exportCustomer($users->get());
        }
        else{
        $users = $users->with('route')->paginate(50);
       return view('backend.customer.customers.index', compact('users', 'sort_search'));
        }
        
    }
    
    //points
    public function points(Request $request)
    {
        $sort_search = null;
        $users = User::where('user_type', 'customer')
        ->where('points', '>',0);
        if ($request->has('search')){
            $sort_search = $request->search;
            $users->where(function ($q) use ($sort_search){
                $q->where('name', 'like', '%'.$sort_search.'%')->orWhere('email', 'like', '%'.$sort_search.'%');
            });
        }
        if($request->export != null)
        {
            //return $users->get();
          $this->exportCustomer($users->get());
        }
        else{
        $users = $users->with('route')
        ->orderBy("users.points","desc")
        ->paginate(50);
       return view('backend.customer.customers.points', compact('users', 'sort_search'));
        }
        
    }
      
    public function exportCustomer($data)
    {
        
     $delimiter = ","; 
    $filename = "customers_" . date('Y-m-d') . ".csv"; 
    // Create a file pointer 
    $f = fopen('php://memory', 'w'); 
    // Set column headers 
    $fields = array('Name','Email', 'Phone', 'Route'); 
    fputcsv($f, $fields, $delimiter); 
    // Output each row of the data, format line as csv and write to file pointe
    foreach($data as $row){ 
        
        $route="";
        if(isset($row->route))
        {
            $route=$row->route->name;
        }
         $lineData = array($row->name,$row->email, $row->phone,$route); 
        fputcsv($f, $lineData, $delimiter); 
    } 
    
    $this->export($f,$filename);
  
    }
    
      public function export($f,$filename){
           
    // Move back to beginning of file 
    fseek($f, 0); 
    // Set headers to download file rather than displayed 
    header('Content-Type: text/csv'); 
    header('Content-Disposition: attachment; filename="' . $filename . '";'); 
     
    //output all remaining data on a file pointer 
    fpassthru($f); 
    }
    

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required',
            'email'         => 'required|unique:users|email',
            'phone'         => 'required|unique:users',
        ]);
        
        $response['status'] = 'Error';
        
        $user = User::create($request->all());
        
        $customer = new Customer;
        
        $customer->user_id = $user->id;
        $customer->save();
        
        if (isset($user->id)) {
            $html = '';
            $html .= '<option value="">
                        '. translate("Walk In Customer") .'
                    </option>';
            foreach(Customer::all() as $key => $customer){
                if ($customer->user) {
                    $html .= '<option value="'.$customer->user->id.'" data-contact="'.$customer->user->email.'">
                                '.$customer->user->name.'
                            </option>';
                }
            }
            
            $response['status'] = 'Success';
            $response['html'] = $html;
        }
        
        echo json_encode($response);
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
        $user = User::findOrFail(decrypt($id));
        return view('backend.customer.customers.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $id=$request->id;
         $user = User::findOrFail(($id));
          $user->name=$request->name;
           $user->phone=$request->phone;
             $user->email=$request->email;
             $user->save();
             //return redirect()->route('customers.index');
            session()->flash('message', 'Customer edited successfully!'); 
             return back()->with('success','Customer edited successfully!');
        
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        User::destroy($id);
        flash(translate('Customer has been deleted successfully'))->success();
        return redirect()->route('customers.index');
    }
    
    public function bulk_customer_delete(Request $request) {
        if($request->id) {
            foreach ($request->id as $customer_id) {
                $this->destroy($customer_id);
            }
        }
        
        return 1;
    }

    public function login($id)
    {
        $user = User::findOrFail(decrypt($id));

        auth()->login($user, true);

        return redirect()->route('dashboard');
    }

    public function ban($id) {
        $user = User::findOrFail(decrypt($id));

        if($user->banned == 1) {
            $user->banned = 0;
            flash(translate('Customer UnBanned Successfully'))->success();
        } else {
            $user->banned = 1;
            flash(translate('Customer Banned Successfully'))->success();
        }

        $user->save();
        
        return back();
    }
}
