@extends('backend.layouts.app')
@section('content')
<div class="card">
    <form class="" action="" id="sort_orders" method="GET">
        <div class="card-header row gutters-5">
            <div class="col">
                 <h5 class="mb-md-0 h1">{{ translate($msg) }}</h5>
                <h5 class="mb-md-0 h6">{{ translate('SMS LIST') }}</h5>
              
            </div>




          
<div class="col-lg-2 ml-auto">
                <select class="form-control aiz-selectpicker" name="route_id" id="route_id">
                    
                    <option value="">{{translate('Filter by Routes')}}</option>
                    @foreach ($routes as $rt)
                                  @if($route==$rt->id)
                                   <option selected value="{{$rt->id}}">{{ $rt->name }}</option>
                                  @else
                                <option value="{{$rt->id}}">{{ $rt->name }}</option>
                                @endif
                                     
                                @endforeach   
                </select>
            </div>
       
            <div class="col-lg-2">
                <div class="form-group mb-0">
                    <input type="text" class="form-control" id="search" name="search"@isset($sort_search) value="{{ $sort_search }}" @endisset placeholder="{{ translate('Type Order code & hit Enter') }}">
                </div>
            </div>
            <div class="col-auto">
                <div class="form-group mb-0">
                    <button type="submit" class="btn btn-primary">{{ translate('Filter') }}</button>
                </div>
            </div>
            
             <div class="col-auto">
                <div class="form-group mb-0">
                    <button type="submit" name="send" value="yes" class="btn btn-success">{{ translate('Send SMS') }}</button>
                </div>
            </div>
        </div>

        <div class="card-body">
            
             <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                       
                        <th data-breakpoints="md">{{ translate('Customer') }}</th>
                        <th data-breakpoints="md">{{ translate('Phone') }}</th>
                          <th data-breakpoints="md">{{ translate('Route') }}</th>
                        <th data-breakpoints="md">{{ translate('Sales') }}</th>
                        <th data-breakpoints="md">{{ translate('Debt') }}</th>
                        
                    </tr>
                </thead>
                <tbody>
                    @foreach ($final as $order)
                    <tr>
                        <td>
                            {{ $order["name"] }}
                        </td>
                          <td>
                            {{ $order["phone"] }}
                        </td>
                         <td>
                            {{ $order["route"] }}
     
                        </td>
                        <td>
                            {{ $order["sales"] }}
                        </td>
                        <td>
                            {{ ($order["debt"])}}
                        </td>
                       
                    </tr>
                    @endforeach
                </tbody>
            </table>


        </div>
    </form>
</div>

@endsection

@section('modal')
    @include('modals.delete_modal')
@endsection

@section('script')
    <script type="text/javascript">
        $(document).on("change", ".check-all", function() {
            if(this.checked) {
                // Iterate each checkbox
                $('.check-one:checkbox').each(function() {
                    this.checked = true;
                });
            } else {
                $('.check-one:checkbox').each(function() {
                    this.checked = false;
                });
            }

        });

//        function change_status() {
//            var data = new FormData($('#order_form')[0]);
//            $.ajax({
//                headers: {
//                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
//                },
//                url: "{{route('bulk-order-status')}}",
//                type: 'POST',
//                data: data,
//                cache: false,
//                contentType: false,
//                processData: false,
//                success: function (response) {
//                    if(response == 1) {
//                        location.reload();
//                    }
//                }
//            });
//        }

        function bulk_delete() {
            var data = new FormData($('#sort_orders')[0]);
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: "{{route('bulk-order-delete')}}",
                type: 'POST',
                data: data,
                cache: false,
                contentType: false,
                processData: false,
                success: function (response) {
                    if(response == 1) {
                        location.reload();
                    }
                }
            });
        }
    </script>
@endsection
