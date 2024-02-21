<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Order;
use App\Models\CustomerRoute;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Http\Request;
 class CustomersExport implements FromCollection
{
    public function collection()
    {
         $customers=User::orderBy('name', 'desc')
        ->select('users.name','phone','customer_routes.name as routename','users.id','users.email')
        ->leftjoin("customer_routes","customer_routes.id","users.customerroute_id")
        ->where("user_type","customer")
        ->with('route')
         ->where("user_type","customer")
         ->orderBy('name', 'desc')
        ->get();

        
        $final=[];
        foreach($customers as $customer)
        {
         $orders = Order::orderBy('id', 'desc')
        ->where('payment_status',"unpaid")
        ->where('user_id',$customer->id);
        
            $rt=($customer->route);
            $orders=$orders->get();
            /*if(isset($customer->route))
            {
             $output['route']=$customer->route->name;
            }
            else{
                 $output['route']="None";
            }*/
            
            
              $output['route']=$customer->routename;
              
             $output['telephone']=$customer->phone;
              $output['id']=$customer->id;
            $output['name']=$customer->name;
            if(isset($rt))
            {
                  $output['route']=$rt->name;
            }
            else{
                  $output['route']="no route";
            }
          
            $output['sales']=number_format(sizeof($orders));
            $debt=0;
            foreach($orders as $or)
            {
                if(($or->grand_total-$or->amount_paid)>0)
                {
                     $debt+=$or->grand_total-$or->amount_paid;
                }
            }
             $output['debt']=number_format($debt);
             /*if($debt>0)
             {*/
            array_push($final,$output);
            // }
        }
        return $customers;
    }
}

?>
