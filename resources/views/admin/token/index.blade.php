@extends('admin.layout')

@section('content')
    <div class="container-fluid">
        <div class="row page-title-row">
            <div class="col-md-6">
                <h3>Tokens <small>» Listing</small></h3>
            </div>
            <div class="col-md-6 text-right">
                <a href="{{url('admin/token/create')}}" class="btn btn-success btn-md">
                    <i class="fa fa-plus-circle"></i> New Token
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">

                @include('admin.partials.errors')
                @include('admin.partials.success')

                <table id="tokens-table" class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Package Id</th>
                        <th>Public Key</th>
                        <th data-sortable="false">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($tokens as $token)
                        <tr>
                            <td>{{ $token->name }}</td>
                            <td>{{ $token->package_id }}</td>
                            <td>{{ $token->public_key }}</td>
                            <td>
                                <a href="{{route('admin.token.edit',array('token'=>$token->id))}}"
                                   class="btn btn-xs btn-info">
                                    <i class="fa fa-edit"></i> Edit
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop

@section('scripts')
    <script>
        $(function() {
            $("#tokens-table").DataTable({
            });
        });
    </script>
@stop