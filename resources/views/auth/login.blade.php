@extends('admin.layout')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6 col-md-offset-3">
                <div class="panel panel-default">
                    <div class="panel-heading">Авторизация</div>
                    <div class="panel-body">
                        @include('admin.partials.errors')
                        {!! Form::open(array('action' => 'Auth\AuthController@getLogin' , 'method' => 'post', 'class'=>'form-horizontal')) !!}
                        <div class="form-group">
                            {!! Form::label('email', 'E-Mail', array('class' => 'col-md-4 control-label')) !!}
                            <div class="col-md-6">
                                {!! Form::email('email', old('email'), array('name'=>'email','class'=>'form-control','type'=>'email','autofocus')) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            {!! Form::label('password', 'Пароль', array('class' => 'col-md-4 control-label')) !!}
                            <div class="col-md-6">
                                {!! Form::password('password', array('name'=>'password','class'=>'form-control','type'=>'password','autofocus')) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-md-6 col-md-offset-4">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('remember','remember') !!} Запомнить меня
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-md-6 col-md-offset-4">
                                {!! Form::submit('Вход',array('class'=>'btn btn-primary')) !!}
                            </div>
                        </div>
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection