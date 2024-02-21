<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\Expensetype;
use App\Models\Unit;
use App\Models\CustomerRoute;
use App\Models\BrandTranslation;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ExpensesController extends Controller
{
    public function index(Request $request)
    {
        $sort_search =null;
        $date="";
        $brands = Expense::
        with('type')
        ->orderBy('id', 'desc');
        if ($request->has('search')){
            $sort_search = $request->search;
            $brands = $brands->where('name', 'like', '%'.$sort_search.'%');
        }
        
        if ($date != null) {
            $date = $request->date;
       
            //2023-03-13 08:16:47
            $start=explode(" to ", $date)[0]." 00:00:00";
            $end=explode(" to ", $date)[1]." 00:00:00";
               $booleanAll=false;
            // return $start." to ".$end;
             
             if($start==$end)
             {
                $brands = $brands->where('orders.created_at', '>=', date('Y-m-d', strtotime(explode(" to ", $date)[0])));
             }
             else{
            $brands = $brands->where('orders.created_at', '>=', date('Y-m-d', strtotime(explode(" to ", $date)[0])))
            
            ->where('orders.created_at', '<=', date('Y-m-d', strtotime(explode(" to ", $date)[1])));
             }
        }
        $brands = $brands->paginate(500);

        $total=0;
        foreach($brands as $brand){

            $total+=$brand->amount;
        }

      $types = Expensetype::orderBy('name', 'asc')->get();
        return view('backend.expenses.index', compact('brands', 'sort_search','types','total','date'));
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
        $user_id=\Auth::user()->id;
        
        $brand = new Expense;
        $brand->amount = $request->amount;
        $brand->description = $request->description;
        $brand->expense_type = $request->expense_type;
        $brand->created_by = $user_id;
        $brand->save();
        flash(('Expense has been inserted successfully'))->success();
        return redirect()->route('expense.index');

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


        $sort_search =null;
        $brands = Expense::orderBy('name', 'asc');
        if ($request->has('search')){
            $sort_search = $request->search;
            $brands = $brands->where('name', 'like', '%'.$sort_search.'%');
        }
        $brands = $brands->paginate(15);
        return view('backend.expenses.index', compact('brands', 'sort_search'));


        /*
        $lang   = $request->lang;
        $brand  = Dimension::findOrFail($id);
        return view('backend.dimensions.edit', compact('brand','lang'));*/
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,$id)
    {
        //$id=$request->id;
        $brand = Expense::findOrFail($id);
        if($request->lang == env("DEFAULT_LANGUAGE")){
            $brand->name = $request->name;
        }
        //$brand->description = $request->description;
        $brand->update();
        flash(('Expense has been updated successfully'))->success();
         return redirect()->route('expense.index');
        //return back();

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $brand = Expense::findOrFail($id);
        Expense::destroy($id);
        flash(translate('Expense has been deleted successfully'))->success();
        return redirect()->route('expense.index');

    }


    //summary
     //sales by route
     public function summary(Request $request)
     {
        // 
         $date = $request->date;
         $sort_search = null;
         $delivery_status = null;
         $payment_status=null;
        $orders = new Expense();
        $route="";
         $product_id="";
         $booleanAll=true;
         if ($request->has('search')) {
             $sort_search = $request->search;
             $orders = $orders->where('code', 'like', '%' . $sort_search . '%');
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
         
         $orders=$orders->leftjoin("expensetypes as t",
         "expenses.expense_type","t.id");
         $orders =
         $orders
         ->select("t.name as type",
        DB::raw('sum(expenses.amount) as total'))
         ->groupBy("t.name")
         ->orderBy(DB::raw('sum(expenses.amount)'),"desc")
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
         return view('backend.expenses.summary', compact('orders', 'sort_search', 'delivery_status', 'date',
         'payment_status','unpaid','paid','debt','totalpaid','products','product_id'));
         }
     }
}
