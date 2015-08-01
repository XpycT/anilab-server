<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="apple-touch-icon-precomposed" sizes="57x57" href="./apple-touch-icon-57x57.png" />
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="./apple-touch-icon-114x114.png" />
    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="./apple-touch-icon-72x72.png" />
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="./apple-touch-icon-144x144.png" />
    <link rel="apple-touch-icon-precomposed" sizes="60x60" href="./apple-touch-icon-60x60.png" />
    <link rel="apple-touch-icon-precomposed" sizes="120x120" href="./apple-touch-icon-120x120.png" />
    <link rel="apple-touch-icon-precomposed" sizes="76x76" href="./apple-touch-icon-76x76.png" />
    <link rel="apple-touch-icon-precomposed" sizes="152x152" href="./apple-touch-icon-152x152.png" />
    <link rel="icon" type="image/png" href="./favicon-196x196.png" sizes="196x196" />
    <link rel="icon" type="image/png" href="./favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/png" href="./favicon-32x32.png" sizes="32x32" />
    <link rel="icon" type="image/png" href="./favicon-16x16.png" sizes="16x16" />
    <link rel="icon" type="image/png" href="./favicon-128.png" sizes="128x128" />
    <meta name="application-name" content="AniLab"/>
    <meta name="msapplication-TileColor" content="#FFFFFF" />
    <meta name="msapplication-TileImage" content="./mstile-144x144.png" />
    <meta name="msapplication-square70x70logo" content="./mstile-70x70.png" />
    <meta name="msapplication-square150x150logo" content="./mstile-150x150.png" />
    <meta name="msapplication-wide310x150logo" content="./mstile-310x150.png" />
    <meta name="msapplication-square310x310logo" content="./mstile-310x310.png" />
    <title>{{ config('api.title') }}</title>
    <link href="/assets/css/landing.css" rel="stylesheet">
    @yield('styles')
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body>
<div class="container">
    <div class="content">
        <div class="title">{{ config('api.title') }}</div>
        <div class="subtitle">Сайт в разработке. Пока можно скачать приложение.</div>
        <div class="row around-xs">

            <div class="col-xs-2">
                <div class="box">
                    <a href="http://anilab.web-zone.com.ua/update/file"><img src="/assets/images/icon_yandex.png" alt="Яндекс.Store"></a>
                </div>
            </div>
            <div class="col-xs-2">
                <div class="box">
                    <a href="https://play.google.com/store/apps/details?id=com.xpyct.apps.anilab"><img src="/assets/images/icon_gplay.png" alt="Google Play"></a>
                </div>
            </div>
            <div class="col-xs-2">
                <div class="box">
                    <a href="http://4pda.ru/forum/index.php?showtopic=681235"><img src="/assets/images/icon_4pda.png" alt="4pda"></a>
                </div>
            </div>
        </div>
    </div>
</div>

@yield('content')

@yield('scripts')
</body>
</html>