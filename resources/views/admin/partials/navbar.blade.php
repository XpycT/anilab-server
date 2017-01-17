<ul class="nav navbar-nav">
    @if (Auth::check())
        <li @if (Request::is('admin/token*')) class="active" @endif>
            {!! Html::linkAction('Admin\TokenController@index', 'Tokens') !!}
        </li>
        <li @if (Request::is('admin/update*')) class="active" @endif>
            {!! Html::linkAction('Admin\UpdateController@index', 'Updates') !!}
        </li>
    @endif
</ul>

<ul class="nav navbar-nav navbar-right">
    @if (Auth::guest())
        <li>{!! Html::linkAction('Auth\AuthController@getLogin', 'Вход') !!}</li>
    @else
        <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button"
               aria-expanded="false">{{ Auth::user()->name }}
                <span class="caret"></span>
            </a>
            <ul class="dropdown-menu" role="menu">
                <li>{!! Html::linkAction('Auth\AuthController@getLogout', 'Выход') !!}</li>
            </ul>
        </li>
    @endif
</ul>