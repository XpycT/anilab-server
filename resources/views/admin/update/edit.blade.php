@extends('admin.layout')

@section('content')
    <div class="container-fluid">
        <div class="row page-title-row">
            <div class="col-md-12">
                <h3>Updates <small>» Edit Update</small></h3>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Update Edit Form</h3>
                    </div>
                    <div class="panel-body">
                        @include('admin.partials.success')
                        @include('admin.partials.errors')
                        {!! Form::open(array('action' => array('Admin\UpdateController@update',$update->id), 'files' => true , 'method' => 'put', 'class'=>'form-horizontal')) !!}
                        {!! Form::hidden('id', $update->id, array('name'=>'id')) !!}
                            @include('admin.update._form_edit')
                            <div class="form-group">
                                <div class="col-md-7 col-md-offset-3">
                                    <button type="submit" class="btn btn-primary btn-md">
                                        <i class="fa fa-save"></i>
                                        Save Changes
                                    </button>
                                    <button type="button" class="btn btn-danger btn-md"
                                            data-toggle="modal" data-target="#modal-delete">
                                        <i class="fa fa-times-circle"></i>
                                        Delete
                                    </button>
                                </div>
                            </div>
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Confirm Delete --}}
    <div class="modal fade" id="modal-delete" tabIndex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">
                        ×
                    </button>
                    <h4 class="modal-title">Please Confirm</h4>
                </div>
                <div class="modal-body">
                    <p class="lead">
                        <i class="fa fa-question-circle fa-lg"></i>
                        Are you sure you want to delete this update?
                    </p>
                </div>
                <div class="modal-footer">
                    {!! Form::open(array('action' => array('Admin\UpdateController@destroy',$update->id) , 'method' => 'DELETE', 'class'=>'form-horizontal')) !!}
                        <button type="button" class="btn btn-default"
                                data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fa fa-times-circle"></i> Yes
                        </button>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>

@stop