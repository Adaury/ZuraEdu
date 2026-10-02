{{--
    Íconos de la pestaña / pantalla de inicio con la marca ZuraEdu. Incluir dentro de <head> de las páginas independientes.
    El portal instalable (PWA) declara su propio apple-touch-icon y theme-color por colegio (/pwa/icon, con el color del centro): allí se pasa
    ['sinPwa' => true] para no duplicarlos.
--}}
<link rel="icon" type="image/svg+xml" href="{{ asset('brand/favicon.svg') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('brand/favicon-32.png') }}">
<link rel="alternate icon" href="{{ asset('favicon.ico') }}">
@if(empty($sinPwa))
<link rel="apple-touch-icon" href="{{ asset('brand/apple-touch-icon.png') }}">
<meta name="theme-color" content="#1e3a6e">
@endif
