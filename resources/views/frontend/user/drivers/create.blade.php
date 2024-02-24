@extends('frontend.layouts.user_panel')
@section('panel_content')

<div class="aiz-titlebar text-left mt-2 mb-3">
	<div class="row align-items-center">
		<div class="col-md-6">
			<h1 class="h3">{{translate('My Drivers')}}</h1>
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
        <h5 class="mb-0 h6">{{translate('Drivers')}}</h5>
    </div>
    <div class="card-body">
        <!--<h5 class="mb-0 h6">{{translate('Staff Information')}}</h5> -->
        <form class="form-horizontal" action="{{ route('drivers.store') }}" method="POST" enctype="multipart/form-data">
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
                            <input type="hidden" name="photo" class="selected-files">
                        </div>
                        <div class="file-preview box sm">
                        </div>
                    </div>
                </div>


                <div class="form-group row">
                    <label class="col-sm-3 col-from-label" for="name">{{translate('Name')}}</label>
                    <div class="col-sm-9">
                        <input type="text" placeholder="{{translate('Name')}}" id="name" name="name" class="form-control" required>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-from-label" for="email">{{translate('Email')}}</label>
                    <div class="col-sm-9">
                        <input type="text" placeholder="{{translate('Email')}}" id="email" name="email" class="form-control" required>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-from-label" for="mobile">{{translate('Phone')}}</label>
                    <div class="col-sm-9">
                        <input type="text" placeholder="{{translate('Phone')}}" id="mobile" name="mobile" class="form-control" required>
                    </div>
                </div>



                <div class="form-group row">
                    <label class="col-sm-3 col-from-label" for="mobile">{{translate('Permit No')}}</label>
                    <div class="col-sm-9">
                        <input type="text" placeholder="{{translate('Permit Number')}}" id="mobile" name="permit_no" class="form-control" required>
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
                            <input type="hidden" name="permit_photos" class="selected-files">
                        </div>
                        <div class="file-preview box sm">
                        </div>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-sm-3 col-from-label" for="mobile">{{translate('Nin')}}</label>
                    <div class="col-sm-9">
                        <input type="text" placeholder="{{translate('NIN')}}" id="mobile"
                         name="nin" class="form-control" required>
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
                            <input type="hidden" name="nin_photos" class="selected-files">
                        </div>
                        <div class="file-preview box sm">
                        </div>
                    </div>
                </div>




                <div class="form-group row">
                    <label class="col-sm-3 col-from-label" for="password">{{translate('Password')}}</label>
                    <div class="col-sm-9">
                        <input type="password" placeholder="{{translate('Password')}}" id="password" name="password" class="form-control" required>
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
