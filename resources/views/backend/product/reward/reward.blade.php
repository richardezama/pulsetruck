@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <h5 class="mb-0 h6">{{translate('Reward Customer')}}</h5>
</div>

<div class="col-lg-8 mx-auto">
    <div class="card">
        <div class="card-body p-0">
           
            <form class="p-4" action="{{ route('reward.post', $user->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group row">
                    <label class="col-sm-3 col-from-label" for="name">{{translate('Select Gift')}} <i class="las la-language text-danger" title="{{translate('Translatable')}}"></i></label>
                    <div class="col-sm-9">
                       <select name="product" class="form-control">
                            @foreach($products as $item)
                      
                           <option value="{{$item->id}}">{{$item->name}}
                           </option>
@endforeach
     </select>
</div>
                </div>
               
               <input type="hidden" class="form-control" name="user_id" value="{{ $user->id }}">
             
                
                
                <div class="form-group mb-0 text-right">
                    <button type="submit" class="btn btn-primary">{{translate('Reward Now')}}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
