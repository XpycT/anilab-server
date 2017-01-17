@extends('admin.layout')

@section('content')
    <div class="container-fluid">
        <div class="row page-title-row">
            <div class="col-md-6">
                <h3>Updates <small>» Listing</small></h3>
            </div>
            <div class="col-md-6 text-right">
                <a href="{{url('admin/update/create')}}" class="btn btn-success btn-md">
                    <i class="fa fa-plus-circle"></i> New Update
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
                        <th>Version Code</th>
                        <th>Version Name</th>
                        <th>Description</th>
                        <th data-sortable="false">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($updates as $update)
                        <tr>
                            <td>{{ $update->version_code }}</td>
                            <td>{{ $update->version_name }}</td>
                            <td>{{ $update->content }}</td>
                            <td>
                                <a href="{{route('admin.update.edit',array('update'=>$update->id))}}"
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