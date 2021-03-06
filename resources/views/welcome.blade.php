<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{config('api.description')}}." />
    <meta name="keywords" content="{{config('api.keywords')}}" />
    <link rel="apple-touch-icon-precomposed" sizes="57x57" href="./apple-touch-icon-57x57.png?v1" />
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="./apple-touch-icon-114x114.png?v1" />
    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="./apple-touch-icon-72x72.png?v1" />
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="./apple-touch-icon-144x144.png?v1" />
    <link rel="apple-touch-icon-precomposed" sizes="60x60" href="./apple-touch-icon-60x60.png?v1" />
    <link rel="apple-touch-icon-precomposed" sizes="120x120" href="./apple-touch-icon-120x120.png?v1" />
    <link rel="apple-touch-icon-precomposed" sizes="76x76" href="./apple-touch-icon-76x76.png?v1" />
    <link rel="apple-touch-icon-precomposed" sizes="152x152" href="./apple-touch-icon-152x152.png?v1" />
    <link rel="icon" type="image/png" href="./favicon-196x196.png?v1" sizes="196x196" />
    <link rel="icon" type="image/png" href="./favicon-96x96.png?v1" sizes="96x96" />
    <link rel="icon" type="image/png" href="./favicon-32x32.png?v1" sizes="32x32" />
    <link rel="icon" type="image/png" href="./favicon-16x16.png?v1" sizes="16x16" />
    <link rel="icon" type="image/png" href="./favicon-128.png?v1" sizes="128x128" />
    <meta name="application-name" content="AniLab"/>
    <meta name="msapplication-TileColor" content="#FFFFFF" />
    <meta name="msapplication-TileImage" content="./mstile-144x144.png?v1" />
    <meta name="msapplication-square70x70logo" content="./mstile-70x70.png?v1" />
    <meta name="msapplication-square150x150logo" content="./mstile-150x150.png?v1" />
    <meta name="msapplication-wide310x150logo" content="./mstile-310x150.png?v1" />
    <meta name="msapplication-square310x310logo" content="./mstile-310x310.png?v1" />
    <title>{{ config('api.title') }}</title>
    <link href="/assets/css/landing.css?v1" rel="stylesheet">
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
            <div class="col-xs-3">
                <div class="box">
                    <a href="http://anilab.web-zone.com.ua/update/file"><img src="/assets/images/icon_yandex.png?v1" alt="Яндекс.Store"></a>
                </div>
            </div>
            <div class="col-xs-3">
                <div class="box">
                    <a href="http://www.amazon.com/gp/product/B0137HBJI4"><img src="/assets/images/icon_amazon.png?v1" alt="Amazon"></a>
                </div>
            </div>
            <div class="col-xs-3">
                <div class="box">
                    <a href="http://4pda.ru/forum/index.php?showtopic=681235"><img src="/assets/images/icon_4pda.png?v1" alt="4pda"></a>
                </div>
            </div>
        </div>
    </div>
</div>

@yield('content')

@yield('scripts')

<script>
    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
                (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
            m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

    ga('create', 'UA-65757409-3', 'auto');
    ga('send', 'pageview');

</script>

</body>
</html>