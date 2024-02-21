@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="align-items-center">
        <h1 class="h3">{{translate('Edit Customer')}}</h1>
          
    </div>
    
     @if (session()->has('message'))

            <div class="alert alert-success">

                {{ session('message') }}

            </div>

        @endif
</div>


<div class="card">
    <form id="sort_customers" action="{{route('customer.update')}}" method="post">
         <input type="hidden" name="id" class="form-control" value="{{$user->id}}"/>
        	@csrf
        <div class="card-header row gutters-5">
            <div class="col">
                <h5 class="mb-0 h6">{{$user->name}}</h5>
            </div>
            
           
            
           
        </div>
    
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        
                        <th>{{translate('Name')}}</th>
                        <th data-breakpoints="lg">
                            {{translate('Email Address')}}
                            </th>
                        <th data-breakpoints="lg">{{translate('Phone')}}</th>
                        
                        <th>{{translate('Options')}}</th>
                    </tr>
                </thead>
                <tbody>
                            <tr>
                                 
                                <td>
                                    
                                      <input type="text" name="name" class="form-control" value="{{$user->name}}"/>
                           
                                     </td>
                                <td>
                                     <input type="text" name="email"
                                     class="form-control"
                                     value="{{$user->email}}"/>
                                    
                                </td>
                                <td>
                                     <input type="text" name="phone" 
                                     class="form-control"
                                     value="{{$user->phone}}"/>
                                </td>
                               
                                <td class="text-right">
                                  <input type="submit" class="btn btn-success" value="Edit User"/>
                                </td>
                            </tr>
                </tbody>
            </table>
           
        </div>
        
       
    </form>
</div>


<div class="modal fade" id="confirm-ban">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title h6">{{translate('Confirmation')}}</h5>
                <button type="button" class="close" data-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>{{translate('Do you really want to ban this Customer?')}}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">{{translate('Cancel')}}</button>
                <a type="button" id="confirmation" class="btn btn-primary">{{translate('Proceed!')}}</a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirm-unban">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title h6">{{translate('Confirmation')}}</h5>
                <button type="button" class="close" data-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>{{translate('Do you really want to unban this Customer?')}}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">{{translate('Cancel')}}</button>
                <a type="button" id="confirmationunban" class="btn btn-primary">{{translate('Proceed!')}}</a>
            </div>
        </div>
    </div>
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
        
        function sort_customers(el){
            $('#sort_customers').submit();
        }
        function confirm_ban(url)
        {
            $('#confirm-ban').modal('show', {backdrop: 'static'});
            document.getElementById('confirmation').setAttribute('href' , url);
        }

        function confirm_unban(url)
        {
            $('#confirm-unban').modal('show', {backdrop: 'static'});
            document.getElementById('confirmationunban').setAttribute('href' , url);
        }
        
        function bulk_delete() {
            var data = new FormData($('#sort_customers')[0]);
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: "{{route('bulk-customer-delete')}}",
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
