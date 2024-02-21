<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Brand;
use App\Models\BrandTranslation;
use App\Models\Product;
use App\Models\Reward;
use App\Models\User;
use App\Models\RewardProduct;
use Illuminate\Support\Str;

class RewardController extends Controller
{

    public function products(Request $request)
    {
        $sort_search =null;
        $brands = RewardProduct::orderBy('name', 'asc');
        if ($request->has('search')){
            $sort_search = $request->search;
            $brands = $brands->where('name', 'like', '%'.$sort_search.'%');
        }
        $brands = $brands->paginate(15);
        return view('backend.product.reward.index', compact('brands', 'sort_search'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $brand = new RewardProduct();
        $brand->name = $request->name;
          $brand->points = $request->points;
        $brand->price = $request->price;
        $brand->save();
        flash(translate('Item has been inserted successfully'))->success();
        return redirect()->route('reward.products');

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
    public function edit(Request $request, $id)
    {
        $lang   = $request->lang;
        $brand  = RewardProduct::findOrFail($id);
        return view('backend.product.reward.edit', compact('brand','lang'));
    }
    
    //reward here
    
       public function reward(Request $request, $id)
    {
        $lang   = $request->lang;
        $user  = User::findOrFail($id);
         $products  = RewardProduct::orderBy("name","asc")->get();
        return view('backend.product.reward.reward', compact('user','lang','products'));
    }
    
    public function post(Request $request, $id)
    {
        $item=$request->product;
        $user = User::findOrFail($id);
        $product = RewardProduct::findOrFail($item);
       if($product->points>$user->points)
       {
        flash(translate('Insufficient Points'))->warning();
        return back(); 
       }
       else{
           
           
           //insert a log
           
           $r=new Reward();
           $r->points=$product->points;
           $r->user_id=$id;
           $r->item_id=$item;
           $r->save();
           
           $user->points-=$product->points;
           $user->save();
            flash(translate('Success Customer Rewarded'))->success();
        return back();
       }
       
       

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
        $brand = RewardProduct::findOrFail($id);
        $brand->name = $request->name;
        $brand->price = $request->price;
        $brand->points = $request->points;
        $brand->save();
               /*
        $brand_translation = BrandTranslation::firstOrNew(['lang' => $request->lang, 'brand_id' => $brand->id]);
        $brand_translation->name = $request->name;
        $brand_translation->save();*/
        flash(translate('Brand has been updated successfully'))->success();
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
        $brand = RewardProduct::findOrFail($id);
        RewardProduct::destroy($id);
        flash(translate('Reward product has been deleted successfully'))->success();
        return redirect()->route('reward.products');

    }
    
     public function rewardreport(Request $request)
    {
        $lang   = $request->lang;
        $items  = Reward::get();
        $items = Reward::paginate(40);
        return view('backend.customer.customers.rewardreport', compact('items','lang'));
    }
}
