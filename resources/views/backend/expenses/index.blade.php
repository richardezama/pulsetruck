@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
	<div class="align-items-center">
			<h1 class="h3">{{translate('Expenses')}}</h1>
	</div>
</div>

<div class="row">
	<div class="col-md-7">
		<div class="card">
		    <div class="card-header row gutters-5">
				<div class="col text-center text-md-left">
					<h5 class="mb-md-0 h6">{{ translate('Expenses') }} {{single_price($total)}}</h5>
				</div>
				<div class="col-md-6">
					<form class="" id="sort_brands" action="" method="GET">
                        <table>
                        <tr>
                            <td>
							    <input type="text" class="aiz-date-range form-control"
                                value="{{ $date }}" name="date" placeholder="{{ translate('Filter by date') }}"
                                 data-format="DD-MM-Y" data-separator=" to " data-advanced-range="true" autocomplete="off">
                            </td>
                            <td>
                                 <button type="submit" class="btn btn-primary">{{ translate('Filter') }}</button>
                            </td>
                        </tr>
                        </table>

					</form>
				</div>
		    </div>
		    <div class="card-body">
		        <table class="table aiz-table mb-0">
		            <thead>
		                <tr>
		                    <th>#</th>
                            <th>{{translate('Type')}}</th>
		                    <th>{{translate('Amount')}}</th>
                            <th>{{translate('Description')}}</th>
                            <th class="text-right">{{translate('Options')}}</th>
		                </tr>
		            </thead>
		            <tbody>
		                @foreach($brands as $key => $brand)
		                    <tr>
		                        <td>{{ ($key+1) + ($brands->currentPage() - 1)*$brands->perPage() }}</td>
		                        <td>{{ single_price($brand->amount) }}</td>
                                <td>{{ $brand->type->name }}</td>
                                <td>{{ $brand->description }}</td>
		                        <td class="text-right">

		                            <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm
                                     confirm-delete" data-href="{{route('expense.destroy', $brand->id)}}" title="{{ translate('Delete') }}">
		                                <i class="las la-trash"></i>
		                            </a>
		                        </td>
		                    </tr>
		                @endforeach
		            </tbody>
		        </table>
		        <div class="aiz-pagination">
                	{{ $brands->appends(request()->input())->links() }}
            	</div>
		    </div>
		</div>
	</div>
	<div class="col-md-5">
		<div class="card">
			<div class="card-header">
				<h5 class="mb-0 h6">{{ translate('Add Expense') }}</h5>
			</div>
			<div class="card-body">
				<form action="{{ route('expense.store') }}" method="POST">
					@csrf
					<div class="form-group mb-3">
						<label for="name">{{translate('Amount')}}</label>
						<input type="text" placeholder="{{translate('Amount')}}" name="amount" class="form-control" required>
					</div>

                    <div class="form-group row" id="category">
                        <label class="col-md-3 col-from-label">{{translate('Type')}} <span class="text-danger">*</span></label>
                        <br/>
                        <div class="col-md-12">
                            <select class="form-control aiz-selectpicker" name="expense_type"
                             id="product_id" data-live-search="true" required>
                                @foreach ($types as $category)
                                <option value="{{ $category->id }}">{{ $category->name}}</option>

                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group mb-3">
						<label for="name">{{translate('Description')}}</label>
						<input type="text" placeholder="{{translate('Description')}}" name="description" class="form-control" required>
					</div>


					<div class="form-group mb-3 text-right">
						<button type="submit" class="btn btn-primary">{{translate('Save')}}</button>
					</div>
				</form>
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
    function sort_brands(el){
        $('#sort_brands').submit();
    }
</script>
@endsection
