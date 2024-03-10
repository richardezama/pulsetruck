<?php

/** @noinspection PhpUndefinedClassInspection */

namespace App\Http\Controllers;

use App\Http\Controllers\OTPVerificationController;
use App\Models\BusinessSetting;
use App\Models\Customer;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Pointlog;
use App\Models\Order;
use App\Notifications\AppEmailVerificationNotification;
use Hash;
use App\Models\Cart;
use App\Models\Log;
use App\Models\Payment;
use App\Models\OrderDetail;
use App\Models\ProductStock;
use App\Models\Product;
use App\Models\CustomerRoute;
use Illuminate\Support\Facades\DB;





use App\Models\Category;
use App\Models\Brand;
use App\Models\Types;




class Api2Controller extends Controller
{
    
    //change password
    
    
    
    
     public function deactivateroute(Request $request)
    {
       
       $time=date("H",time());
       if($time=="23")
       {
           $routes=CustomerRoute::where("active",1)->get();
           foreach($routes as $route)
           {
               $x=CustomerRoute::find($route->id);
              $x->active=0;
             // $x->update();
           }
       }
       
       
    }
    
    public function changepassword(Request $request,$id,$password)
    {
        //get the raw post
        $json = json_decode($request->getContent());
       // $id=$json->id;
        $user=User::find($id);
        $user->password= Hash::make($password);
        $user->update();
        return response()->json(['result' =>true, 'message' => translate('Password Changed')], 200);
    }
   

  public function searchcustomer(Request $request,$search)
    {
        if($search=="0")
        {
            $search="";
        }
        //return all the data about the customer here
        $data=Order::join("users","orders.user_id","users.id")
           // ->where("users.name","like","%".$search."%")
             ->orwhere("users.phone","like","%".$search."%")
             ->where("users.phone","=",$search)
            ->get();
            
            $json=[];
            $debt=0;
            $sales=0;
            $unpaid=0;
            $name="";
            $address="";
            foreach($data as $dt)
            {
                $name=$dt->name;
                 $address=$dt->address;
                $sales+=$dt->grand_total;
                if(($dt->grand_total-$dt->amount_paid)>0)
                {
                     $debt+=$dt->grand_total-$dt->amount_paid;
                }
                
                if($dt->payment_status=="unpaid")
                {
                     $unpaid++;
                }
               
            }
             $json['name']=($name);
             $json['address']=($address);
             
            
             $json['unpaid']=number_format($unpaid);
            $json['sales']=number_format($sales);
            $json['debt']=number_format($debt);
            $json['totalorders']=number_format(sizeof($data));
            $json['orders']=$data;
            return $json;
    }
    //checkout 
     
    public function posting(Request $request)
    {
         $json = json_decode($request->getContent());
         $name=$json->name;
         $phone=$json->phone;
         $address=$json->address;
         $email=$json->email;
         $latitude=$json->latitude;
         $longitude=$json->longitude;
         $payment_type=$json->payment_type;
         $amountpaid=$json->amountpaid;
         $routeid=$json->routeid;
         $products=$json->products;
          $output['ResponseCode']="0";
           $output['ResponseDescription']="Success";
         return $output;
         return $json;
    }
    
    
    
     public function checkoutmodified(Request $request,$user_id,$name,$phone,$address,$email,$latitude,$longitude,$payment_type,$amountpaid,$routeid,$date)
    {
        try{

            $json = json_decode($request->getContent());
            //$user_id=$json->user_id;
          
            /*
            if(Session::get('pos.shipping_info') == null
            || Session::get('pos.shipping_info')['name'] == null ||
             Session::get('pos.shipping_info')['phone'] == null || Session::get('pos.shipping_info')['address'] == null){
               return array('success' => 0, 'message' => translate("Please Add Shipping Information."));
           }
   */
  $prods = Cart::join('products','carts.product_id', '=',
  'products.id')->where('products.added_by', 'admin')
  ->where('carts.user_id',$user_id)
//get the logos here
  ->join('uploads','products.thumbnail_img', '=',
  'uploads.id')
  //brands
  ->join('brands','products.brand_id', '=',
  'brands.id')
  //categories
  ->join('categories','products.category_id', '=',
  'categories.id')

  ->join('product_stocks','product_stocks.product_id', '=',
  'products.id')

  ->select('products.*','product_stocks.id as stock_id','product_stocks.variant',
  'product_stocks.price as stock_price', 'product_stocks.qty as stock_qty',
   'product_stocks.image as stock_image','uploads.*','brands.name as brand',
   'categories.name as category','products.id as pid','product_stocks.qty','product_stocks.id as stock_id',
   'carts.id as cart_id','carts.quantity as cartquantity','carts.tax')
   ->orderBy('products.created_at', 'desc')->get();

           if(sizeof($prods)>0){
               
               $credit_limit=get_setting('credit_limit');
               $creditamount=0;
                foreach ($prods as $tds){
                       $product_stock = ProductStock::find($tds->stock_id);
                       $product = $product_stock->product;
                     
                       $product_variation = $product_stock->variant;
                       $creditamount += $product->unit_price*$tds->cartquantity;
                }
                
                //lets get the balance
                $bb=$creditamount-$amountpaid;
                if($payment_type!="cash_on_delivery" && $credit_limit<$bb) 
                {
                   return response()->json(['result' => false, 
                'message' => translate('Credit Limit Exceeded shs '.$credit_limit)], 200); 
                }
                else{
               
               $order = new Order;
              
                   $order->user_id = $user_id;
             
               if($email=="0")
               {
                   $email="";
               }
               
               if($phone=="0")
               {
                   $phone="";
               }
               
               $data['name']           = $name;
               $data['address']        = $address;
               $data['phone']          = $phone;
               $data['email']          = $email;
               $data['city']          = "";
               $data['country']          = "uganda";
               $data['postal_code']          = "";
               $order->shipping_address = json_encode($data);
               $customerid=0;
               
               $usercheck=User::where("phone",$phone)
               ->where("user_type","!=","staff")
              ->where("user_type","!=","admin")
               ->get();
               
               
               
               $cid=0;
               if(sizeof($usercheck)==0)
               {
               $user = User::create([
                   'name' => $data['name'],
                    'phone' => $data['phone'],
                   'email' => $data['email'],
                     'address' => $data['address'],
                   'type' => "auto",
                     'customerroute_id'=>$routeid,
                   'password' => Hash::make("6Ymljoput5"),
               ]);
               $user->save();
               $customer = new Customer;
               $customer->user_id = $user->id;
               $customer->save();
               $customerid=$user->id;
               $cid=$user->id;
                }
                else{
                     $apt=User::where("phone",$phone)
               ->where("user_type","!=","staff")
               ->first();
               $apt->customerroute_id=$routeid;
                $apt->address=$address;
               $apt->update();
                    //
                    /*
                    $customerdetails=Customer::where("user_id",$usercheck[0]->id)->get();
                    
                    if(sizeof($customerdetails)>0)
                    {
                     $customerid=$customerdetails[0]->user_id;
                    }*/
                 $cid=$usercheck[0]->id;
                }
                
                //check customer total orders not paid
                $ap=Order::where("user_id",$cid)->sum('amount_paid');
                $gt=Order::where("user_id",$cid)->sum('grand_total');
                
                $balances=$gt-$ap;
                $debt=$creditamount+($balances)-$amountpaid;
                 
                
                if($debt>$credit_limit && $payment_type!="cash_on_delivery")
                {
                  return response()->json(['result' => false, 
                'message' => ('Customer has  Exceeded credit limit shs '.number_format($credit_limit).", Current Debt shs ".number_format($balances)." Invoice Amount shs ".number_format($creditamount)." Outstanding Debt shs ".number_format($debt))], 200);
                }
              
                //set location here
               $order->latitude=$latitude;
               $order->route_id=$routeid;
               
               
               $order->longitude=$longitude;
               $order->payment_type = $payment_type;
               $order->delivery_viewed = '0';
               $order->sold_by=$user_id;
               $order->seller_id="0";
               $order->user_id=$cid;
               $order->payment_status_viewed = '0';
               $order->code = date('Ymd-His').rand(10,99);
               $order->date = strtotime($date);
                $order->created_at = strtotime($date);
              
               $order->payment_details = $payment_type;
               //field orders are delivered
               $order->delivery_status = "delivered";
                 DB::beginTransaction();         
               if($order->save()){
                   $subtotal = 0;
                   $tax = 0;
                  //return $prods;
                   foreach ($prods as $pro){
                       $product_stock = ProductStock::find($pro->stock_id);
                       $product = $product_stock->product;
                      // return $product;
                     
                       $product_variation = $product_stock->variant;
                       $subtotal += $product->unit_price*$pro->cartquantity;
                       $tax += $pro->tax*$pro->quantity;
                       if($pro->quantity >$product_stock->qty){
                           $order->delete();
                           return response()->json(['result' => false, 'message' => 
                           $product->name.' ('.$product_variation.') '.translate(" just stock outs.")], 200);
                              }
                       else {
                           $product_stock->qty -= $pro->cartquantity;
                           $product_stock->update();
                       }
   
                       //echo $product->price*$pro->quantity;

                       $order_detail = new OrderDetail;
                       $order_detail->order_id  =$order->id;
                       $order_detail->seller_id = $product->user_id;
                       $order_detail->product_id = $product->id;
                       $order_detail->payment_status = $payment_type != 'cash_on_delivery' ? 'unpaid' : 'paid';
                       $order_detail->variation = $product_variation;
                       $order_detail->price = $product->unit_price*$pro->cartquantity;
                       $order_detail->tax = $pro->tax*$pro->quantity;
                       $order_detail->quantity = $pro->cartquantity;
                       $order_detail->delivery_status ="delivered";
                       $order_detail->delivery_status =$payment_type;

                       //stock table update stock quantity

                       $order_detail->shipping_type = null;
                       $order_detail->shipping_cost = 0;
                       $order_detail->save();
                       $product->num_of_sale++;
                       $product->save();

                   }
                   $order->grand_total = $subtotal + $tax+0;
                   $type=$payment_type;
                   if($type=="cash_on_delivery")
                   {
                     $amountpaid=$order->grand_total;
                     $order->payment_status = 'paid';
                   }
                   else if($type=="credit")
                   {
                     $order->payment_status = 'unpaid';
                   }
                   else{
                         $order->payment_status = 'unpaid';
                   }
                   
                   //save this payment
                   if($amountpaid>0)
                   {
                $dt= date('Y-m-d',time());
                   $payment=new Payment();
                   $payment->seller_id=$user_id;
                   $payment->amount=$amountpaid;
                   $payment->user_id=$cid;
                   $payment->order_id=$order->id;
                   $payment->short_date=$dt;
                   $payment->save();
                   }
                   foreach ($prods as $pro){
                       try{
                           $customerid=$cid;
                       
                       /*if($pro->reward_mechanism)
                       {*/
                       $cmer=User::findOrFail($customerid);
                        if($type=="cash_on_delivery")
                   {
                         //$points=$this->getPoint($pro->id,$pro->cartquantity);
                   
                       if($pro->reward_mechanism!="")
                       {
                           $points=0;
                           
                           //dozens only
                           if($pro->id==28 || $pro->id==35 || $pro->id==37 || $pro->id==33 || $pro->id==27 || $pro->id==34 || $pro->id==31 || $pro->id==36 || $pro->id==32 || $pro->id==26)
                           {
                                $points=$pro->cartquantity*$pro->reward_mechanism;
                                //dozens
                           }
                           else{
                                $points=$pro->cartquantity/$pro->reward_mechanism;
                          
                          }
                           if($points>0)
                           {
                      
                    if($cmer->points=="")
                    {
                      $cmer->points=$points; 
                        
                    }
                    else{
                        $cmer->points+=$points; 
                    }
                    $cmer->update();
                    $l=new Pointlog();
                    $l->customer_id=$customerid;
                    $l->order_id=$order->id;
                    $l->points=$points;
                    $l->save();
                    
                       }
                       }
                   
                       }
                   
                 
                       }
                       catch(Exception $er)
                       {
                        $log=new Log();
                        $log->message="error";//$er->getMessage();
                        $log->save();
                       }
                       }

                   $order->amount_paid = $amountpaid;
                   $order->seller_id = $product->user_id;
                   $order->update();
                   if($user_id != NULL && $order->payment_status == 'paid') {
                      // calculateCommissionAffilationClubPoint($order);
                   }
                   DB::table('carts')->where('user_id', '=', $user_id)->delete();
                   DB::commit();
                     return response()->json(['result' => true, 
                     'message' => translate('Order Completed Successfully.')], 200);
               }
               else {
                return response()->json(['result' => false, 
                'message' => translate('Please enter customer information')], 200);
               }
               
           }
               
           }
           
           
           else{
            return response()->json(['result' => false, 
            'message' => translate('No items in the cart')], 200);
           }

            
            }
         catch (\Exception $e) {
            DB::rollBack();
return response()->json(['result' => false, 
            'message' => $e->getMessage()], 200);
        
         }
    }




public function getPoint($id,$quantity)
{
  
   $points=0;
   if($id==10)
   {
       //shampoo 1 liter
       $points=$quantity*20;
   }
   else if($id==32)
   {
       //shampoo 5ltrs liter
       $points=$quantity*5;
   }
   
   else if($id==3)
   {
       //shampoo 20ltrs liter
       $points=$quantity*20;
   }
   //Relaxer
   
   
   else if($id==5)
   {
       //2kgs
       $points=$quantity/2;
   }
   
    else if($id==1)
   {
       //1kgs
       $points=$quantity/4;
   }
   
    else if($id==1)
   {
       //500g
       $points=$quantity/5;
   }
   
   
   //Hair Food
   
    else if($id==1)
   {
       //500g
       $points=$quantity/5;
   }
 
   return $points;
}









    public function checkout(Request $request,$user_id,$name,$phone,$address,$email,$latitude,$longitude,$payment_type,$amountpaid,$routeid)
    {
        try{

            $json = json_decode($request->getContent());
            //$user_id=$json->user_id;
          
            /*
            if(Session::get('pos.shipping_info') == null
            || Session::get('pos.shipping_info')['name'] == null ||
             Session::get('pos.shipping_info')['phone'] == null || Session::get('pos.shipping_info')['address'] == null){
               return array('success' => 0, 'message' => translate("Please Add Shipping Information."));
           }
   */
  $prods = Cart::join('products','carts.product_id', '=',
  'products.id')->where('products.added_by', 'admin')
  ->where('carts.user_id',$user_id)
//get the logos here
  ->join('uploads','products.thumbnail_img', '=',
  'uploads.id')
  //brands
  ->join('brands','products.brand_id', '=',
  'brands.id')
  //categories
  ->join('categories','products.category_id', '=',
  'categories.id')

  ->join('product_stocks','product_stocks.product_id', '=',
  'products.id')

  ->select('products.*','product_stocks.id as stock_id','product_stocks.variant',
  'product_stocks.price as stock_price', 'product_stocks.qty as stock_qty',
   'product_stocks.image as stock_image','uploads.*','brands.name as brand',
   'categories.name as category','products.id as pid','product_stocks.qty','product_stocks.id as stock_id',
   'carts.id as cart_id','carts.quantity as cartquantity','carts.tax')
   ->orderBy('products.created_at', 'desc')->get();

           if(sizeof($prods)>0){
               
               $credit_limit=get_setting('credit_limit');
               $creditamount=0;
                foreach ($prods as $tds){
                    
                    
                    
                    
                  
                    
                    
                    
                    
                    
                    
                       $product_stock = ProductStock::find($tds->stock_id);
                       $product = $product_stock->product;
                     
                       $product_variation = $product_stock->variant;
                       $creditamount += $product->unit_price*$tds->cartquantity;
                }
                
                //lets get the balance
                $bb=$creditamount-$amountpaid;
                if($payment_type!="cash_on_delivery" && $credit_limit<$bb) 
                {
                   return response()->json(['result' => false, 
                'message' => translate('Credit Limit Exceeded shs '.$credit_limit)], 200); 
                }
                else{
               
               $order = new Order;
              
                   $order->user_id = $user_id;
             
               if($email=="0")
               {
                   $email="";
               }
               
               if($phone=="0")
               {
                   $phone="";
               }
               
               $data['name']           = $name;
               $data['address']        = $address;
               $data['phone']          = $phone;
               $data['email']          = $email;
               $data['city']          = "";
               $data['country']          = "uganda";
               $data['postal_code']          = "";
               $order->shipping_address = json_encode($data);
               $customerid=0;
               
               $usercheck=User::where("phone",$phone)
               ->where("user_type","!=","staff")
              ->where("user_type","!=","admin")
               ->get();
               
               
               
               $cid=0;
               if(sizeof($usercheck)==0)
               {
               $user = User::create([
                   'name' => $data['name'],
                    'phone' => $data['phone'],
                   'email' => $data['email'],
                     'address' => $data['address'],
                   'type' => "auto",
                     'customerroute_id'=>$routeid,
                   'password' => Hash::make("6Ymljoput5"),
               ]);
               $user->save();
               $customer = new Customer;
               $customer->user_id = $user->id;
               $customer->save();
               $customerid=$user->id;
               $cid=$user->id;
                }
                else{
                     $apt=User::where("phone",$phone)
               ->where("user_type","!=","staff")
               ->first();
               $apt->customerroute_id=$routeid;
                $apt->address=$address;
               $apt->update();
                    //
                    /*
                    $customerdetails=Customer::where("user_id",$usercheck[0]->id)->get();
                    
                    if(sizeof($customerdetails)>0)
                    {
                     $customerid=$customerdetails[0]->user_id;
                    }*/
                 $cid=$usercheck[0]->id;
                }
                
                //check customer total orders not paid
                $ap=Order::where("user_id",$cid)->sum('amount_paid');
                $gt=Order::where("user_id",$cid)->sum('grand_total');
                
                $balances=$gt-$ap;
                $debt=$creditamount+($balances)-$amountpaid;
                 
                
                if($debt>$credit_limit && $payment_type!="cash_on_delivery")
                {
                  return response()->json(['result' => false, 
                'message' => ('Customer has  Exceeded credit limit shs '.number_format($credit_limit).", Current Debt shs ".number_format($balances)." Invoice Amount shs ".number_format($creditamount)." Outstanding Debt shs ".number_format($debt))], 200);
                }
              
                //set location here
               $order->latitude=$latitude;
               $order->route_id=$routeid;
                
               $order->longitude=$longitude;
               $order->payment_type = $payment_type;
               $order->delivery_viewed = '0';
               $order->sold_by=$user_id;
               $order->seller_id="0";
               $order->user_id=$cid;
                $today= date('Y-m-d',time());
               $order->shortdate=$today;
               $order->payment_status_viewed = '0';
               $order->code = date('Ymd-His').rand(10,99);
               $order->date = strtotime('now');
              
               $order->payment_details = $payment_type;
               //field orders are delivered
               $order->delivery_status = "delivered";
                 DB::beginTransaction();         
               if($order->save()){
                   $subtotal = 0;
                   $tax = 0;
                  //return $prods;
                   foreach ($prods as $pro){
            
                       
                       $product_stock = ProductStock::find($pro->stock_id);
                       $product = $product_stock->product;
                      // return $product;
                     
                       $product_variation = $product_stock->variant;
                       $subtotal += $product->unit_price*$pro->cartquantity;
                       $tax += $pro->tax*$pro->quantity;
                       if($pro->quantity >$product_stock->qty){
                           $order->delete();
                           return response()->json(['result' => false, 'message' => 
                           $product->name.' ('.$product_variation.') '.translate(" just stock outs.")], 200);
                              }
                       else {
                           $product_stock->qty -= $pro->cartquantity;
                           $product_stock->update();
                       }
   
                       //echo $product->price*$pro->quantity;

                       $order_detail = new OrderDetail;
                       $order_detail->order_id  =$order->id;
                       $order_detail->seller_id = $product->user_id;
                       $order_detail->product_id = $product->id;
                       $order_detail->payment_status = $payment_type != 'cash_on_delivery' ? 'unpaid' : 'paid';
                       $order_detail->variation = $product_variation;
                       $order_detail->price = $product->unit_price*$pro->cartquantity;
                       $order_detail->tax = $pro->tax*$pro->quantity;
                       $order_detail->quantity = $pro->cartquantity;
                       $order_detail->delivery_status ="delivered";
                       $order_detail->delivery_status =$payment_type;

                       //stock table update stock quantity

                       $order_detail->shipping_type = null;
                       $order_detail->shipping_cost = 0;
                       $order_detail->save();
                       $product->num_of_sale++;
                       $product->save();

                   }
                   $order->grand_total = $subtotal + $tax+0;
                   $type=$payment_type;
                   if($type=="cash_on_delivery")
                   {
                     $amountpaid=$order->grand_total;
                     $order->payment_status = 'paid';
                   }
                   else if($type=="credit")
                   {
                     $order->payment_status = 'unpaid';
                   }
                   else{
                         $order->payment_status = 'unpaid';
                   }
                   
                   //save this payment
                   if($amountpaid>0)
                   {
                             $dt= date('Y-m-d',time());
                   $payment=new Payment();
                   $payment->seller_id=$user_id;
                   $payment->amount=$amountpaid;
                   $payment->user_id=$cid;
                   $payment->order_id=$order->id;
                      $payment->short_date=$dt;
                   $payment->save();
                   }


 
                   $order->amount_paid = $amountpaid;
                   $order->seller_id = $product->user_id;
                   $order->update();
                   if($user_id != NULL && $order->payment_status == 'paid') {
                      // calculateCommissionAffilationClubPoint($order);
                   }
                   DB::table('carts')->where('user_id', '=', $user_id)->delete();
                   DB::commit();
                     return response()->json(['result' => true, 
                     'message' => translate('Order Completed Successfully.')], 200);
               }
               else {
                return response()->json(['result' => false, 
                'message' => translate('Please enter customer information')], 200);
               }
               
           }
               
           }
           
           
           else{
            return response()->json(['result' => false, 
            'message' => translate('No items in the cart')], 200);
           }

            
            }
         catch (\Exception $e) {
            DB::rollBack();
return response()->json(['result' => false, 
            'message' => $e->getMessage()], 200);
        
         }
    }
    
    //checkout2
    public function checkout2(Request $request,$user_id,$name,$phone,$address,$email,$latitude,$longitude,$payment_type,$amountpaid,$routeid,$products,$date)
    {
        try{

            $json = json_decode($request->getContent());
            //$user_id=$json->user_id;
          
            /*
            if(Session::get('pos.shipping_info') == null
            || Session::get('pos.shipping_info')['name'] == null ||
             Session::get('pos.shipping_info')['phone'] == null || Session::get('pos.shipping_info')['address'] == null){
               return array('success' => 0, 'message' => translate("Please Add Shipping Information."));
           }
   */
   
   $decordedprods=base64_decode($products);
   //return $decordedprods;
  $prods =  json_decode($decordedprods);
  //get prods from deserialised
           if(sizeof($prods)>0){
               $credit_limit=get_setting('credit_limit');
               $creditamount=0;
               
                foreach ($prods as $tds){
                        $product_stock = ProductStock::where("product_id",$tds->product_id)->first();
                       $product = $product_stock->product;
                       $product_variation = $product_stock->variant;
                       $creditamount += $product->unit_price*$tds->quantity;
                }
                
                //lets get the balance
                $bb=$creditamount-$amountpaid;
                if($payment_type!="cash_on_delivery" && $credit_limit<$bb) 
                {
                   return response()->json(['result' => false,
                   'success'=>0,
                'message' => translate('Credit Limit Exceeded shs '.$credit_limit)], 200); 
                }
                else{
               
               $order = new Order;
              
                   $order->user_id = $user_id;
             
               if($email=="0")
               {
                   $email="";
               }
               
               if($phone=="0")
               {
                   $phone="";
               }
               
               $data['name']           = $name;
               $data['address']        = $address;
               $data['phone']          = $phone;
               $data['email']          = $email;
               $data['city']          = "";
               $data['country']          = "uganda";
               $data['postal_code']          = "";
               $order->shipping_address = json_encode($data);
               $customerid=0;
               
               $usercheck=User::where("phone",$phone)
               ->where("user_type","!=","staff")
              ->where("user_type","!=","admin")
               ->get();
               
               
               
               $cid=0;
               if(sizeof($usercheck)==0)
               {
               $user = User::create([
                   'name' => $data['name'],
                    'phone' => $data['phone'],
                   'email' => $data['email'],
                     'address' => $data['address'],
                   'type' => "auto",
                     'customerroute_id'=>$routeid,
                   'password' => Hash::make("6Ymljoput5"),
               ]);
               $user->save();
               $customer = new Customer;
               $customer->user_id = $user->id;
               $customer->save();
               $customerid=$user->id;
               $cid=$user->id;
                }
                else{
                     $apt=User::where("phone",$phone)
               ->where("user_type","!=","staff")
               ->first();
               $apt->customerroute_id=$routeid;
                $apt->address=$address;
               $apt->update();
                    //
                    /*
                    $customerdetails=Customer::where("user_id",$usercheck[0]->id)->get();
                    
                    if(sizeof($customerdetails)>0)
                    {
                     $customerid=$customerdetails[0]->user_id;
                    }*/
                 $cid=$usercheck[0]->id;
                }
                //check customer total orders not paid
                $ap=Order::where("user_id",$cid)->sum('amount_paid');
                $gt=Order::where("user_id",$cid)->sum('grand_total');
                
                $balances=$gt-$ap;
                $debt=$creditamount+($balances)-$amountpaid;
                 
                
                if($debt>$credit_limit && $payment_type!="cash_on_delivery")
                {
                  return response()->json(['result' => false, 
                  'success'=>0,
                'message' => ('Customer has  Exceeded credit limit shs '.number_format($credit_limit).", Current Debt shs ".number_format($balances)." Invoice Amount shs ".number_format($creditamount)." Outstanding Debt shs ".number_format($debt))], 200);
                }
              
                //set location here
               $order->latitude=$latitude;
               $order->route_id=$routeid;
               
               
               $order->longitude=$longitude;
               $order->payment_type = $payment_type;
               $order->delivery_viewed = '0';
               $order->sold_by=$user_id;
               $order->seller_id="0";
               $order->user_id=$cid;
               $order->payment_status_viewed = '0';
               $order->code = date('Ymd-His').rand(10,99);
               $order->date = strtotime('now');
               $order->created_at=strtotime($date);
               $today= date('Y-m-d',time());
               $order->shortdate=$today;
               $order->payment_details = $payment_type;
               //field orders are delivered
               $order->delivery_status = "delivered";
                 DB::beginTransaction();         
               if($order->save()){
                   $subtotal = 0;
                   $tax = 0;
                  //return $prods;
                   foreach ($prods as $pro){
                       //change these by product_id
                       $product_stock = ProductStock::where("product_id",$pro->product_id)->first();
                       $product = $product_stock->product;
                      // return $product;
                       $product_variation = $product_stock->variant;
                       $subtotal += $product->unit_price*$pro->quantity;
                       $tax += $product->tax*$pro->quantity;
                       if($pro->quantity >$product_stock->qty){
                           $order->delete();
                           return response()->json(['result' => false, 
                           'success'=>0,
                           'message' => 
                           $product->name.' ('.$product_variation.') '.translate(" just stock outs.")], 200);
                              }
                       else {
                           $product_stock->qty -= $pro->quantity;
                           $product_stock->update();
                       }
   
                       //echo $product->price*$pro->quantity;

                       $order_detail = new OrderDetail;
                       $order_detail->order_id  =$order->id;
                       $order_detail->seller_id = $product->user_id;
                       $order_detail->product_id = $product->id;
                       $order_detail->payment_status = $payment_type != 'cash_on_delivery' ? 'unpaid' : 'paid';
                       $order_detail->variation = $product_variation;
                       $order_detail->price = $product->unit_price*$pro->quantity;
                       $order_detail->tax = $product->tax*$pro->quantity;
                       $order_detail->quantity = $pro->quantity;
                       $order_detail->delivery_status ="delivered";
                       $order_detail->delivery_status =$payment_type;

                       //stock table update stock quantity

                       $order_detail->shipping_type = null;
                       $order_detail->shipping_cost = 0;
                       $order_detail->save();
                       $product->num_of_sale++;
                       $product->save();

                   }
                   $order->grand_total = $subtotal + $tax+0;
                   $type=$payment_type;
                   if($type=="cash_on_delivery")
                   {
                     $amountpaid=$order->grand_total;
                     $order->payment_status = 'paid';
                   }
                   else if($type=="credit")
                   {
                     $order->payment_status = 'unpaid';
                   }
                   else{
                         $order->payment_status = 'unpaid';
                   }
                   
                   //save this payment
                   if($amountpaid>0)
                   {
                        $dt= date('Y-m-d',time());
                   $payment=new Payment();
                   $payment->seller_id=$user_id;
                   $payment->amount=$amountpaid;
                   $payment->user_id=$cid;
                     $payment->short_date=$dt;
                   $payment->order_id=$order->id;
                   $payment->save();
                   }

                   $order->amount_paid = $amountpaid;
                   $order->seller_id = $product->user_id;
                   $order->update();
                   if($user_id != NULL && $order->payment_status == 'paid') {
                      // calculateCommissionAffilationClubPoint($order);
                   }
                   DB::table('carts')->where('user_id', '=', $user_id)->delete();
                  //DB::commit();
                     return response()->json(['success'=>1,'result' => true, 
                     'message' => translate('Order Completed Successfully.')], 200);
               }
               else {
                return response()->json(['success'=>0,'result' => false, 
                'message' => translate('Please enter customer information')], 200);
               }
               
           }
               
           }
           
           
           else{
            return response()->json(['success'=>0,'result' => false, 
            'message' => translate('No items in the cart')], 200);
           }

            
            }
         catch (\Exception $e) {
            DB::rollBack();
return response()->json(['success'=>0,'result' => false, 
            'message' => $e->getMessage()], 200);
        
         }
    }





    //update order
    public function updateOrder(Request $request,$user_id,$lat,$lng,$amountpaid,$type,$status,$order_id)
    {
        try{
            $json = json_decode($request->getContent());
            $amount=$amountpaid;
                DB::beginTransaction();         
                   $order =Order::findOrFail($order_id);
                  // return $order;
                   if($order->payment_status=="paid")
                   {
                    return response()->json(['result' => false, 
                    'message' => translate('Order fully paid')], 200);
                   }
                   else{
                       
                   if(($order->amount_paid+$amountpaid)>=$order->grand_total)
                   {
                       $status="paid";
                   $order->amount_paid = $order->grand_total;
                   }
                   else{
                        $status="unpaid";
                       $order->amount_paid = $order->amount_paid+$amountpaid;
                   }
                   $order->payment_status = $status;
                   $order->update();
                   $dt= date('Y-m-d',time());
                   $payment=new Payment();
                   $payment->amount=$amount;
                   $payment->user_id=$user_id;
                    $payment->short_date=$dt;
                   $payment->order_id=$order_id;
                   $payment->save();
                  /* DB::table('carts')->where('user_id', '=', $user_id)->delete();*/
                   DB::commit();
                     return response()->json(['result' => false, 
                     'message' => translate('Order Updated Successfully.')], 200);
                }
            }
         catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['result' => true, 
            'message' => translate('error '.$e->getMessage())], 200);

        
         }
    }


    public function deleteCartItem(Request $request,$id)
    {
        //get the raw post
        $json = json_decode($request->getContent());
       // $id=$json->id;
        $cart=Cart::find($id);
        $cart->delete();
        return response()->json(['result' =>true, 'message' => translate('Item Removed')], 200);
    }

    //get get cart    
    public function getCart(Request $request,$user_id)
    {
        //get the raw post
        $json = json_decode($request->getContent());
         
        
       // $user_id=$json->user_id;
        //lets start here
        $prods = Cart::join('products','carts.product_id', '=',
        'products.id')->where('products.added_by', 'admin')
        ->where('carts.user_id',$user_id)
//get the logos here
        ->leftjoin('uploads','products.thumbnail_img', '=',
        'uploads.id')
        //brands
        ->leftjoin('brands','products.brand_id', '=',
        'brands.id')
        //categories
        ->join('categories','products.category_id', '=',
        'categories.id')

        ->join('product_stocks','product_stocks.product_id', '=',
        'products.id')

        ->select('products.*','product_stocks.id as stock_id','product_stocks.variant',
        'product_stocks.price as stock_price', 'product_stocks.qty as stock_qty',
         'product_stocks.image as stock_image','uploads.*','brands.name as brand',
         'categories.name as category','products.id as pid','product_stocks.qty','product_stocks.id as stock_id',
         'carts.id as cart_id','carts.quantity as quantity')
         ->orderBy('products.created_at', 'desc')->get();

       //  return $prods;
         $products=[];
         foreach($prods as $product){

          //$output['brand']=uploaded_asset($product['thumbnail_img']);
          $output['name']=$product['name'];
          $output['logo']=uploaded_asset($product['thumbnail_img']);
          $output['qty']=$product['qty'];
          $output['quantity']=$product['quantity'];
          $output['cart_id']=$product['cart_id'];
          $output['stock_id']=$product['stock_id'];
          $output['brand']=$product['brand'];
          $output['description']=$product['description'];
          $output['category']=$product['category'];
          $output['product_id']=$product['pid'];
          $output['price']=($product['unit_price']);
          $output['cost']=($product['unit_price']);
          $output['brandlogo']=uploaded_asset($product['thumbnail_img']);
          array_push($products,$output);

         }
             $routes=CustomerRoute::orderBy('id','desc')
             ->where("active",1)
             ->get();
         return response()->json(['routes' =>$routes,'result' =>true, 'message' => translate(''), 'products' => $products,'categories'=>[]], 200);

    }
    public function addcart(Request $request,$stock_id,$quantity,$user_id)
    {
        //get the raw post
        $json = json_decode($request->getContent());
       /* $stock_id=$json->stock_id;
        $quantity=$json->quantity;
        $user_id=$json->userid;*/
        //lets start
        $stock = ProductStock::find($stock_id);
        if(!isset($stock))
        {
           return response()->json(['result' => false, 'message' => translate("This product doesn't have enough stock for minimum purchase quantity")], 200); 
        }
        $product = $stock->product;

        $cart=new Cart();
        //$cart->stock_id = $stock_id;
        $cart->user_id = $user_id;
        $cart->owner_id = $product->user_id;
        $cart->product_id = $product->id;
        $cart->variation = $stock->variant;
        $cart->quantity = $quantity;
        if($stock->qty < $product->min_qty){
            return response()->json(['result' => false, 'message' => translate("This product doesn't have enough stock for minimum purchase quantity")], 200);
  
             }
        $tax = 0;
        $price = $stock->price;

        // discount calculation
        $discount_applicable = false;
        if ($product->discount_start_date == null) {
            $discount_applicable = true;
        }
        elseif (strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
            strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date) {
            $discount_applicable = true;
        }
        if ($discount_applicable) {
            if($product->discount_type == 'percent'){
                $price -= ($price*$product->discount)/100;
            }
            elseif($product->discount_type == 'amount'){
                $price -= $product->discount;
            }
        }

        //tax calculation
        foreach ($product->taxes as $product_tax) {
            if($product_tax->tax_type == 'percent'){
                $tax += ($price * $product_tax->tax) / 100;
            }
            elseif($product_tax->tax_type == 'amount'){
                $tax += $product_tax->tax;
            }
        }

        $cart->price=$price;
        $cart->tax=$tax;
    

        //check if product exists
        $cartexists=Cart::where("product_id",$product->id)
        ->where("user_id",$cart->user_id)->get();
        if(sizeof($cartexists)>0)
        {
            $ppd = DB::table('carts')
            ->where('product_id',"=", $product->id)
            ->where('user_id',"=", $user_id)
            ->update(['quantity' => $quantity]);
             $msg="product updated";
        }
        else{
            $cart->save();
            $msg="product added";
           
        }
        
        $cartprods = Cart::join('products','carts.product_id', '=',
        'products.id')->where('products.added_by', 'admin')
        ->where('carts.user_id',$user_id)
//get the logos here
        ->join('uploads','products.thumbnail_img', '=',
        'uploads.id')
        //brands
        ->join('brands','products.brand_id', '=',
        'brands.id')
        //categories
        ->join('categories','products.category_id', '=',
        'categories.id')

        ->join('product_stocks','product_stocks.product_id', '=',
        'products.id')

        ->select('products.*','product_stocks.id as stock_id','product_stocks.variant',
        'product_stocks.price as stock_price', 'product_stocks.qty as stock_qty',
         'product_stocks.image as stock_image','uploads.*','brands.name as brand',
         'categories.name as category','products.id as pid','product_stocks.qty','product_stocks.id as stock_id',
         'carts.id as cart_id','carts.quantity as quantity')
         ->orderBy('products.created_at', 'desc')->get();

       //  return $prods;
         $carts=[];
         foreach($cartprods as $productcart){

          //$output['brand']=uploaded_asset($product['thumbnail_img']);
          $output['name']=$productcart['name'];
          $output['logo']=uploaded_asset($productcart['thumbnail_img']);
          $output['qty']=$productcart['qty'];
          $output['quantity']=$productcart['quantity'];
          $output['cart_id']=$productcart['cart_id'];
          $output['stock_id']=$productcart['stock_id'];
          $output['brand']=$productcart['brand'];
          $output['description']=$productcart['description'];
          $output['category']=$productcart['category'];
          $output['product_id']=$productcart['pid'];
          $output['price']=($productcart['unit_price']);
          $output['cost']=($productcart['unit_price']);
          $output['brandlogo']=uploaded_asset($productcart['thumbnail_img']);
           array_push($carts,$output);

         }
         
    $routes=CustomerRoute::orderBy('id','desc')
      ->where("active",1)
    ->get();
 return response()->json(['routes'=>$routes,'products'=>$carts,'result' => true, 'message' => translate($msg)], 200);


    }
    
    public function dashboard(Request $request,$id)
    {
           $routes = CustomerRoute::orderBy('id', 'asc')
             ->where("active",1)
           ->get();
     
        $user_id=$id;
        //get the raw post
        $json = json_decode($request->getContent());
        //$id=$json->user_id;
        $prods = ProductStock::join('products','product_stocks.product_id', '=',
         'products.id')->where('products.added_by', 'admin')
//get the logos here
->leftjoin('uploads','products.thumbnail_img', '=',
         'uploads.id')
         //brands
         ->leftjoin('brands','products.brand_id', '=',
         'brands.id')

         //categories
         ->leftjoin('categories','products.category_id', '=',
         'categories.id')

         ->select('products.*','product_stocks.id as stock_id','product_stocks.variant',
         'product_stocks.price as stock_price', 'product_stocks.qty as stock_qty',
          'product_stocks.image as stock_image','uploads.*','brands.name as brand',
          'categories.name as category','products.id as pid','product_stocks.qty','product_stocks.id as stock_id2')->orderBy('products.created_at', 'desc')
          ->where('qty','>',0)
          ->orderBy('name','asc')
          ->get();

        //  return $prods;
          $products=[];
          foreach($prods as $product){

           //$output['brand']=uploaded_asset($product['thumbnail_img']);
           $output['name']=$product['name'];
           $output['logo']=uploaded_asset($product['thumbnail_img']);
           $output['qty']=$product['qty'];
           $output['stock_id']=$product['stock_id2'];
           $output['brand']=$product['brand'];
           $output['description']=$product['description'];
           $output['category']=$product['category'];
           $output['product_id']=$product['pid'];
           $output['price']=($product['unit_price']);
            $output['brandlogo']=uploaded_asset($product['thumbnail_img']);
            array_push($products,$output);

          }
          
          $today=date("Y-m-d",time());
          
          $data['sales']=number_format(sizeof(Order::where("sold_by",$user_id)
          ->where("created_at",'like','%'.$today.'%')
          ->get()));
          $data['amount']=number_format((Order::where("sold_by",$user_id)
          ->where("created_at",'like','%'.$today.'%')
          ->sum('grand_total')))." Shs";
          $data['amount_paid']=number_format((Order::where("sold_by",$user_id)
          ->where("created_at",'like','%'.$today.'%')
          ->sum('amount_paid')))." Shs";
            $data['unpaid']=number_format(sizeof(Order::where("sold_by",$user_id)
            ->where("created_at",'like','%'.$today.'%')
            ->where("payment_status","unpaid")
            ->get()));
            
            $orders=Order::where("sold_by",$user_id)
          ->where("created_at",'like','%'.$today.'%')
          ->get();
          $dbt=0;
          foreach($orders as $or)
          {
              if($or->status="unpaid"){
                  $dbt += $or->grand_total-$or->amount_paid;
              }
          }
            
           $data['debt']=number_format($dbt)." Shs"; 
             //$data['routes']=$routes;
          
         


      return response()->json(['routes'=>$routes,'stats' => $data,'result' => true, 'message' => translate(''), 'products' => $products,'categories'=>[]], 
      200);
    }



    

    protected function loginSuccess($user)
    {
        $token = $user->createToken('API Token')->plainTextToken;
        return response()->json([
            'result' => true,
            'message' => translate('Successfully logged in'),
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => null,
            'user' => [
                'id' => $user->id,
                'type' => $user->user_type,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'avatar_original' => api_asset($user->avatar_original),
                'phone' => $user->phone
            ],
            'categories' => Category::orderby("name","asc")->get(),
            'types' => Types::orderby("name","asc")->get(),
            'brands' => Brand::orderby("name","asc")->get(),
        ]);
    }
    
    
    public function getcustomerOrders(Request $request,$customer)
    {
        $json = json_decode($request->getContent());
      
        $ors = Order::
         join('users as customer','customer.id', '=',
         'orders.user_id')
          ->join('users','users.id', '=',
         'orders.sold_by')
         ->select('orders.*','customer.*','orders.id as order_id')
          ->where('orders.user_id',$customer)
          ->orderBy('orders.id', 'desc');
          $ors=$ors->get();
          $orders=[];
          foreach($ors as $order){
          $order_id=$order['order_id'];
            //return $order_id;
           $output['order_id']=$order_id;
           $output['date']= date("Y-m-d", strtotime($order->created_at));
           $output['delivery_status']=$order->delivery_status;
           $output['grand_total']=number_format($order->grand_total);
           $output['order']=$order;
           $output['amount_paid']=$order->amount_paid;
           $output['payment_status']=$order->payment_status;
           $output['balanceformat']=number_format($order->grand_total-$order->amount_paid);
           $output['balance']=$order->grand_total-$order->amount_paid;
           //get order details
           $orderdetails=[];
           $details = OrderDetail::with('product')
           ->where("order_details.order_id",$order_id)
           ->get();
           $output['shipping_address']=$order->shipping_address;
           $output['quantity']=OrderDetail::with('product')
           ->sum("order_details.quantity",$order_id);
           $output['order_details']=$details;
           $output['items']=sizeof($details);
           array_push($orders,$output);
          }
      return response()->json(['result' => true, 'message' => translate(''), 'orders' => $orders,'categories'=>[]], 200);
    }
    



    public function getOrders(Request $request,$id,$status,$search,$route_id)
    {

        //get the raw post
        $json = json_decode($request->getContent());
       // $id=$json->user_id;
        //$status=$json->status;

        $ors = Order::
         join('users as customer','customer.id', '=',
         'orders.user_id')
          ->join('users','users.id', '=',
         'orders.sold_by')
         ->select('orders.*','customer.*','orders.id as order_id','customer.name as customername')
         //->where('orders.sold_by',$id)
              //->orwhere('orders.sold_by','116')
          ->orderBy('orders.id', 'desc');
          
          if($search!="0")
          {
            $ors=$ors->where(function($query) use($search){
                $query->orwhere('users.name',"like","%".$search."%")
                ->orwhere('users.phone',"like","%".$search."%")
           ->orwhere('orders.shipping_address',"like","%".$search."%");
                
            });
          }
          
          
          
          if($status!="0")
          {
            $ors=$ors->where('orders.payment_status',$status);
          }
          
          if($route_id!="0")
          {
            $ors=$ors->where('orders.route_id',$route_id);
          }
          
          
         
          
          
          $ors=$ors->with('route')->get();
          $orders=[];
          foreach($ors as $order){
          $order_id=$order['order_id'];
            //return $order_id;
            
            
            
            $rt=($order->route);
            if(isset($rt))
            {
                  $output['route']=$rt->name;
            }
            else{
                  $output['route']="no route";
            }
            
           $output['order_id']=$order_id;
           $output['date']= date("Y-m-d", strtotime($order->created_at));
           $output['delivery_status']=$order->delivery_status;
           $output['grand_total']=number_format($order->grand_total);
           $output['order']=$order;
            $output['customer']=$order->customername;
             $output['phone']=$order->phone;
           $output['amount_paid']=$order->amount_paid;
           $output['payment_status']=$order->payment_status;
           $output['balanceformat']=number_format($order->grand_total-$order->amount_paid);
           $output['balance']=$order->grand_total-$order->amount_paid;
           //get order details
           $orderdetails=[];
           $details = OrderDetail::with('product')
           ->where("order_details.order_id",$order_id)
           ->get();
           
            $output['shipping_address']=$order->shipping_address;
            $output['quantity']=OrderDetail::with('product')
           ->sum("order_details.quantity",$order_id);
           $output['order_details']=$details;
           $output['items']=sizeof($details);
           /*$output['brand']=$product['brand'];
           $output['description']=$product['description'];
           $output['category']=$product['category'];
           $output['product_id']=$product['pid'];
           $output['price']=($product['unit_price']);
           $output['brandlogo']=uploaded_asset($product['thumbnail_img']);*/
           array_push($orders,$output);
          }


      return response()->json(['result' => true, 'message' => translate(''), 'orders' => $orders,'categories'=>[]], 200);
    }
    
    
    
    public function getcustomers(Request $request,$route,$search)
    {
      $customers=User::orderBy('name', 'desc')
        ->where("user_type","customer");
        
        if ($search!="0") {
            $customers = $customers->where('name', 'like', '%' . $search . '%')->orwhere('phone', 'like', '%' . $search . '%');
        }
       // return $customers;
        $routes = CustomerRoute::orderBy('id', 'asc')
          ->where("active",1)
        ->get();
        if ($route!= "0") {
         $customers = $customers->where('customerroute_id', '=',trim($route));
        }
        $final=[];
         $customers=$customers->with('route')->get();
       
        
        foreach($customers as $customer)
        {
         $orders = Order::orderBy('id', 'desc')
        ->where('payment_status',"unpaid")
        ->where('user_id',$customer->id);
        
        //if ($request->route_id != null) {
            $orders = $orders->where('route_id', $request->route_id);
        //}
        
            $rt=($customer->route);
            $orders=$orders->get();
            if(isset($customer->route))
            {
             $output['route']=$customer->route->name;
            }
            else{
                 $output['route']="None";
            }
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
        
        return $final;
    }
    //register
    public function register(Request $request)
    {
        //get the raw post

        $json = json_decode($request->getContent());
        $email=$json->email;
        $name=$json->name;
        $mobile=$json->mobile;
        $password=$json->password;

        $output=[];
        if(User::where('email', $json->email)->first() == null){
          $user = new User;
          $user->name = $name;
          $user->email = $email;
          $user->phone = $mobile;
          $user->user_type = "customer";
          $user->password = Hash::make($password);
          if($user->save()){
             
//start login
$response=User::where("email",$email)->get();    
$user=$response[0];
         return $this->loginSuccess($user);
              
          }
          else{
            return response()->json(['message' => translate('An error has occured'),
            'result' => 0], 200);
          }
      }
      else{
        return response()->json(['message' => translate('User Already Exists'),
        'result' => 0], 200);

      }

     
    }

    public function login(Request $request/*,$email,$password*/)
    {
        //get the raw post

        $json = json_decode($request->getContent());
        $email=$json->username;
        $password=$json->password;

        $response=User::where("email",$email)->get();
        if(sizeof($response)==0)
        {
            return response()->json(['message' => translate('No records found'),
             'user' => null], 401);
            
        }
        else{
            $user=$response[0];
        if ($user != null) {
            if (Hash::check($password, $user->password)) {
                if ($user->user_type= "staff" || $user->user_type== "admin") {
                    
                     return $this->loginSuccess($user);
                    
                 
                }
                else{
                  return response()->json(['message' => translate('Please Only staff allowed to use this app'), 'user' => null], 401);
                }
            } else {
                return response()->json(['result' => false, 'message' => translate('Unauthorized'), 'user' => null], 401);
            }
        } else {
            return response()->json(['result' => false, 'message' => translate('User not found'), 'user' => null], 401);
        }
    }
    }



}

                    