@extends('frontend.layouts.user_panel')
@section('panel_content')

<div class="aiz-titlebar text-left mt-2 mb-3">
	<div class="row align-items-center">
		<div class="col-md-6">
			<h1 class="h3">{{translate('Driving Profile')}}</h1>
		</div>
		<div class="col-md-6 text-md-right">
			<a href="{{ route('drivers.index') }}" class="btn btn-circle btn-info">
				<span>{{translate('Back')}}</span>
			</a>
		</div>
	</div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">Complete Profile</h5>
    </div>
    <div class="card-body">

        <form class="form-horizontal" action="{{ route('drivers.storedriver') }}"
         method="POST" enctype="multipart/form-data">
            @csrf
            <div class="card-body">

                <div class="form-group row">
                    <label class="col-md-3 col-form-label"
                        for="signinSrEmail">Driver's Photo</label>
                    <div class="col-md-8">
                        <div class="input-group" data-toggle="aizuploader"
                        data-type="image" data-multiple="true">
                            <div class="input-group-prepend">
                                <div class="input-group-text bg-soft-secondary font-weight-medium">
                                    {{ translate('Browse')}}</div>
                            </div>
                            <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                            <input type="hidden" name="photo" class="selected-files" {{$required}}>
                        </div>
                        <div class="file-preview box sm">
                        </div>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-sm-3 col-from-label" for="mobile">{{translate('Permit No')}}</label>
                    <div class="col-sm-9">
                        <input type="text" placeholder="{{translate('Permit Number')}}" id="mobile"
                        value="{{$driver->PermitNumber}}"
                        {{$required}}

                        name="permit_no" class="form-control" >
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-3 col-form-label"
                        for="signinSrEmail">{{translate('Permit Images')}}</label>
                    <div class="col-md-8">
                        <div class="input-group" data-toggle="aizuploader"
                        data-type="image" data-multiple="true">
                            <div class="input-group-prepend">
                                <div class="input-group-text bg-soft-secondary font-weight-medium">
                                    {{ translate('Browse')}}</div>
                            </div>
                            <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                            <input type="hidden" name="permit_photos" class="selected-files" {{$required}}>
                        </div>
                        <div class="file-preview box sm">
                        </div>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-sm-3 col-from-label" for="mobile">{{translate('Nin')}}</label>
                    <div class="col-sm-9">
                        <input type="text" placeholder="{{translate('NIN')}}" id="mobile"
                        value="{{$driver->NIN}}" {{$required}}
                         name="nin" class="form-control" >
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-3 col-form-label"
                        for="signinSrEmail">{{translate('National ID Images')}}</label>
                    <div class="col-md-8">
                        <div class="input-group" data-toggle="aizuploader"
                        data-type="image" data-multiple="true">
                            <div class="input-group-prepend">
                                <div class="input-group-text bg-soft-secondary font-weight-medium">
                                    {{ translate('Browse')}}</div>
                            </div>
                            <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                            <input type="hidden" name="nin_photos" class="selected-files" {{$required}}>
                        </div>
                        <div class="file-preview box sm">
                        </div>
                    </div>
                </div>
                <!--
                <div class="form-group row">
                    <label class="col-sm-3 col-from-label" for="name">{{translate('Role')}}</label>
                    <div class="col-sm-9">
                        <select name="role_id" required class="form-control aiz-selectpicker">
                            @foreach($roles as $role)
                                <option value="{{$role->id}}">{{$role->getTranslation('name')}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                -->
                <div class="form-group mb-0 text-right">
                    <button type="submit" class="btn btn-sm btn-primary">{{translate('Save')}}</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection
