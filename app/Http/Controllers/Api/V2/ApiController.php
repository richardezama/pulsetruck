<?php

/** @noinspection PhpUndefinedClassInspection */

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\OTPVerificationController;
use App\Models\BusinessSetting;
use App\Models\Customer;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Order;
use App\Notifications\AppEmailVerificationNotification;
use Hash;
use App\Models\Cart;
use App\Models\Payment;
use App\Models\OrderDetail;
use App\Models\ProductStock;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{

    //checkout 


    public function checkout(Request $request)
    {
        try{

            $json = json_decode($request->getContent());
            $user_id=$json->user_id;
            DB::beginTransaction();         

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
               $order = new Order;
               /*
               $shipping_info = Session::get('pos.shipping_info');
               if ($request->user_id == null) {
                   $order->guest_id    = mt_rand(100000, 999999);
               }
               else {*/
                   $order->user_id = $request->user_id;
               //}
               $data['name']           = $json->name;
               $data['address']        = $json->address;
               $data['phone']          = $json->phone;
               $data['email']          = $json->email;
               $data['city']          = "";
               $data['country']          = "uganda";
               $data['postal_code']          = "";

               


               
               $order->shipping_address = json_encode($data);

               $customerid=0;
               $usercheck=User::where("phone",$json->phone)->get();
               if(sizeof($usercheck)==0)
               {
               $user = User::create([
                   'name' => $data['name'],
                   'email' => $data['email'],
                   'type' => "auto",
                   'password' => Hash::make("6Ymljoput5"),
               ]);
               $user->save();
               $customer = new Customer;
               $customer->user_id = $user->id;
               $customer->save();
               $customerid=$user->id;
                }
                else{

                }
                //set location here
               $order->latitude=$json->latitude;
               $order->longitude=$json->longitude;
               $order->payment_type = $json->payment_type;
               $order->delivery_viewed = '0';
               $order->sold_by=$user_id;
               $order->seller_id="0";
               $order->user_id=$customerid;
               $order->payment_status_viewed = '0';
               $order->code = date('Ymd-His').rand(10,99);
               $order->date = strtotime('now');
               $order->payment_status = $json->payment_type != 'cash_on_delivery' ? 'unpaid' : 'paid';
               $order->payment_details = $json->payment_type;
               //field orders are delivered
               $order->delivery_status = "delivered";
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
                           $product_stock->save();
                       }
   
                       //echo $product->price*$pro->quantity;

                       $order_detail = new OrderDetail;
                       $order_detail->order_id  =$order->id;
                       $order_detail->seller_id = $product->user_id;
                       $order_detail->product_id = $product->id;
                       $order_detail->payment_status = $json->payment_type != 'cash_on_delivery' ? 'unpaid' : 'paid';
                       $order_detail->variation = $product_variation;
                       $order_detail->price = $product->unit_price*$pro->cartquantity;
                       $order_detail->tax = $pro->tax*$pro->quantity;
                       $order_detail->quantity = $pro->cartquantity;
                       $order_detail->delivery_status ="delivered";
                       $order_detail->delivery_status =$json->payment_type;

                       $order_detail->shipping_type = null;
                       $order_detail->shipping_cost = 0;
                       $order_detail->save();
                       $product->num_of_sale++;
                       $product->save();

                   }
                   $order->grand_total = $subtotal + $tax+0;
                   $amountpaid=0;
                   $type=$json->payment_type;
                   if($type=="cash_on_delivery")
                   {
                    $amountpaid=$order->grand_total;
                    
                   }
                   else if($type=="credit")
                   {
                    $amountpaid=0;
                   }
                   else if($type=="partial")
                   {
                    $amountpaid=$json->amountpaid;
                   }

                   //save this payment
                   $payment=new Payment();
                   $payment->seller_id=$order->seller_id;
                   $payment->amount=$amountpaid;
                   $payment->user_id=$user_id;
                   $payment->order_id=$order->id;
                   $payment->save();

                   $order->amount_paid = $amountpaid;
                   $order->seller_id = $product->user_id;
                   $order->update();
                   if($json->user_id != NULL && $order->payment_status == 'paid') {
                      // calculateCommissionAffilationClubPoint($order);
                   }
                   DB::table('carts')->where('user_id', '=', $user_id)->delete();
                   DB::commit();
                     return response()->json(['result' => false, 
                     'message' => translate('Order Completed Successfully.')], 200);
               }
               else {
                return response()->json(['result' => false, 
                'message' => translate('Please enter customer information')], 200);
               }
           }
           else{
            return response()->json(['result' => false, 
            'message' => translate('No items in the cart')], 200);
           }

            
            }
         catch (\Exception $e) {
            return $e;
            DB::rollBack();

        
         }
    }





    //update order
    public function updateOrder(Request $request)
    {
        try{
            $json = json_decode($request->getContent());
            $user_id=$json->user_id;
            $order_id=$json->order_id;
            $status=$json->status;
            $type=$json->payment_type;
            $amountpaid=$json->amountpaid;
            $amount=$json->amountpaid;
                DB::beginTransaction();         
                   $order =Order::findOrFail($order_id);
                   if($order->payment_status)
                   {
                    return response()->json(['result' => false, 
                    'message' => translate('Order fully paid')], 200);

                   }
                   else{
                   /*return response()->json(['result' => false, 
                   'message' => translate('order '.$order)], 200);*/
       
                   $updatestatus="";
                   if($type=="cash_on_delivery")
                   {
                    $amountpaid=$order->grand_total;
                
                   }
                   else if($type=="partial")
                   {
                    $amountpaid=$json->amountpaid+$order->amount_paid;
                   }

                   if($amountpaid>=$order->grand_total)
                   {
                   $status="paid";
                  
                   }
                   $order->payment_status = $status;
                   $order->amount_paid = $amountpaid;
                   $order->update();
                   $payment=new Payment();
                   $payment->amount=$amount;
                   $payment->user_id=$user_id;
                   $payment->order_id=$order_id;
                   $payment->save();
                   DB::table('carts')->where('user_id', '=', $user_id)->delete();
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


    public function deleteCartItem(Request $request)
    {
        //get the raw post
        $json = json_decode($request->getContent());
        $id=$json->id;
        $cart=Cart::find($id);
        $cart->delete();
        return response()->json(['result' =>true, 'message' => translate('Item Removed')], 200);
    }

    //get get cart    
    public function getCart(Request $request)
    {
        //get the raw post
        $json = json_decode($request->getContent());
        $user_id=$json->user_id;
        //lets start here
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
         return response()->json(['result' =>true, 'message' => translate(''), 'products' => $products,'categories'=>[]], 200);

    }
    public function addcart(Request $request)
    {
        //get the raw post
        $json = json_decode($request->getContent());
        $stock_id=$json->stock_id;
        $quantity=$json->quantity;
        $user_id=$json->userid;


        //lets start

        $stock = ProductStock::find($stock_id);
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
        $cartexists=Cart::where("product_id",$product->id)->where("user_id",$cart->user_id)->get();
        if(sizeof($cartexists)>0)
        {
            $ppd = DB::table('carts')
            ->where('product_id',"=", $product->id)
            ->where('user_id',"=", $user_id)
            ->update(['quantity' => $quantity]);

            return response()->json(['result' => true, 'message' => translate("product updated")], 200);
        }
        else{
            $cart->save();
            return response()->json(['result' => true, 'message' => translate("product added")], 200);
        }

    }
    
    public function dashboard(Request $request)
    {
        //get the raw post
        $json = json_decode($request->getContent());
        $id=$json->user_id;
        $prods = ProductStock::join('products','product_stocks.product_id', '=',
         'products.id')->where('products.added_by', 'admin')
//get the logos here
->join('uploads','products.thumbnail_img', '=',
         'uploads.id')
         //brands
         ->join('brands','products.brand_id', '=',
         'brands.id')

         //categories
         ->join('categories','products.category_id', '=',
         'categories.id')

         ->select('products.*','product_stocks.id as stock_id','product_stocks.variant',
         'product_stocks.price as stock_price', 'product_stocks.qty as stock_qty',
          'product_stocks.image as stock_image','uploads.*','brands.name as brand',
          'categories.name as category','products.id as pid','product_stocks.qty','product_stocks.id as stock_id2')->orderBy('products.created_at', 'desc')->get();

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

      return response()->json(['result' => true, 'message' => translate(''), 'products' => $products,'categories'=>[]], 200);
    }



    public function login(Request $request)
    {
        //get the raw post

        $json = json_decode($request->getContent());
        $email=$json->email;
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
                /*if ($user->email_verified_at == null) {
                    return response()->json(['message' => translate('Please verify your account'), 'user' => null], 401);
                }*/
                return $this->loginSuccess($user);
            } else {
                return response()->json(['result' => false, 'message' => translate('Unauthorized'), 'user' => null], 401);
            }
        } else {
            return response()->json(['result' => false, 'message' => translate('User not found'), 'user' => null], 401);
        }
    }
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
            ]
        ]);
    }



    public function getOrders(Request $request)
    {

        //get the raw post
        $json = json_decode($request->getContent());
        $id=$json->user_id;
        $status=$json->status;

        $ors = Order::
         join('users as customer','customer.id', '=',
         'orders.user_id')
          ->join('users','users.id', '=',
         'orders.sold_by')
         ->select('orders.*','customer.*','orders.id as order_id')
         //->where('orders.sold_by',$id)
          ->orderBy('orders.id', 'desc');
          if($status!="")
          {
            $ors=$ors->where('orders.delivery_status',$status);
          }
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
}
