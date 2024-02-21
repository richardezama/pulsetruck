<?php

namespace App\Http\Controllers;

use App\Http\Controllers\AffiliateController;
use App\Http\Controllers\OTPVerificationController;
use Illuminate\Http\Request;
use App\Http\Controllers\ClubPointController;
use App\Models\Order;
use App\Models\Cart;
use App\Models\Address;
use App\Models\CustomerRoute;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\CommissionHistory;
use App\Models\Color;
use App\Models\OrderDetail;
use App\Models\CouponUsage;
use App\Models\Coupon;
use App\OtpConfiguration;
use App\Models\User;
use App\Models\BusinessSetting;
use App\Models\CombinedOrder;
use App\Models\SmsTemplate;
use Auth;
use Session;
use App\Models\Payment;
//use DB;
use Mail;
use App\Mail\InvoiceEmailManager;
use App\Utility\NotificationUtility;
use App\Utility\SmsUtility;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource to seller.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $payment_status = null;
        $delivery_status = null;
        $sort_search = null;
        $orders = DB::table('orders')
            ->orderBy('id', 'desc')
            //->join('order_details', 'orders.id', '=', 'order_details.order_id')
            ->where('seller_id', Auth::user()->id)
            ->select('orders.id')
            ->distinct();

        if ($request->payment_status != null) {
            $orders = $orders->where('payment_status', $request->payment_status);
            $payment_status = $request->payment_status;
            return  $payment_status;
        }
        if ($request->delivery_status != null) {
            $orders = $orders->where('delivery_status', $request->delivery_status);
            $delivery_status = $request->delivery_status;
        }
        if ($request->has('search')) {
            $sort_search = $request->search;
            $orders = $orders->where('code', 'like', '%' . $sort_search . '%');
        }

        $orders = $orders->paginate(50);

        foreach ($orders as $key => $value) {
            $order = \App\Models\Order::find($value->id);
            $order->viewed = 1;
            $order->save();
        }

        return view('frontend.user.seller.orders', compact('orders', 'payment_status', 'delivery_status', 'sort_search'));
    }

    // All Orders
    public function all_orders(Request $request)
    {
       
        $date = $request->date;
        $sort_search = null;
        $delivery_status = null;
        $payment_status=null;

        $orders = Order::orderBy('id', 'desc');
        if ($request->has('search')) {
            $sort_search = $request->search;
            $orders = $orders->where('code', 'like', '%' . $sort_search . '%');
        }
        if ($request->delivery_status != null) {
            $orders = $orders->where('delivery_status', $request->delivery_status);
            $delivery_status = $request->delivery_status;
        }

        if ($request->paid != null) {
            $orders = $orders->where('payment_status', $request->paid);
            $payment_status = $request->paid;
        }

        
        if ($date != null) {
            $orders = $orders->where('created_at', '>=', date('Y-m-d', strtotime(explode(" to ", $date)[0])))->where('created_at', '<=', date('Y-m-d', strtotime(explode(" to ", $date)[1])));
        }
        
        
         
        
        $orders = $orders->paginate(500);
        
         $paid=0;
        $unpaid=0;
        $debt=0;
        foreach($orders as $or)
        {
            if($or->payment_status=="unpaid")
            {
                $unpaid++;
            }
            if($or->payment_status=="paid")
            {
                $paid++;
            }
             if(($or->grand_total-$or->amount_paid)>0)
                {
                     $debt+=$or->grand_total-$or->amount_paid;
                }
        }
        //return  $payment_status;
        return view('backend.sales.all_orders.index', compact('orders', 'sort_search', 'delivery_status', 'date',
        'payment_status','unpaid','paid','debt'));
    }
    
    //sales by route
    public function all_orders_routes(Request $request)
    {
        
        $date = $request->date;
        $sort_search = null;
        $delivery_status = null;
        $payment_status=null;
        $routes = CustomerRoute::orderBy('id', 'asc')->get();
        $orders = Order::orderBy('id', 'desc');
        $route="";
        $booleanAll=true;
        if ($request->has('search')) {
            $sort_search = $request->search;
            $orders = $orders->where('code', 'like', '%' . $sort_search . '%');
        }
        if ($request->delivery_status != null) {
            $orders = $orders->where('delivery_status', $request->delivery_status);
            $delivery_status = $request->delivery_status;
        }
        if ($request->paid != null) {
            $orders = $orders->where('payment_status', $request->paid);
            $payment_status = $request->paid;
        }
        if ($request->route_id != null) {
            $orders = $orders->where('route_id', $request->route_id);
            $route = $request->route_id;
        }
        if ($date != null) {
            //2023-03-13 08:16:47
            $start=explode(" to ", $date)[0]." 00:00:00";
            $end=explode(" to ", $date)[1]." 00:00:00";
               $booleanAll=false;
            // return $start." to ".$end;
             
             if($start==$end)
             {
                 //return $end."na totday oooh";
                   $orders = $orders->where('orders.created_at', '>=', date('Y-m-d', strtotime(explode(" to ", $date)[0])));
             }
             else{
            $orders = $orders->where('orders.created_at', '>=', date('Y-m-d', strtotime(explode(" to ", $date)[0])))
            
            ->where('orders.created_at', '<=', date('Y-m-d', strtotime(explode(" to ", $date)[1])));
             }
        }
        
         $orders=$orders->leftjoin('users as u','u.id','orders.user_id')
        ->select('orders.*','u.name as custname','u.phone');
        if($booleanAll)
        {
            $orders =
        
        $orders->with('road')->paginate(500000000000);
        }
        else{
            $orders =
        
        $orders->with('road')->paginate(500);
        }
        
        $paid=0;
        $unpaid=0;
        $debt=0;
        
        $totalpaid=0;
        foreach($orders as $or)
        {
            if($or->payment_status=="unpaid")
            {
                $unpaid++;
            }
            if($or->payment_status=="paid")
            {
                $paid++;
            }
             if(($or->grand_total-$or->amount_paid)>0)
                {
                     $debt+=$or->grand_total-$or->amount_paid;
                }
                
                $totalpaid+=$or->amount_paid;
        }
        //2023-07-07 00:00:00
        
        //other money collected today
        $dt= date('Y-m-d',time());
          $todaytotal=Payment::where('created_at', '>=',
        ($dt))->sum("amount");
        
        
        

//return $orders;
if($request->export != null)
        {
          $this->exportByRoute($orders);
        }
        else{
           
        return view('backend.sales.all_orders.routes', compact('orders', 'sort_search', 'delivery_status', 'date',
        'payment_status','routes','route','unpaid','paid','debt','totalpaid','todaytotal'));
        }
    }
    
     
    public function exportByRoute($data)
    {
        
     $delimiter = ","; 
    $filename = "orders_by_routes_" . date('Y-m-d') . ".csv"; 
    // Create a file pointer 
    $f = fopen('php://memory', 'w'); 
    // Set column headers 
    $fields = array('Customer','Order No', 'Order Date', 'Route','Total Amount','Total Paid','Balance','Payment Status','Phone'); 
    fputcsv($f, $fields, $delimiter); 
    // Output each row of the data, format line as csv and write to file pointe
    foreach($data as $row){ 
         $lineData = array($row->custname,$row->code, $row->created_at,$row->road->name,$row->grand_total,$row->amount_paid,$row->grand_total-$row->amount_paid,$row->payment_status,$row->phone); 
        fputcsv($f, $lineData, $delimiter); 
    } 
    
    $this->export($f,$filename);
  
    }
    
    //report by sellers
    
    public function all_orders_sellers(Request $request)
    {
        
        $date = $request->date;
        $sort_search = null;
        $delivery_status = null;
        $payment_status=null;
        $routes = CustomerRoute::orderBy('id', 'asc')->get();
         $sellers = User::orderBy('name', 'desc')
         ->where("user_type","staff")
         ->get();
        $orders = Order::orderBy('id', 'desc');
        $route="";
        $seller="";
        if ($request->has('search')) {
            $sort_search = $request->search;
            $orders = $orders->where('code', 'like', '%' . $sort_search . '%');
        }
        if ($request->delivery_status != null) {
            $orders = $orders->where('delivery_status', $request->delivery_status);
            $delivery_status = $request->delivery_status;
        }
        if ($request->paid != null) {
            $orders = $orders->where('payment_status', $request->paid);
            $payment_status = $request->paid;
        }
        if ($request->route_id != null) {
            $orders = $orders->where('route_id', $request->route_id);
            $route = $request->route_id;
        }
        if ($date != null) {
            
           $start=explode(" to ", $date)[0]." 00:00:00";
            $end=explode(" to ", $date)[1]." 00:00:00";
             
            // return $start." to ".$end;
             
             if($start==$end)
             {
                 //return $end."na totday oooh";
                   $orders = $orders->where('orders.created_at', '>=', date('Y-m-d', strtotime(explode(" to ", $date)[0])));
             }
             else{
            $orders = $orders->where('orders.created_at', '>=', date('Y-m-d', strtotime(explode(" to ", $date)[0])))
            
            ->where('orders.created_at', '<=', date('Y-m-d', strtotime(explode(" to ", $date)[1])));
             }
        }
        
         if ($request->seller_id != null) {
            $orders = $orders->where('sold_by', $request->seller_id);
            $seller = $request->seller_id;
        }
        $orders=$orders->leftjoin('users as u','u.id','orders.user_id');
        $orders = $orders->with('salesman')
        ->select('orders.*','u.name as custname','u.phone')
        ->paginate(500);
        $paid=0;
        $unpaid=0;
        $debt=0;
         $totalpaid=0;
        foreach($orders as $or)
        {
            if($or->payment_status=="unpaid")
            {
                $unpaid++;
            }
            if($or->payment_status=="paid")
            {
                $paid++;
            }
             if(($or->grand_total-$or->amount_paid)>0)
                {
                     $debt+=$or->grand_total-$or->amount_paid;
                }
                  $totalpaid+=$or->amount_paid;
        }
        //return  $payment_status;
          $dt= date('Y-m-d',time());
         $todaytotal=Payment::where('created_at', '>=',
        $dt)->sum("amount");
        
        if($request->export != null)
        {
          $this->exportBySellers($orders);
        }
        else{
        return view('backend.sales.all_orders.sellers', compact('orders', 'sort_search', 'delivery_status', 'date',
        'payment_status','routes','route','sellers','seller','paid','unpaid','debt','totalpaid','todaytotal'));
        }
    }
    
    public function exportBySellers($data)
    {
        
     $delimiter = ","; 
    $filename = "orders_by_sellers_" . date('Y-m-d') . ".csv"; 
    // Create a file pointer 
    $f = fopen('php://memory', 'w'); 
    // Set column headers 
    $fields = array('Seller','Customer','Order No', 'Order Date', 'Route','Total Amount','Total Paid','Balance','Payment Status','Phone'); 
    fputcsv($f, $fields, $delimiter); 
    // Output each row of the data, format line as csv and write to file pointe
    foreach($data as $row){ 
         $lineData = array($row->salesman->name,$row->custname,$row->code, $row->created_at,$row->road->name,$row->grand_total,$row->amount_paid,$row->grand_total-$row->amount_paid,$row->payment_status,$row->phone); 
        fputcsv($f, $lineData, $delimiter); 
    } 
    
    $this->export($f,$filename);
  
    }
    

    public function all_orders_show($id)
    {
        $order = Order::findOrFail(decrypt($id));
        $order_shipping_address = json_decode($order->shipping_address);
        $delivery_boys = User::where('city', $order_shipping_address->city)
            ->where('user_type', 'delivery_boy')
            ->get();

        return view('backend.sales.all_orders.show', compact('order', 'delivery_boys'));
    }

    // Inhouse Orders
    public function admin_orders(Request $request)
    {
        

        $date = $request->date;
        $payment_status = null;
        $delivery_status = null;
        $sort_search = null;
        $admin_user_id = User::where('user_type', 'admin')->first()->id;
        $orders = Order::orderBy('id', 'desc')
                        ->where('seller_id', $admin_user_id);

        if ($request->payment_type != null) {
            $orders = $orders->where('payment_status', $request->payment_type);
            $payment_status = $request->payment_type;
        }
        if ($request->delivery_status != null) {
            $orders = $orders->where('delivery_status', $request->delivery_status);
            $delivery_status = $request->delivery_status;
        }
        if ($request->has('search')) {
            $sort_search = $request->search;
            $orders = $orders->where('code', 'like', '%' . $sort_search . '%');
        }
        if ($date != null) {
            $orders = $orders->whereDate('created_at', '>=', date('Y-m-d', strtotime(explode(" to ", $date)[0])))->whereDate('created_at', '<=', date('Y-m-d', strtotime(explode(" to ", $date)[1])));
        }

        $orders = $orders->paginate(15);
        return view('backend.sales.inhouse_orders.index', compact('orders', 'payment_status', 'delivery_status', 'sort_search', 'admin_user_id', 'date'));
    }

    public function show($id)
    {
        $order = Order::findOrFail(decrypt($id));
        $order_shipping_address = json_decode($order->shipping_address);
        $delivery_boys = User::where('city', $order_shipping_address->city)
            ->where('user_type', 'delivery_boy')
            ->get();

        $order->viewed = 1;
        $order->save();
        return view('backend.sales.inhouse_orders.show', compact('order', 'delivery_boys'));
    }

    // Seller Orders
    public function seller_orders(Request $request)
    {
        

        $date = $request->date;
        $seller_id = $request->seller_id;
        $payment_status = null;
        $delivery_status = null;
        $sort_search = null;
        $admin_user_id = User::where('user_type', 'admin')->first()->id;
        $orders = Order::orderBy('code', 'desc')
            ->where('orders.seller_id', '!=', $admin_user_id);

        if ($request->payment_type != null) {
            $orders = $orders->where('payment_status', $request->payment_type);
            $payment_status = $request->payment_type;
        }
        if ($request->delivery_status != null) {
            $orders = $orders->where('delivery_status', $request->delivery_status);
            $delivery_status = $request->delivery_status;
        }
        if ($request->has('search')) {
            $sort_search = $request->search;
            $orders = $orders->where('code', 'like', '%' . $sort_search . '%');
        }
        if ($date != null) {
           $start=explode(" to ", $date)[0]." 00:00:00";
            $end=explode(" to ", $date)[1]." 00:00:00";
             
            // return $start." to ".$end;
             
             if($start==$end)
             {
                 //return $end."na totday oooh";
                   $orders = $orders->where('orders.created_at', '>=', date('Y-m-d', strtotime(explode(" to ", $date)[0])));
             }
             else{
            $orders = $orders->where('orders.created_at', '>=', date('Y-m-d', strtotime(explode(" to ", $date)[0])))
            
            ->where('created_at', '<=', date('Y-m-d', strtotime(explode(" to ", $date)[1])));
             }
        }
        if ($seller_id) {
            $orders = $orders->where('seller_id', $seller_id);
        }

        $orders = $orders->paginate(500);
        return view('backend.sales.seller_orders.index', compact('orders', 'payment_status', 'delivery_status', 'sort_search', 'admin_user_id', 'seller_id', 'date'));
    }

    public function seller_orders_show($id)
    {
        $order = Order::findOrFail(decrypt($id));
        $order->viewed = 1;
        $order->save();
        return view('backend.sales.seller_orders.show', compact('order'));
    }


    // Pickup point orders
    public function pickup_point_order_index(Request $request)
    {
        $date = $request->date;
        $sort_search = null;
        $orders = Order::query();
        if (Auth::user()->user_type == 'staff' && Auth::user()->staff->pick_up_point != null) {
            $orders->where('shipping_type', 'pickup_point')
                    ->where('pickup_point_id', Auth::user()->staff->pick_up_point->id)
                    ->orderBy('code', 'desc');

            if ($request->has('search')) {
                $sort_search = $request->search;
                $orders = $orders->where('code', 'like', '%' . $sort_search . '%');
            }
            if ($date != null) {
                $orders = $orders->whereDate('orders.created_at', '>=', date('Y-m-d', strtotime(explode(" to ", $date)[0])))->whereDate('orders.created_at', '<=', date('Y-m-d', strtotime(explode(" to ", $date)[1])));
            }

            $orders = $orders->paginate(15);

            return view('backend.sales.pickup_point_orders.index', compact('orders', 'sort_search', 'date'));
        } else {
            $orders->where('shipping_type', 'pickup_point')->orderBy('code', 'desc');

            if ($request->has('search')) {
                $sort_search = $request->search;
                $orders = $orders->where('code', 'like', '%' . $sort_search . '%');
            }
            if ($date != null) {
                $orders = $orders->whereDate('orders.created_at', '>=', date('Y-m-d', strtotime(explode(" to ", $date)[0])))->whereDate('orders.created_at', '<=', date('Y-m-d', strtotime(explode(" to ", $date)[1])));
            }

            $orders = $orders->paginate(15);

            return view('backend.sales.pickup_point_orders.index', compact('orders', 'sort_search', 'date'));
        }
    }

    public function pickup_point_order_sales_show($id)
    {
        if (Auth::user()->user_type == 'staff') {
            $order = Order::findOrFail(decrypt($id));
            $order_shipping_address = json_decode($order->shipping_address);
            $delivery_boys = User::where('city', $order_shipping_address->city)
                ->where('user_type', 'delivery_boy')
                ->get();

            return view('backend.sales.pickup_point_orders.show', compact('order', 'delivery_boys'));
        } else {
            $order = Order::findOrFail(decrypt($id));
            $order_shipping_address = json_decode($order->shipping_address);
            $delivery_boys = User::where('city', $order_shipping_address->city)
                ->where('user_type', 'delivery_boy')
                ->get();

            return view('backend.sales.pickup_point_orders.show', compact('order', 'delivery_boys'));
        }
    }

    /**
     * Display a single sale to admin.
     *
     * @return \Illuminate\Http\Response
     */


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
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $carts = Cart::where('user_id', Auth::user()->id)
            ->get();

        if ($carts->isEmpty()) {
            flash(translate('Your cart is empty'))->warning();
            return redirect()->route('home');
        }

        $address = Address::where('id', $carts[0]['address_id'])->first();

        $shippingAddress = [];
        if ($address != null) {
            $shippingAddress['name']        = Auth::user()->name;
            $shippingAddress['email']       = Auth::user()->email;
            $shippingAddress['address']     = $address->address;
            $shippingAddress['country']     = $address->country->name;
            $shippingAddress['state']       = $address->state->name;
            $shippingAddress['city']        = $address->city->name;
            $shippingAddress['postal_code'] = $address->postal_code;
            $shippingAddress['phone']       = $address->phone;
            if ($address->latitude || $address->longitude) {
                $shippingAddress['lat_lang'] = $address->latitude . ',' . $address->longitude;
            }
        }

        $combined_order = new CombinedOrder;
        $combined_order->user_id = Auth::user()->id;
        $combined_order->shipping_address = json_encode($shippingAddress);
        $combined_order->save();

        $seller_products = array();
        foreach ($carts as $cartItem){
            $product_ids = array();
            $product = Product::find($cartItem['product_id']);
            if(isset($seller_products[$product->user_id])){
                $product_ids = $seller_products[$product->user_id];
            }
            array_push($product_ids, $cartItem);
            $seller_products[$product->user_id] = $product_ids;
        }

        foreach ($seller_products as $seller_product) {
            $order = new Order;
            $order->combined_order_id = $combined_order->id;
            $order->user_id = Auth::user()->id;
            $order->shipping_address = $combined_order->shipping_address;
            $order->shipping_type = $carts[0]['shipping_type'];
            if ($carts[0]['shipping_type'] == 'pickup_point') {
                $order->pickup_point_id = $cartItem['pickup_point'];
            }
            $order->payment_type = $request->payment_option;
            $order->delivery_viewed = '0';
            $order->payment_status_viewed = '0';
            $order->code = date('Ymd-His') . rand(10, 99);
            $order->date = strtotime('now');
            $order->save();

            $subtotal = 0;
            $tax = 0;
            $shipping = 0;
            $coupon_discount = 0;

            //Order Details Storing
            foreach ($seller_product as $cartItem) {
                $product = Product::find($cartItem['product_id']);

                $subtotal += $cartItem['price'] * $cartItem['quantity'];
                $tax += $cartItem['tax'] * $cartItem['quantity'];
                $coupon_discount += $cartItem['discount'];

                $product_variation = $cartItem['variation'];

                $product_stock = $product->stocks->where('variant', $product_variation)->first();
                if ($product->digital != 1 && $cartItem['quantity'] > $product_stock->qty) {
                    flash(translate('The requested quantity is not available for ') . $product->getTranslation('name'))->warning();
                    $order->delete();
                    return redirect()->route('cart')->send();
                } elseif ($product->digital != 1) {
                    $product_stock->qty -= $cartItem['quantity'];
                    $product_stock->save();
                }

                $order_detail = new OrderDetail;
                $order_detail->order_id = $order->id;
                $order_detail->seller_id = $product->user_id;
                $order_detail->product_id = $product->id;
                $order_detail->variation = $product_variation;
                $order_detail->price = $cartItem['price'] * $cartItem['quantity'];
                $order_detail->tax = $cartItem['tax'] * $cartItem['quantity'];
                $order_detail->shipping_type = $cartItem['shipping_type'];
                $order_detail->product_referral_code = $cartItem['product_referral_code'];
                $order_detail->shipping_cost = $cartItem['shipping_cost'];

                $shipping += $order_detail->shipping_cost;
                //End of storing shipping cost

                $order_detail->quantity = $cartItem['quantity'];
                $order_detail->save();

                $product->num_of_sale += $cartItem['quantity'];
                $product->save();

                $order->seller_id = $product->user_id;

                if ($product->added_by == 'seller' && $product->user->seller != null){
                    $seller = $product->user->seller;
                    $seller->num_of_sale += $cartItem['quantity'];
                    $seller->save();
                }

                if (addon_is_activated('affiliate_system')) {
                    if ($order_detail->product_referral_code) {
                        $referred_by_user = User::where('referral_code', $order_detail->product_referral_code)->first();

                        $affiliateController = new AffiliateController;
                        $affiliateController->processAffiliateStats($referred_by_user->id, 0, $order_detail->quantity, 0, 0);
                    }
                }
            }

            $order->grand_total = $subtotal + $tax + $shipping;

            if ($seller_product[0]->coupon_code != null) {
                // if (Session::has('club_point')) {
                //     $order->club_point = Session::get('club_point');
                // }
                $order->coupon_discount = $coupon_discount;
                $order->grand_total -= $coupon_discount;

                $coupon_usage = new CouponUsage;
                $coupon_usage->user_id = Auth::user()->id;
                $coupon_usage->coupon_id = Coupon::where('code', $seller_product[0]->coupon_code)->first()->id;
                $coupon_usage->save();
            }

            $combined_order->grand_total += $order->grand_total;

            $order->save();
        }

        $combined_order->save();

        $request->session()->put('combined_order_id', $combined_order->id);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        if ($order != null) {
            foreach ($order->orderDetails as $key => $orderDetail) {
                try {

                    $product_stock = ProductStock::where('product_id', $orderDetail->product_id)->where('variant', $orderDetail->variation)->first();
                    if ($product_stock != null) {
                        $product_stock->qty += $orderDetail->quantity;
                        $product_stock->save();
                    }

                } catch (\Exception $e) {

                }

                $orderDetail->delete();
            }
            $order->delete();
            
            //remove payments attached
            Payment::where("order_id",$id)->delete();
            
            //audit
            flash(translate('Order has been deleted successfully'))->success();
        } else {
            flash(translate('Something went wrong'))->error();
        }
        return back();
    }

    public function bulk_order_delete(Request $request)
    {
        if ($request->id) {
            foreach ($request->id as $order_id) {
                $this->destroy($order_id);
            }
        }

        return 1;
    }

    public function order_details(Request $request)
    {
        $order = Order::findOrFail($request->order_id);
        $order->save();
        return view('frontend.user.seller.order_details_seller', compact('order'));
    }

    public function update_delivery_status(Request $request)
    {
        $order = Order::findOrFail($request->order_id);
        $order->delivery_viewed = '0';
        $order->delivery_status = $request->status;
        $order->save();

        if ($request->status == 'cancelled' && $order->payment_type == 'wallet') {
            $user = User::where('id', $order->user_id)->first();
            $user->balance += $order->grand_total;
            $user->save();
        }

        if (Auth::user()->user_type == 'seller') {
            foreach ($order->orderDetails->where('seller_id', Auth::user()->id) as $key => $orderDetail) {
                $orderDetail->delivery_status = $request->status;
                $orderDetail->save();

                if ($request->status == 'cancelled') {
                    $variant = $orderDetail->variation;
                    if ($orderDetail->variation == null) {
                        $variant = '';
                    }

                    $product_stock = ProductStock::where('product_id', $orderDetail->product_id)
                        ->where('variant', $variant)
                        ->first();

                    if ($product_stock != null) {
                        $product_stock->qty += $orderDetail->quantity;
                        $product_stock->save();
                    }
                }
            }
        } else {
            foreach ($order->orderDetails as $key => $orderDetail) {

                $orderDetail->delivery_status = $request->status;
                $orderDetail->save();

                if ($request->status == 'cancelled') {
                    $variant = $orderDetail->variation;
                    if ($orderDetail->variation == null) {
                        $variant = '';
                    }

                    $product_stock = ProductStock::where('product_id', $orderDetail->product_id)
                        ->where('variant', $variant)
                        ->first();

                    if ($product_stock != null) {
                        $product_stock->qty += $orderDetail->quantity;
                        $product_stock->save();
                    }
                }

                if (addon_is_activated('affiliate_system')) {
                    if (($request->status == 'delivered' || $request->status == 'cancelled') &&
                        $orderDetail->product_referral_code) {

                        $no_of_delivered = 0;
                        $no_of_canceled = 0;

                        if ($request->status == 'delivered') {
                            $no_of_delivered = $orderDetail->quantity;
                        }
                        if ($request->status == 'cancelled') {
                            $no_of_canceled = $orderDetail->quantity;
                        }

                        $referred_by_user = User::where('referral_code', $orderDetail->product_referral_code)->first();

                        $affiliateController = new AffiliateController;
                        $affiliateController->processAffiliateStats($referred_by_user->id, 0, 0, $no_of_delivered, $no_of_canceled);
                    }
                }
            }
        }
        if (addon_is_activated('otp_system') && SmsTemplate::where('identifier', 'delivery_status_change')->first()->status == 1) {
            try {
                SmsUtility::delivery_status_change(json_decode($order->shipping_address)->phone, $order);
            } catch (\Exception $e) {

            }
        }

        //sends Notifications to user
        NotificationUtility::sendNotification($order, $request->status);
        if (get_setting('google_firebase') == 1 && $order->user->device_token != null) {
            $request->device_token = $order->user->device_token;
            $request->title = "Order updated !";
            $status = str_replace("_", "", $order->delivery_status);
            $request->text = " Your order {$order->code} has been {$status}";

            $request->type = "order";
            $request->id = $order->id;
            $request->user_id = $order->user->id;

            NotificationUtility::sendFirebaseNotification($request);
        }


        if (addon_is_activated('delivery_boy')) {
            if (Auth::user()->user_type == 'delivery_boy') {
                $deliveryBoyController = new DeliveryBoyController;
                $deliveryBoyController->store_delivery_history($order);
            }
        }

        return 1;
    }

   public function update_tracking_code(Request $request) {
        $order = Order::findOrFail($request->order_id);
        $order->tracking_code = $request->tracking_code;
        $order->save();

        return 1;
   }

    public function update_payment_status(Request $request)
    {
        $order = Order::findOrFail($request->order_id);
        if($order->payment_status=="paid")
        {
           // return json_encode($order);
           return 2;
        }
        else{
        $order->payment_status_viewed = '0';
        $order->save();

        if (Auth::user()->user_type == 'seller') {
            foreach ($order->orderDetails->where('seller_id', Auth::user()->id) as $key => $orderDetail) {
                $orderDetail->payment_status = $request->status;
                $orderDetail->save();
            }
        } else {
            foreach ($order->orderDetails as $key => $orderDetail) {
                $orderDetail->payment_status = $request->status;
                $orderDetail->save();
            }
        }

        $status = 'paid';
        foreach ($order->orderDetails as $key => $orderDetail) {
            if ($orderDetail->payment_status != 'paid') {
                $status = 'unpaid';
            }
        }
       

        if ($request->status == 'paid') {
 $dt= date('Y-m-d',time());
                 //save  payment balance
          $payment=new Payment();
          $payment->seller_id=$order->seller_id;
          $payment->amount=$order->grand_total-$order->amount_paid;
          $payment->user_id=$order->user_id;
          $payment->order_id=$order->id;
         $payment->short_date=$dt;
          $payment->save();

           //here we must add the payment
           $order->amount_paid = $order->grand_total;



        }


        $order->payment_status = $status;
        $order->save();
        if ($order->payment_status == 'paid') {
            if ($order->commission_calculated == 0) {
                calculateCommissionAffilationClubPoint($order);
            }
            else{
                $order->amount_paid = $order->grand_total;
            }
        }
      


        //sends Notifications to user
        NotificationUtility::sendNotification($order, $request->status);
        if (get_setting('google_firebase') == 1 && $order->user->device_token != null) {
            $request->device_token = $order->user->device_token;
            $request->title = "Order updated !";
            $status = str_replace("_", "", $order->payment_status);
            $request->text = " Your order {$order->code} has been {$status}";

            $request->type = "order";
            $request->id = $order->id;
            $request->user_id = $order->user->id;

            NotificationUtility::sendFirebaseNotification($request);
        }


        if (addon_is_activated('otp_system') && SmsTemplate::where('identifier', 'payment_status_change')->first()->status == 1) {
            try {
                SmsUtility::payment_status_change(json_decode($order->shipping_address)->phone, $order);
            } catch (\Exception $e) {

            }
        }
        return 1;
    }
    }

    public function assign_delivery_boy(Request $request)
    {
        if (addon_is_activated('delivery_boy')) {

            $order = Order::findOrFail($request->order_id);
            $order->assign_delivery_boy = $request->delivery_boy;
            $order->delivery_history_date = date("Y-m-d H:i:s");
            $order->save();

            $delivery_history = \App\Models\DeliveryHistory::where('order_id', $order->id)
                ->where('delivery_status', $order->delivery_status)
                ->first();

            if (empty($delivery_history)) {
                $delivery_history = new \App\Models\DeliveryHistory;

                $delivery_history->order_id = $order->id;
                $delivery_history->delivery_status = $order->delivery_status;
                $delivery_history->payment_type = $order->payment_type;
            }
            $delivery_history->delivery_boy_id = $request->delivery_boy;

            $delivery_history->save();

            if (env('MAIL_USERNAME') != null && get_setting('delivery_boy_mail_notification') == '1') {
                $array['view'] = 'emails.invoice';
                $array['subject'] = translate('You are assigned to delivery an order. Order code') . ' - ' . $order->code;
                $array['from'] = env('MAIL_FROM_ADDRESS');
                $array['order'] = $order;

                try {
                    Mail::to($order->delivery_boy->email)->queue(new InvoiceEmailManager($array));
                } catch (\Exception $e) {

                }
            }

            if (addon_is_activated('otp_system') && SmsTemplate::where('identifier', 'assign_delivery_boy')->first()->status == 1) {
                try {
                    SmsUtility::assign_delivery_boy($order->delivery_boy->phone, $order->code);
                } catch (\Exception $e) {

                }
            }
        }

        return 1;
    }
    
    //customer debt report
     public function customerdebtreport(Request $request)
    {
        
      
        $customers=User::orderBy('name', 'desc')
        ->where("user_type","customer");
        
        if ($request->has('search')) {
            $sort_search = $request->search;
            $customers = $customers->where('name', 'like', '%' . $sort_search . '%')->where('phone', 'like', '%' . $sort_search . '%');
        }
        $customers=$customers->with('route')->get();
       // return $customers;
        
        $routes = CustomerRoute::orderBy('id', 'asc')->get();
        $route="";
        if ($request->route_id != null) {
            $route = $request->route_id;
        }
        $final=[];
        
        
        foreach($customers as $customer)
        {
         $orders = Order::orderBy('id', 'desc')
        ->where('payment_status',"unpaid")
        ->where('user_id',$customer->id);
        
        if ($request->route_id != null) {
            $orders = $orders->where('route_id', $request->route_id);
        }
        
            $orders=$orders->get();
            $output['name']=$customer->name;
              $output['phone']=$customer->phone;
            $rt=($customer->route);
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
             if($debt>0)
             {
            array_push($final,$output);
             }
        }
        //return $final;
        if($request->export != null)
        {
          $this->exportdebt($final);
        }
        else{
        return view('backend.sales.all_orders.debt', compact('final','routes','route'));
        }
    }
    
    
    
   
    public function exportdebt($data)
    {
        
     $delimiter = ","; 
    $filename = "debtdata_" . date('Y-m-d') . ".csv"; 
    // Create a file pointer 
    $f = fopen('php://memory', 'w'); 
    // Set column headers 
    $fields = array('Name', 'Route', 'Amount','Phone'); 
    fputcsv($f, $fields, $delimiter); 
    // Output each row of the data, format line as csv and write to file pointe
    foreach($data as $row){ 
         $lineData = array($row['name'], $row['route'], $row['debt'],$row['phone']); 
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
    
    
    
    //sms here
     public function sms(Request $request)
    {
        
      
        $customers=User::orderBy('name', 'desc')
        ->where("user_type","customer");
        
        if ($request->has('search')) {
            $sort_search = $request->search;
            $customers = $customers->where('name', 'like', '%' . $sort_search . '%')->where('phone', 'like', '%' . $sort_search . '%');
        }
        $customers=$customers->with('route')->get();
       // return $customers;
        
        $routes = CustomerRoute::orderBy('id', 'asc')->get();
        $route="";
        if ($request->route_id != null) {
            $route = $request->route_id;
        }
        $final=[];
        
        
        foreach($customers as $customer)
        {
         $orders = Order::orderBy('id', 'desc')
        ->where('payment_status',"unpaid")
        ->where('user_id',$customer->id);
        
        if ($request->route_id != null) {
            $orders = $orders->where('route_id', $request->route_id);
        }
        
            $orders=$orders->get();
            $output['name']=$customer->name;
              $output['phone']=$customer->phone;
            $rt=($customer->route);
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
             if($debt>0)
             {
            array_push($final,$output);
             }
        }
        #msg="";
        //return $final;
        if($request->send != null)
        {
            $body="Dear Richard Ezama, Kindly Clear your outstanding balance with Zamani 60,000ugx";
         //start sending sms
         $sid = "ACc2ad5527a35cad071450b8aa309d3be5"; // Your Account SID from https://console.twilio.com
          $token = "5414679e861f2f018b9227a0b7dfdbc2"; // Your Auth Token from https://console.twilio.com
$client = new Client($sid, $token);
/*
// Use the Client to make requests to the Twilio REST API
$client->messages->create(
    // The number you'd like to send the message to
    '+256779703391',
    [
        // A Twilio phone number you purchased at https://console.twilio.com
        'from' => '+15077020826',
        // The body of the text message you'd like to send
        'body' => $body
    ]
);*/
$msg= "sms sent";
return view('backend.sales.all_orders.sms', compact('final','routes','route','msg'));
        }
        else{
        return view('backend.sales.all_orders.sms', compact('final','routes','route','msg'));
        }
    }
    
    
    
    
    
    
    //sales by route
    public function product_sales(Request $request)
    {
       // 
        $date = $request->date;
        $sort_search = null;
        $delivery_status = null;
        $payment_status=null;
        $routes = CustomerRoute::orderBy('id', 'asc')->get();
        $orders = new Order();
        $route="";
        $product_id="";
        $booleanAll=true;
        if ($request->has('search')) {
            $sort_search = $request->search;
            $orders = $orders->where('code', 'like', '%' . $sort_search . '%');
        }
        if ($request->delivery_status != null) {
            $orders = $orders->where('delivery_status', $request->delivery_status);
            $delivery_status = $request->delivery_status;
        }
        if ($request->paid != null) {
            $orders = $orders->where('orders.payment_status', $request->paid);
            $payment_status = $request->paid;
        }
        if ($request->route_id != null) {
            $orders = $orders->where('route_id', $request->route_id);
            $route = $request->route_id;
        }
        if ($date != null) {
            //2023-03-13 08:16:47
            $start=explode(" to ", $date)[0]." 00:00:00";
            $end=explode(" to ", $date)[1]." 00:00:00";
               $booleanAll=false;
            // return $start." to ".$end;
             
             if($start==$end)
             {
                 //return $end."na totday oooh";
                   $orders = $orders->where('orders.created_at', '>=', date('Y-m-d', strtotime(explode(" to ", $date)[0])));
             }
             else{
            $orders = $orders->where('orders.created_at', '>=', date('Y-m-d', strtotime(explode(" to ", $date)[0])))
            
            ->where('orders.created_at', '<=', date('Y-m-d', strtotime(explode(" to ", $date)[1])));
             }
        }
        
        $orders=$orders->leftjoin("order_details as o",
        "orders.id","o.order_id");
        $orders=$orders->leftjoin("products as p","p.id","o.product_id");
        $orders=$orders->leftjoin('users as u','u.id','orders.user_id');
        $orders=$orders->leftjoin('customer_routes as ru','ru.id','orders.route_id');
        
        
        if ($request->product_id != null) {
            $product_id=$request->product_id;
            $orders = $orders->where('o.product_id', $request->product_id);
        }
        
        $orders =
        $orders
        ->select("p.name as product_name", "ru.name as route",
        
          DB::raw('sum(orders.grand_total-orders.amount_paid) as balance'),
        
        DB::raw('sum(o.quantity) as total'))
       // ->with('road')
        ->groupBy("p.name","orders.route_id")
        ->orderBy(DB::raw('sum(o.quantity)'),"desc")
        ->paginate(500000000);
        $paid=0;
        $unpaid=0;
        $debt=0;
        $totalpaid=0;
        $dt= date('Y-m-d',time());
        $products=Product::orderby("name","asc")->get();
         if($request->export != null)
        {
          $this->exportProductSales($orders);
        }
        else{
        return view('backend.sales.all_orders.product_sales', compact('orders', 'sort_search', 'delivery_status', 'date',
        'payment_status','routes','route','unpaid','paid','debt','totalpaid','products','product_id'));
        }
    }
    
    
    
    //by brand
    public function brand2_sales(Request $request)
    {
       // 
        $date = $request->date;
        $sort_search = null;
        $delivery_status = null;
        $payment_status=null;
        $routes = CustomerRoute::orderBy('id', 'asc')->get();
        $orders = new Order();
        $route="";
        $product_id="";
        $booleanAll=true;
        if ($request->has('search')) {
            $sort_search = $request->search;
            $orders = $orders->where('code', 'like', '%' . $sort_search . '%');
        }
        if ($request->delivery_status != null) {
            $orders = $orders->where('delivery_status', $request->delivery_status);
            $delivery_status = $request->delivery_status;
        }
        if ($request->paid != null) {
            $orders = $orders->where('orders.payment_status', $request->paid);
            $payment_status = $request->paid;
        }
        if ($request->route_id != null) {
            $orders = $orders->where('route_id', $request->route_id);
            $route = $request->route_id;
        }
        if ($date != null) {
            //2023-03-13 08:16:47
            $start=explode(" to ", $date)[0]." 00:00:00";
            $end=explode(" to ", $date)[1]." 00:00:00";
               $booleanAll=false;
            // return $start." to ".$end;
             
             if($start==$end)
             {
                 //return $end."na totday oooh";
                   $orders = $orders->where('orders.created_at', '>=', date('Y-m-d', strtotime(explode(" to ", $date)[0])));
             }
             else{
            $orders = $orders->where('orders.created_at', '>=', date('Y-m-d', strtotime(explode(" to ", $date)[0])))
            
            ->where('orders.created_at', '<=', date('Y-m-d', strtotime(explode(" to ", $date)[1])));
             }
        }
        
        $orders=$orders->leftjoin("order_details as o",
        "orders.id","o.order_id");
        $orders=$orders->leftjoin("products as p","p.id","o.product_id");
        $orders=$orders->leftjoin('users as u','u.id','orders.user_id');
        $orders=$orders->leftjoin('customer_routes as ru','ru.id','orders.route_id');
        $orders=$orders->leftjoin('brands as br','p.brand_id','br.id');
        
        
        if ($request->product_id != null) {
            $product_id=$request->product_id;
            $orders = $orders->where('o.product_id', $request->product_id);
        }


        $orders =$orders
        ->select("p.brand_id","p.name as product_name", "br.name as brand",
        DB::raw('sum(orders.grand_total-orders.amount_paid) as balance'),
        DB::raw('sum(o.quantity) as total'),
        DB::raw('sum(orders.grand_total) as price'))
        ->groupBy("p.name","p.brand_id")
        ->orderBy(DB::raw('sum(o.quantity)'),"desc")
        ->paginate(500000000);
        $paid=0;
        $unpaid=0;
        $debt=0;
        $totalpaid=0;
        $dt= date('Y-m-d',time());
        $products=Product::orderby("name","asc")->get();

         if($request->export != null)
        {
          $this->exportProductSales($orders);
        }
        else{
        return view('backend.sales.all_orders.brand_sales', compact('orders', 'sort_search', 'delivery_status', 'date',
        'payment_status','routes','route','unpaid','paid','debt','totalpaid','products','product_id'));
        }
    }
    
    
    
      //credit_collections
    public function credit_collections(Request $request)
    {
        $today= date('Y-m-d',time());
       // 
        $date = $request->date;
        $sort_search = null;
        $delivery_status = null;
        $payment_status=null;
        $routes = CustomerRoute::orderBy('id', 'asc')->get();
        $orders = new Payment();
        $route="";
        $product_id="";
        $booleanAll=true;
        if ($request->has('search')) {
            $sort_search = $request->search;
            $orders = $orders->where('code', 'like', '%' . $sort_search . '%');
        }
        if ($request->delivery_status != null) {
            $orders = $orders->where('delivery_status', $request->delivery_status);
            $delivery_status = $request->delivery_status;
        }
        
        if ($request->route_id != null) {
            $orders = $orders->where('route_id', $request->route_id);
            $route = $request->route_id;
        }
         $orders=$orders->leftjoin('orders as or','payments.order_id','or.id');
        /*$orders=$orders->leftjoin("order_details as o",
        "or.id","o.order_id");
        $orders=$orders->leftjoin("products as p","p.id","o.product_id");*/
        $orders=$orders->leftjoin('users as u','u.id','or.user_id');
        $orders=$orders->leftjoin('customer_routes as ru','ru.id','or.route_id');
       
        
              if ($date != null) {
            //2023-03-13 08:16:47
            $start=explode(" to ", $date)[0]." 00:00:00";
            $end=explode(" to ", $date)[1]." 00:00:00";
               $booleanAll=false;
            // return $start." to ".$end;
             
             if($start==$end)
             {
                 //return $end."na totday oooh";
                   $orders = $orders->where('payments.created_at', '>=', date('Y-m-d', strtotime(explode(" to ", $date)[0])));
             }
             else{
            $orders = $orders->where('payments.created_at', '>=', date('Y-m-d', strtotime(explode(" to ", $date)[0])))
            
            ->where('payments.created_at', '<=', date('Y-m-d', strtotime(explode(" to ", $date)[1])));
             }
        }
        if ($request->paid != null) {
            $orders = $orders->where('or.payment_status', $request->paid);
            $payment_status = $request->paid;
        }
        $orders =
        $orders
       
      //
        //->orderBy(DB::raw('sum(u.name)'),"desc")
         /*->select("u.name","u.phone", "ru.name as route",
        
        DB::raw('sum(or.grand_total-or.amount_paid) as balance'),
        
        DB::raw('sum(payments.amount) as total'))*/
        
        ->select("u.name","u.phone", "ru.name as route",
        DB::raw('sum(payments.amount) as total'),
          DB::raw('sum(or.grand_total-or.amount_paid) as balance')
        )
       // ->with('road')
        ->where("or.created_at","<",$today)
        ->groupBy("u.name","u.phone","or.route_id")
        ->orderBy("u.name","asc")
        ->paginate(500000000);
        $paid=0;
        $unpaid=0;
        $debt=0;
        $totalpaid=0;
        $dt= date('Y-m-d',time());
        $products=Product::orderby("name","asc")->get();
        
        //return $orders;
         if($request->export != null)
        {
          $this->exportCreditSales($orders);
        }
        else{
           // return $orders;
        return view('backend.sales.all_orders.creditcollections', compact('orders', 'sort_search', 'delivery_status', 'date',
        'payment_status','routes','route','unpaid','paid','debt','totalpaid','products','product_id'));
        }
    }
    //total collections
     public function total_collections(Request $request)
    {
        $today= date('Y-m-d',time());
       // 
        $date = $request->date;
        $sort_search = null;
        $delivery_status = null;
        $payment_status=null;
        $routes = CustomerRoute::orderBy('id', 'asc')->get();
        $orders = new Payment();
        $route="";
        $product_id="";
        $booleanAll=true;
        if ($request->has('search')) {
            $sort_search = $request->search;
            $orders = $orders->where('code', 'like', '%' . $sort_search . '%');
        }
        if ($request->delivery_status != null) {
            $orders = $orders->where('delivery_status', $request->delivery_status);
            $delivery_status = $request->delivery_status;
        }
       
        if ($request->route_id != null) {
            $orders = $orders->where('route_id', $request->route_id);
            $route = $request->route_id;
        }
         $orders=$orders->leftjoin('orders as or','payments.order_id','or.id');
        /*$orders=$orders->leftjoin("order_details as o",
        "or.id","o.order_id");
        $orders=$orders->leftjoin("products as p","p.id","o.product_id");*/
        $orders=$orders->leftjoin('users as u','u.id','or.user_id');
        $orders=$orders->leftjoin('customer_routes as ru','ru.id','or.route_id');
       
        
              if ($date != null) {
            //2023-03-13 08:16:47
            $start=explode(" to ", $date)[0]." 00:00:00";
            $end=explode(" to ", $date)[1]." 00:00:00";
               $booleanAll=false;
            // return $start." to ".$end;
             
             if($start==$end)
             {
                  $startdate= date('Y-m-d', strtotime($start));
                 
                 
                 //return $end."na totday oooh";
                   $orders = $orders->where('payments.created_at', 'like', "%".$startdate."%");
                 //  ->where('payments.created_at', '<=',$end);
                 //return explode(" to ", $date)[1];
             }
             else{
            $orders = $orders->where('payments.created_at', '>=', date('Y-m-d', strtotime(explode(" to ", $date)[0])))
            
            ->where('payments.created_at', '<=', date('Y-m-d', strtotime(explode(" to ", $date)[1])));
             }
        }
        $orders =
        $orders
       
      //
        //->orderBy(DB::raw('sum(u.name)'),"desc")
         /*->select("u.name","u.phone", "ru.name as route",
        
        DB::raw('sum(or.grand_total-or.amount_paid) as balance'),
        
        DB::raw('sum(payments.amount) as total'))*/
        
       
          ->orderBy("u.name","asc");
       // ->with('road')
       // ->where("or.created_at","<",$today)
       $detailed="";
        if ($request->detailed==2) {
              $detailed=$request->detailed;
              
        $orders=$orders
         ->select("payments.created_at as payment_date","or.created_at","u.name","u.phone", "ru.name as route",
        DB::raw('sum(payments.amount) as total'),
          DB::raw('sum(or.grand_total-or.amount_paid) as balance')
        )
        ->groupBy("u.name","u.phone","or.route_id","payments.order_id","or.created_at");
        
       // return $orders->get();
        
        }
        else{
            $orders=$orders
             ->select("u.name","u.phone", "ru.name as route",
        DB::raw('sum(payments.amount) as total'),
          DB::raw('sum(or.grand_total-or.amount_paid) as balance')
        )
            ->groupBy("u.name","u.phone","or.route_id");
        }
       /*
        if ($request->paid != null) {
            $orders = $orders->where('or.payment_status', $request->paid);
            $payment_status = $request->paid;
        }*/
       
        $orders=$orders
        ->orderBy("u.name","asc")
        ->paginate(50);
        $paid=0;
        $unpaid=0;
        $debt=0;
        $totalpaid=0;
        $dt= date('Y-m-d',time());
        $products=Product::orderby("name","asc")->get();
        
        //return $orders;
         if($request->export != null)
        {
          $this->exportCreditSales($orders);
        }
        else{
           // return $orders;
        return view('backend.sales.all_orders.totalcollections', compact('orders', 'sort_search', 'delivery_status', 'date',
        'payment_status','routes','route','unpaid','paid','debt','totalpaid','products','product_id','detailed'));
        }
    }
    
    public function exportCreditSales($data)
    {
        
     $delimiter = ","; 
    $filename = "credit_sales_" . date('Y-m-d') . ".csv"; 
    // Create a file pointer 
    $f = fopen('php://memory', 'w'); 
    // Set column headers 
    $fields = array('Customer','Phone', 'Payment','Balance','Route','Order Date','Payement Date'); 
    fputcsv($f, $fields, $delimiter); 
    // Output each row of the data, format line as csv and write to file pointe
    foreach($data as $row){ 
        if(isset($row['created_at']))
        {
            $lineData = array($row['name'], $row['phone'], $row['total'],$row['balance'],$row['route'],$row['created_at'],$row['payment_date']); 
        }
        else{
            $lineData = array($row['name'], $row['phone'], $row['total'],$row['balance'],$row['route'],'N/A','N/A'); 
        }
         
        fputcsv($f, $lineData, $delimiter); 
    } 
    
    $this->export($f,$filename);
  
    }
   
    public function exportProductSales($data)
    {
        
     $delimiter = ","; 
    $filename = "product_sales_" . date('Y-m-d') . ".csv"; 
    // Create a file pointer 
    $f = fopen('php://memory', 'w'); 
    // Set column headers 
    $fields = array('Name', 'Route', 'Sales','Balance'); 
    fputcsv($f, $fields, $delimiter); 
    // Output each row of the data, format line as csv and write to file pointe
    foreach($data as $row){ 
         $lineData = array($row['product_name'], $row['route'], $row['total'],$row['balance']); 
        fputcsv($f, $lineData, $delimiter); 
    } 
    
    $this->export($f,$filename);
  
    }
    
    
}
