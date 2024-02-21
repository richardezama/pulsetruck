@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <h5 class="mb-0 h6">{{translate('Product')}}</h5>
</div>

<div class="col-lg-8 mx-auto">
    <div class="card">
        <div class="card-body p-0">
           
            <form class="p-4" action="{{ route('reward.update', $brand->id) }}" method="POST" enctype="multipart/form-data">
               
                @csrf
                <div class="form-group row">
                    <label class="col-sm-3 col-from-label" for="name">{{translate('Name')}} <i class="las la-language text-danger" title="{{translate('Translatable')}}"></i></label>
                    <div class="col-sm-9">
                        <input type="text" placeholder="{{translate('Name')}}" id="name" name="name" value="{{ $brand->name }}" class="form-control" required>
                    </div>
                </div>
               
                <div class="form-group row">
                    <label class="col-sm-3 col-from-label">{{translate('Price')}}</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" name="price" value="{{ $brand->price }}" placeholder="{{translate('Price')}}">
                    </div>
                </div>
                
                 <div class="form-group row">
                    <label class="col-sm-3 col-from-label">{{translate('Points Required')}}</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" name="points" value="{{ $brand->points }}" placeholder="{{translate('Points')}}">
                    </div>
                </div>
                
                
                <div class="form-group mb-0 text-right">
                    <button type="submit" class="btn btn-primary">{{translate('Save')}}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
