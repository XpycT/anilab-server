<div class="form-group">
    {!! Form::label('version_code', 'Version Code', array('class' => 'col-md-3 control-label')) !!}
    <div class="col-md-8">
        {!! Form::number('version_code', $version_code, array('name'=>'version_code','class'=>'form-control','type'=>'number','autofocus')) !!}
    </div>
</div>
<div class="form-group">
    {!! Form::label('version_name', 'Version Name', array('class' => 'col-md-3 control-label')) !!}
    <div class="col-md-8">
        {!! Form::text('version_name', $version_name, array('name'=>'version_name','class'=>'form-control','type'=>'text')) !!}
    </div>
</div>

<div class="form-group">
    {!! Form::label('content', 'Description', array('class' => 'col-md-3 control-label')) !!}
    <div class="col-md-8">
        {!! Form::textarea('content', $content, array('name'=>'content','class'=>'form-control')) !!}
    </div>
</div>
