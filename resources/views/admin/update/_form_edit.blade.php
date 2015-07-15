<div class="form-group">
    {!! Form::label('version_code', 'Version Code', array('class' => 'col-md-3 control-label')) !!}
    <div class="col-md-8">
        {!! Form::number('version_code', $update->version_code, array('name'=>'version_code','class'=>'form-control','type'=>'number','autofocus')) !!}
    </div>
</div>
<div class="form-group">
    {!! Form::label('version_name', 'Version Name', array('class' => 'col-md-3 control-label')) !!}
    <div class="col-md-8">
        {!! Form::text('version_name', $update->version_name, array('name'=>'version_name','class'=>'form-control','type'=>'text')) !!}
    </div>
</div>
<div class="form-group">
    {!! Form::label('apk_file', 'Apk file', array('class' => 'col-md-3 control-label')) !!}
    <div class="col-md-8">
        @if ($update->original_filename)
            {{$update->original_filename}}
        @endif
        {!! Form::file('apk_file', array('name'=>'apk_file','class'=>'form-control')) !!}
    </div>
</div>

<div class="form-group">
    {!! Form::label('content', 'Description', array('class' => 'col-md-3 control-label')) !!}
    <div class="col-md-8">
        {!! Form::textarea('content', $update->content, array('name'=>'content','class'=>'form-control')) !!}
    </div>
</div>
