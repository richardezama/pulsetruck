<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Dimension;
use App\Models\Unit;
use App\Models\CustomerRoute;
use App\Models\BrandTranslation;
use App\Models\Product;
use Illuminate\Support\Str;

class DimensionsController extends Controller
{
    public function index(Request $request)
    {
        $sort_search =null;
        $brands = Dimension::orderBy('name', 'asc');
        if ($request->has('search')){
            $sort_search = $request->search;
            $brands = $brands->where('name', 'like', '%'.$sort_search.'%');
        }
        $brands = $brands->paginate(15);
        return view('backend.dimensions.index', compact('brands', 'sort_search'));
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
        $brand = new Dimension;
        $brand->name = $request->name;
        $brand->description = $request->description;
        $brand->save();
        flash(('Dimension has been inserted successfully'))->success();
        return redirect()->route('dimension.index');

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
        $brands = Dimension::orderBy('name', 'asc');
        if ($request->has('search')){
            $sort_search = $request->search;
            $brands = $brands->where('name', 'like', '%'.$sort_search.'%');
        }
        $brands = $brands->paginate(15);
        return view('backend.dimensions.index', compact('brands', 'sort_search'));


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
        $brand = Dimension::findOrFail($id);
        if($request->lang == env("DEFAULT_LANGUAGE")){
            $brand->name = $request->name;
        }
        $brand->description = $request->description;
        $brand->update();
        flash(('Route has been updated successfully'))->success();
         return redirect()->route('dimension.index');
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
        $brand = Dimension::findOrFail($id);
       
        Dimension::destroy($id);
        flash(translate('Dimension has been deleted successfully'))->success();
        return redirect()->route('dimension.index');

    }
}
