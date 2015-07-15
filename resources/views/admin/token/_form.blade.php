<div class="form-group">
    {!! Form::label('package_id', 'Package ID', array('class' => 'col-md-3 control-label')) !!}
    <div class="col-md-8">
        {!! Form::text('package_id', $package_id, array('name'=>'package_id','class'=>'form-control','type'=>'text','autofocus')) !!}
    </div>
</div>
<div class="form-group">
    {!! Form::label('name', 'App name', array('class' => 'col-md-3 control-label')) !!}
    <div class="col-md-8">
        {!! Form::text('name', $name, array('name'=>'name','class'=>'form-control','type'=>'text')) !!}
    </div>
</div>

<div class="form-group">
    {!! Form::label('description', 'Description', array('class' => 'col-md-3 control-label')) !!}
    <div class="col-md-8">
        {!! Form::textarea('description', $description, array('name'=>'description','class'=>'form-control')) !!}
    </div>
</div>
