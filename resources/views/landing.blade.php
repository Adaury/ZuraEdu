<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ZuraEdu — Sistema académico completo con aula virtual. Notas, competencias, horarios y portal para padres en una sola plataforma.">
    <title>ZuraEdu — Sistema educativo inteligente</title>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    {{-- Tailwind CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                    colors: {
                        dark:    '#0f172a',
                        primary: '#2563eb',
                        'primary-dark': '#1d4ed8',
                        success: '#22c55e',
                        'success-dark': '#16a34a',
                    },
                    animation: {
                        'float': 'float 4s ease-in-out infinite alternate',
                        'pulse-slow': 'pulse 3s ease-in-out infinite',
                    },
                    keyframes: {
                        float: { '0%': { transform: 'translateY(0)' }, '100%': { transform: 'translateY(-8px)' } },
                    },
                },
            },
        }
    </script>
    <style>
        [x-cloak] { display: none; }
        .gradient-text {
            background: linear-gradient(135deg, #34d399, #22c55e);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero-glow {
            background: radial-gradient(ellipse at 70% 50%, rgba(37,99,235,.18) 0%, transparent 65%),
                        radial-gradient(ellipse at 30% 80%, rgba(34,197,94,.1) 0%, transparent 50%);
        }
        .card-hover { transition: transform .25s ease, box-shadow .25s ease; }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0,0,0,.1); }
        .btn-primary-hover { transition: all .2s ease; }
        .btn-primary-hover:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(37,99,235,.4); }
        .btn-success-hover { transition: all .2s ease; }
        .btn-success-hover:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(34,197,94,.4); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .nav-blur { backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); }
    </style>
</head>
<body class="font-sans bg-white text-slate-900 antialiased">

{{-- ═══════════════════════════════════════════════
     NAVBAR
═══════════════════════════════════════════════ --}}
<header class="sticky top-0 z-50 bg-white/90 nav-blur border-b border-slate-200/80 transition-shadow" id="navbar">
    <nav class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">

        {{-- Logo --}}
        <a href="{{ route('landing') }}" class="flex items-center gap-2.5 shrink-0">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-800 to-blue-500 flex items-center justify-center shadow-md">
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/></svg>
            </div>
            <span class="text-lg font-black tracking-tight text-slate-900">ZuraEdu</span>
        </a>

        {{-- Links desktop --}}
        <ul class="hidden md:flex items-center gap-1">
            @foreach([['#beneficios','Beneficios'],['#modulos','Módulos'],['#planes','Planes'],['#demo','Demo']] as [$href,$label])
            <li><a href="{{ $href }}" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-all">{{ $label }}</a></li>
            @endforeach
        </ul>

        {{-- Botones --}}
        <div class="flex items-center gap-2.5">
            <a href="{{ route('login') }}" class="hidden sm:inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-semibold text-slate-700 border border-slate-200 hover:border-blue-300 hover:text-blue-600 hover:bg-blue-50 transition-all">
                Iniciar sesión
            </a>
            <a href="{{ route('onboarding') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-semibold text-white bg-primary hover:bg-primary-dark shadow-md shadow-blue-200 btn-primary-hover">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/></svg>
                Crear escuela
            </a>
        </div>
    </nav>
</header>


{{-- ═══════════════════════════════════════════════
     1. HERO
═══════════════════════════════════════════════ --}}
<section class="bg-dark hero-glow min-h-[90vh] flex items-center overflow-hidden relative">

    {{-- Partículas decorativas --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-16 left-1/4 w-72 h-72 bg-blue-600/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-16 right-1/4 w-64 h-64 bg-emerald-500/8 rounded-full blur-3xl"></div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-20 lg:py-24 w-full">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">

            {{-- Columna izquierda: Texto --}}
            <div class="text-center lg:text-left">
                {{-- Badge --}}
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-blue-600/15 border border-blue-500/25 text-blue-300 text-xs font-semibold mb-6">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse-slow"></span>
                    Plataforma SaaS educativa · Disponible ahora
                </div>

                {{-- Título --}}
                <h1 class="text-4xl sm:text-5xl lg:text-[3.2rem] font-black text-white leading-[1.08] tracking-tight mb-5">
                    ZuraEdu<br>
                    <span class="gradient-text">Sistema académico</span><br>
                    + aula virtual en una<br>sola plataforma
                </h1>

                {{-- Subtítulo --}}
                <p class="text-slate-400 text-base sm:text-lg leading-relaxed mb-8 max-w-lg mx-auto lg:mx-0">
                    Gestiona tu centro educativo con notas por competencias, tareas, horarios y conexión con padres desde cualquier dispositivo.
                </p>

                {{-- Botones CTA --}}
                <div class="flex flex-col sm:flex-row gap-3 justify-center lg:justify-start mb-8">
                    <a href="{{ route('onboarding') }}"
                       class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl text-base font-bold text-white bg-success hover:bg-success-dark shadow-lg shadow-green-900/40 btn-success-hover">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/></svg>
                        Crear mi escuela gratis
                    </a>
                    <a href="{{ route('demo.auto') }}"
                       class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl text-base font-bold text-white border-2 border-white/20 hover:border-white/50 hover:bg-white/10 transition-all duration-200">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z"/></svg>
                        Ver demo
                    </a>
                </div>

                {{-- Trust indicators --}}
                <div class="flex flex-wrap items-center gap-4 justify-center lg:justify-start">
                    @foreach(['Sin tarjeta requerida','30 días gratis','Setup en 2 min'] as $trust)
                    <div class="flex items-center gap-1.5 text-slate-500 text-xs">
                        <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
                        {{ $trust }}
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Columna derecha: Mockup --}}
            <div class="hidden lg:block">
                <div class="animate-float">
                    {{-- Ventana del navegador simulada --}}
                    <div class="bg-slate-800 rounded-2xl shadow-2xl shadow-black/50 border border-slate-700/50 overflow-hidden">
                        {{-- Barra de título --}}
                        <div class="bg-slate-900 px-4 py-3 flex items-center gap-3 border-b border-slate-700/50">
                            <div class="flex gap-1.5">
                                <div class="w-3 h-3 rounded-full bg-red-500/80"></div>
                                <div class="w-3 h-3 rounded-full bg-yellow-500/80"></div>
                                <div class="w-3 h-3 rounded-full bg-green-500/80"></div>
                            </div>
                            <div class="flex-1 max-w-[220px] mx-auto bg-slate-700/60 rounded-md px-3 py-1 text-[10px] text-slate-400 text-center">
                                🔒 miescuela.zuraedu.com
                            </div>
                        </div>

                        {{-- Contenido del dashboard --}}
                        <div class="flex h-[300px]">
                            {{-- Sidebar --}}
                            <div class="w-36 bg-slate-900 p-3 border-r border-slate-700/40 shrink-0">
                                <div class="text-[9px] text-slate-500 font-bold uppercase tracking-widest px-2 mb-2 mt-1">Panel</div>
                                @foreach([['M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z','Dashboard',true],['M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z','Estudiantes',false],['M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z','Calificaciones',false],['M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5','Asistencia',false]] as [$path,$label,$active])
                                <div class="flex items-center gap-2 px-2 py-1.5 rounded-lg mb-0.5 cursor-pointer {{ $active ? 'bg-blue-600/25 text-blue-300' : 'text-slate-400' }}">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/></svg>
                                    <span class="text-[10px] font-medium">{{ $label }}</span>
                                </div>
                                @endforeach
                            </div>

                            {{-- Main content --}}
                            <div class="flex-1 p-4 overflow-hidden">
                                {{-- Stats --}}
                                <div class="grid grid-cols-4 gap-2 mb-3">
                                    @foreach([['248','Estudiantes','#93c5fd'],['18','Docentes','#6ee7b7'],['12','Grupos','#fbbf24'],['94%','Asistencia','#a78bfa']] as [$n,$l,$c])
                                    <div class="bg-slate-700/50 rounded-lg p-2 border border-slate-600/30">
                                        <div class="text-xs font-black" style="color:{{ $c }}">{{ $n }}</div>
                                        <div class="text-[9px] text-slate-500 mt-0.5">{{ $l }}</div>
                                    </div>
                                    @endforeach
                                </div>
                                {{-- Mini table --}}
                                <div class="bg-slate-700/30 rounded-lg overflow-hidden border border-slate-600/20">
                                    <div class="grid grid-cols-4 px-3 py-1.5 bg-slate-700/50 text-[9px] text-slate-400 font-bold uppercase tracking-wider">
                                        <div>Estudiante</div><div>Grupo</div><div>Nota</div><div>Estado</div>
                                    </div>
                                    @foreach([['M. García','1-A','92','A','text-emerald-400','bg-emerald-400/15'],['J. Pérez','2-B','78','B','text-blue-400','bg-blue-400/15'],['L. Soto','1-A','55','F','text-red-400','bg-red-400/15'],['A. Torres','3-C','85','B','text-blue-400','bg-blue-400/15']] as [$nm,$gr,$nt,$lr,$tc,$bc])
                                    <div class="grid grid-cols-4 px-3 py-1.5 border-t border-slate-600/20 text-[10px]">
                                        <div class="text-slate-300">{{ $nm }}</div>
                                        <div class="text-slate-400">{{ $gr }}</div>
                                        <div class="text-slate-300">{{ $nt }}</div>
                                        <div><span class="px-1.5 py-0.5 rounded text-[9px] font-bold {{ $tc }} {{ $bc }}">{{ $lr }}</span></div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>


{{-- ═══════════════════════════════════════════════
     STATS BAR
═══════════════════════════════════════════════ --}}
<div class="bg-slate-900 border-y border-slate-800">
    <div class="max-w-4xl mx-auto px-4 py-8 grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
        @foreach([['500+','Instituciones'],['25k+','Estudiantes'],['99%','Disponibilidad'],['24/7','Soporte']] as [$n,$d])
        <div>
            <div class="text-2xl font-black text-white">{{ $n }}</div>
            <div class="text-xs text-slate-500 mt-1">{{ $d }}</div>
        </div>
        @endforeach
    </div>
</div>


{{-- ═══════════════════════════════════════════════
     2. BENEFICIOS
═══════════════════════════════════════════════ --}}
<section class="py-20 bg-white" id="beneficios">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">

        {{-- Header --}}
        <div class="text-center mb-14">
            <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-blue-50 text-blue-700 text-xs font-bold uppercase tracking-wider border border-blue-100 mb-4">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd"/></svg>
                Beneficios
            </span>
            <h2 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight mb-4">Todo lo que necesita tu institución</h2>
            <p class="text-slate-500 text-base max-w-xl mx-auto leading-relaxed">Una plataforma integral que simplifica la gestión y mejora la comunicación entre todos los actores del proceso educativo.</p>
        </div>

        {{-- Grid 3 columnas --}}
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @php
            $benefits = [
                ['bg-blue-50','text-blue-600','M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z','border-blue-100','Gestión académica completa','Notas, asistencia, boletines PDF y análisis de rendimiento en tiempo real desde cualquier dispositivo.'],
                ['bg-violet-50','text-violet-600','M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z','border-violet-100','Registro por competencias','Sistema de evaluación basado en indicadores de logro y competencias. Compatible con currículo MINERD y estándares internacionales.'],
                ['bg-cyan-50','text-cyan-600','M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0H3','border-cyan-100','Aula virtual integrada','ZuraClass incluye tareas, quizzes, rúbricas y recursos. Todo el entorno de aprendizaje en tu propia plataforma.'],
                ['bg-amber-50','text-amber-600','M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z','border-amber-100','Portal para padres','Notas, asistencia y comunicados en tiempo real. Los representantes siempre informados desde su celular.'],
                ['bg-emerald-50','text-emerald-600','M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0','border-emerald-100','Notificaciones automáticas','Alertas de ausencia, bajo rendimiento y pagos enviadas automáticamente. Sin trabajo manual extra.'],
                ['bg-red-50','text-red-600','M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z','border-red-100','Público y privado','Adaptado para centros educativos públicos y privados. Configurable para el currículo de cada institución.'],
            ];
            @endphp

            @foreach($benefits as [$iconBg,$iconColor,$iconPath,$borderColor,$title,$desc])
            <div class="group bg-white rounded-2xl p-6 border border-slate-200 card-hover {{ $borderColor }}">
                <div class="w-12 h-12 rounded-xl {{ $iconBg }} flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6 {{ $iconColor }}" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}"/>
                    </svg>
                </div>
                <h3 class="text-base font-800 font-extrabold text-slate-900 mb-2">{{ $title }}</h3>
                <p class="text-sm text-slate-500 leading-relaxed">{{ $desc }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>


{{-- ═══════════════════════════════════════════════
     3. MÓDULOS
═══════════════════════════════════════════════ --}}
<section class="py-20 bg-slate-50" id="modulos">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">

        <div class="text-center mb-14">
            <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold uppercase tracking-wider border border-emerald-100 mb-4">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
                Módulos del sistema
            </span>
            <h2 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight mb-4">Un sistema para cada necesidad</h2>
            <p class="text-slate-500 text-base max-w-xl mx-auto leading-relaxed">Activa los módulos que necesitas. Cada institución es única y ZuraEdu se adapta.</p>
        </div>

        <div class="grid sm:grid-cols-2 gap-6">
            @php
            $modules = [
                ['from-blue-600','to-blue-800','M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z','Registro académico','Plan Free','Grilla de notas por período, registro por competencias e indicadores de logro, boletines PDF automáticos y análisis de rendimiento estudiantil.',['Notas por período I-IV','Competencias e indicadores','Boletines PDF automáticos','Estadísticas y rankings']],
                ['from-cyan-500','to-cyan-700','M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0H3','Aula virtual (ZuraClass)','Plan Pro','Tu propio Google Classroom. Tareas con entrega, quizzes automáticos, recursos por materia y seguimiento de progreso por estudiante.',['Tareas y entregas online','Quizzes automáticos','Recursos y materiales','Rúbricas de evaluación']],
                ['from-violet-600','to-violet-800','M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5','Horarios inteligentes','Plan Pro','Generación automática de horarios sin conflictos. Detecta choques entre docentes, aulas y grupos. Exporta en PDF con un clic.',['Auto-generación sin conflictos','Gestión de aulas','Suplencias y cambios','Exportación PDF']],
                ['from-amber-500','to-orange-600','M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z','Portal para padres','Plan Free','Los representantes consultan notas, asistencia, horario y comunicados desde su celular. Notificaciones en tiempo real ante cualquier evento.',['Notas y asistencia live','Comunicados del director','Horario actualizado','Verificación matrícula QR']],
            ];
            @endphp

            @foreach($modules as [$from,$to,$iconPath,$title,$plan,$desc,$features])
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden card-hover group">
                <div class="p-6">
                    <div class="flex items-start gap-4 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br {{ $from }} {{ $to }} flex items-center justify-center shrink-0 shadow-md">
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}"/>
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="text-base font-extrabold text-slate-900">{{ $title }}</h3>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $plan === 'Plan Free' ? 'bg-blue-50 text-blue-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $plan }}</span>
                            </div>
                            <p class="text-sm text-slate-500 leading-relaxed">{{ $desc }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-1.5">
                        @foreach($features as $f)
                        <div class="flex items-center gap-1.5 text-sm text-slate-600">
                            <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
                            {{ $f }}
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>


{{-- ═══════════════════════════════════════════════
     4. PREVIEW / DEMO
═══════════════════════════════════════════════ --}}
<section class="py-20 bg-white" id="demo">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">

        <div class="text-center mb-14">
            <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-violet-50 text-violet-700 text-xs font-bold uppercase tracking-wider border border-violet-100 mb-4">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0H3"/></svg>
                Vista previa interactiva
            </span>
            <h2 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight mb-4">Mira el sistema en acción</h2>
            <p class="text-slate-500 text-base max-w-xl mx-auto leading-relaxed">Interfaz moderna, clara e intuitiva. Sin curvas de aprendizaje.</p>
        </div>

        {{-- Tabs --}}
        <div class="flex gap-2 justify-center mb-8 flex-wrap">
            @foreach([['tab-notas','Tabla de notas','M9 12h3.75M9 15h3.75'],['tab-tareas','Tareas','M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],['tab-horario','Horario','M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5']] as [$id,$label,$path])
            <button onclick="switchTab('{{ $id }}')"
                    id="btn-{{ $id }}"
                    class="tab-btn flex items-center gap-2 px-5 py-2.5 rounded-full border text-sm font-semibold transition-all duration-200
                           {{ $id === 'tab-notas' ? 'bg-primary text-white border-primary shadow-md shadow-blue-200' : 'bg-white text-slate-500 border-slate-200 hover:border-blue-200 hover:text-blue-600' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/></svg>
                {{ $label }}
            </button>
            @endforeach
        </div>

        {{-- Mock container --}}
        <div class="bg-slate-900 rounded-2xl overflow-hidden shadow-2xl shadow-slate-900/30">
            {{-- Browser bar --}}
            <div class="bg-slate-950 px-4 py-3 flex items-center gap-3 border-b border-slate-800">
                <div class="flex gap-1.5">
                    <div class="w-3 h-3 rounded-full bg-red-500/80"></div>
                    <div class="w-3 h-3 rounded-full bg-yellow-500/80"></div>
                    <div class="w-3 h-3 rounded-full bg-green-500/80"></div>
                </div>
                <div class="flex-1 max-w-xs mx-auto bg-slate-800 rounded-md px-3 py-1 text-xs text-slate-400 text-center">
                    🔒 miescuela.zuraedu.com
                </div>
            </div>

            {{-- Tab: Notas --}}
            <div id="tab-notas" class="tab-content active p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-white">Grilla de Calificaciones — 1ro A · Período I</h3>
                    <div class="flex gap-2">
                        <span class="px-3 py-1 bg-blue-600/25 text-blue-300 text-xs rounded-lg font-semibold">PDF</span>
                        <span class="px-3 py-1 bg-emerald-600/25 text-emerald-300 text-xs rounded-lg font-semibold">Excel</span>
                    </div>
                </div>
                <div class="overflow-x-auto rounded-xl border border-slate-700/50">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-slate-800 text-slate-400 font-bold">
                                <th class="px-4 py-2.5 text-left">Estudiante</th>
                                @foreach(['Matemática','Español','Inglés','Física','Promedio'] as $materia) <th class="px-3 py-2.5">{{ $materia }}</th>@endforeach
                            </tr>
                        </thead>
                        <tbody>
                        @php
                        $rows=[['María García',[92,88,95,90,91]],['Juan Pérez',[78,82,75,70,76]],['Luis Soto',[55,60,52,48,54]],['Ana Torres',[85,90,88,82,86]],['Carlos Ruiz',[70,65,72,68,69]]];
                        $nc=fn($n)=>$n>=90?'text-emerald-400 bg-emerald-400/10':($n>=75?'text-blue-400 bg-blue-400/10':($n>=60?'text-yellow-400 bg-yellow-400/10':'text-red-400 bg-red-400/10'));
                        @endphp
                        @foreach($rows as [$name,$scores])
                        <tr class="border-t border-slate-700/40 hover:bg-slate-800/40 transition-colors">
                            <td class="px-4 py-2.5 text-slate-200 font-medium">{{ $name }}</td>
                            @foreach($scores as $i => $score)
                            <td class="px-3 py-2.5 text-center">
                                <span class="inline-block px-2 py-0.5 rounded text-xs font-bold {{ $nc($score) }} {{ $i === count($scores)-1 ? 'ring-1 ring-current/30' : '' }}">{{ $score }}</span>
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Tab: Tareas --}}
            <div id="tab-tareas" class="tab-content p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-white">ZuraClass — Tareas activas</h3>
                    <span class="px-3 py-1 bg-cyan-600/25 text-cyan-300 text-xs rounded-lg font-semibold">Nueva tarea</span>
                </div>
                <div class="space-y-3">
                    @php
                    $tasks=[['Ejercicios de Fracciones','Matemática · 1ro A','Entrega: 20 may','5 pendientes','text-yellow-400 bg-yellow-400/10'],['Comprensión lectora','Español · 2do B','Entrega: 22 may','Todos entregaron','text-emerald-400 bg-emerald-400/10'],['Quiz: Revolución Industrial','Historia · 3ro C','Mañana 8:00 AM','Quiz activo','text-blue-400 bg-blue-400/10'],['Reporte de Laboratorio','Biología · 2do A','Entrega: 25 may','8 pendientes','text-red-400 bg-red-400/10']];
                    @endphp
                    @foreach($tasks as [$title,$sub,$date,$status,$statusClass])
                    <div class="flex items-center gap-4 bg-slate-800/50 rounded-xl px-4 py-3 border border-slate-700/40 hover:border-slate-600/60 transition-colors">
                        <div class="w-9 h-9 rounded-lg bg-slate-700 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-semibold text-slate-200 truncate">{{ $title }}</div>
                            <div class="text-xs text-slate-500">{{ $sub }} · {{ $date }}</div>
                        </div>
                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold {{ $statusClass }} shrink-0">{{ $status }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Tab: Horario --}}
            <div id="tab-horario" class="tab-content p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-white">Horario Semanal — 1ro A</h3>
                    <div class="flex gap-2">
                        <span class="flex items-center gap-1 px-3 py-1 bg-emerald-600/25 text-emerald-300 text-xs rounded-lg font-semibold">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>Publicado
                        </span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs border-collapse min-w-[500px]">
                        <thead>
                            <tr class="bg-slate-800">
                                <th class="py-2 px-3 text-left text-slate-400 font-bold w-20">Hora</th>
                                @foreach(['Lun','Mar','Mié','Jue','Vie'] as $d)
                                <th class="py-2 px-2 text-slate-400 font-bold text-center">{{ $d }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                        @php
                        $mats=[['Matemática','bg-blue-600/25 text-blue-300 border-blue-500/30'],['Español','bg-emerald-600/25 text-emerald-300 border-emerald-500/30'],['Física','bg-violet-600/25 text-violet-300 border-violet-500/30'],['Historia','bg-amber-600/25 text-amber-300 border-amber-500/30'],['Inglés','bg-pink-600/25 text-pink-300 border-pink-500/30'],['Ed.Física','bg-red-600/25 text-red-300 border-red-500/30']];
                        $sched=[['07:00',[0,2,4,1,3]],['08:00',[3,0,1,4,2]],['09:00',[1,3,null,2,0]],['10:00',null,'recreo'],['10:30',[2,4,3,0,5]],['11:30',[4,1,2,5,3]]];
                        @endphp
                        @foreach($sched as $row)
                        @if(isset($row[2])&&$row[2]==='recreo')
                        <tr class="border-t border-slate-700/40">
                            <td colspan="6" class="py-2 text-center text-slate-500 text-xs bg-slate-800/20 italic">☕ Recreo — 10:00 a 10:30</td>
                        </tr>
                        @else
                        <tr class="border-t border-slate-700/40 hover:bg-slate-800/30 transition-colors">
                            <td class="py-2 px-3 text-slate-500 font-medium">{{ $row[0] }}</td>
                            @foreach($row[1] as $idx)
                            <td class="py-2 px-1.5 text-center">
                                @if($idx !== null)
                                <div class="px-2 py-1 rounded-lg text-[10px] font-bold border {{ $mats[$idx][1] }} whitespace-nowrap">{{ $mats[$idx][0] }}</div>
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @endif
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- CTA debajo del preview --}}
        <div class="text-center mt-8">
            <button onclick="openDemoModal()" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-bold text-white bg-primary hover:bg-primary-dark shadow-lg shadow-blue-200 btn-primary-hover">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z"/></svg>
                Explorar demo completo
            </button>
        </div>
    </div>
</section>


{{-- ═══════════════════════════════════════════════
     5. PLANES
═══════════════════════════════════════════════ --}}
<section class="py-20 bg-slate-50" id="planes">
    <div class="max-w-5xl mx-auto px-4 sm:px-6">

        <div class="text-center mb-14">
            <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-amber-50 text-amber-700 text-xs font-bold uppercase tracking-wider border border-amber-100 mb-4">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/></svg>
                Planes y precios
            </span>
            <h2 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight mb-4">Empieza gratis, escala cuando quieras</h2>
            <p class="text-slate-500 text-base max-w-xl mx-auto">Sin contratos largos. Cancela o cambia de plan en cualquier momento.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-6 items-start">

            {{-- Gratis --}}
            <div class="bg-white rounded-2xl border-2 border-slate-200 p-7 card-hover">
                <div class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-3">Gratuito</div>
                <div class="flex items-end gap-1 mb-1">
                    <span class="text-4xl font-black text-slate-900">$0</span>
                    <span class="text-slate-400 text-sm mb-1.5">/mes</span>
                </div>
                <p class="text-xs text-slate-400 mb-6">Para empezar sin compromiso</p>
                <ul class="space-y-2.5 mb-7 text-sm">
                    @foreach(['Hasta 100 estudiantes','Hasta 5 docentes','Calificaciones y notas','Control de asistencia','Boletines PDF básicos','Portal padres y estudiantes','Comunicados y calendario'] as $f)
                    <li class="flex items-center gap-2 text-slate-600">
                        <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
                        {{ $f }}
                    </li>
                    @endforeach
                    @foreach(['Horarios automáticos','ZuraClass virtual'] as $f)
                    <li class="flex items-center gap-2 text-slate-300">
                        <svg class="w-4 h-4 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        {{ $f }}
                    </li>
                    @endforeach
                </ul>
                <a href="{{ route('onboarding') }}?plan=free"
                   class="block w-full text-center py-3 rounded-xl font-bold text-sm text-primary border-2 border-blue-200 hover:bg-blue-50 transition-all">
                    Empezar gratis
                </a>
            </div>

            {{-- Pro (destacado) --}}
            <div class="bg-dark rounded-2xl border-2 border-blue-600 p-7 shadow-xl shadow-blue-900/30 relative -mt-2 mb-2 card-hover">
                <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                    <span class="bg-gradient-to-r from-blue-600 to-blue-500 text-white text-xs font-black px-5 py-1.5 rounded-full shadow-lg shadow-blue-800/50 whitespace-nowrap">
                        ⭐ Más popular
                    </span>
                </div>
                <div class="text-xs font-bold uppercase tracking-widest text-blue-400 mb-3 mt-2">Pro</div>
                <div class="flex items-end gap-1 mb-1">
                    <span class="text-4xl font-black text-white">$29</span>
                    <span class="text-slate-400 text-sm mb-1.5">/mes</span>
                </div>
                <p class="text-xs text-slate-500 mb-6">30 días gratis · Sin tarjeta</p>
                <ul class="space-y-2.5 mb-7 text-sm">
                    @foreach(['Hasta 500 estudiantes','Hasta 30 docentes','Todo el plan Gratuito','Horarios automáticos','ZuraClass — Aula virtual','Competencias e indicadores','Gamificación estudiantil','Tutorías y seguimiento','Soporte prioritario'] as $f)
                    <li class="flex items-center gap-2 text-slate-300">
                        <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
                        {{ $f }}
                    </li>
                    @endforeach
                </ul>
                <a href="{{ route('onboarding') }}?plan=pro"
                   class="block w-full text-center py-3 rounded-xl font-bold text-sm text-white bg-primary hover:bg-primary-dark shadow-lg shadow-blue-900/50 transition-all btn-primary-hover">
                    Comenzar prueba Pro
                </a>
            </div>

            {{-- Básico --}}
            <div class="bg-white rounded-2xl border-2 border-slate-200 p-7 card-hover">
                <div class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-3">Premium</div>
                <div class="flex items-end gap-1 mb-1">
                    <span class="text-4xl font-black text-slate-900">$79</span>
                    <span class="text-slate-400 text-sm mb-1.5">/mes</span>
                </div>
                <p class="text-xs text-slate-400 mb-6">Para instituciones grandes</p>
                <ul class="space-y-2.5 mb-7 text-sm">
                    @foreach(['Estudiantes ilimitados','Docentes ilimitados','Todo el plan Pro','Pagos y facturación','WhatsApp automático','Nómina del personal','Biblioteca e inventario','Soporte 24/7 dedicado','Acceso a API'] as $f)
                    <li class="flex items-center gap-2 text-slate-600">
                        <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
                        {{ $f }}
                    </li>
                    @endforeach
                </ul>
                <a href="{{ route('onboarding') }}?plan=premium"
                   class="block w-full text-center py-3 rounded-xl font-bold text-sm text-white bg-slate-900 hover:bg-slate-800 transition-all">
                    Activar Premium
                </a>
            </div>

        </div>

        <p class="text-center text-xs text-slate-400 mt-8">
            <svg class="w-3.5 h-3.5 inline mr-1 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
            Todos los planes incluyen 30 días de prueba gratis · Sin tarjeta · Cancela cuando quieras
        </p>
    </div>
</section>


{{-- ═══════════════════════════════════════════════
     6. CTA FINAL
═══════════════════════════════════════════════ --}}
<section class="py-24 bg-dark relative overflow-hidden">
    {{-- Decoración --}}
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-blue-600/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 right-1/4 w-64 h-64 bg-emerald-500/8 rounded-full blur-3xl"></div>
    </div>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 text-center relative z-10">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-emerald-600/15 border border-emerald-500/25 text-emerald-300 text-xs font-semibold mb-6">
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd"/></svg>
            Únete a 500+ instituciones que ya confían en ZuraEdu
        </div>

        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-white tracking-tight leading-tight mb-5">
            Crea tu escuela en<br>
            <span class="gradient-text">menos de 2 minutos</span>
        </h2>

        <p class="text-slate-400 text-base sm:text-lg leading-relaxed mb-10 max-w-xl mx-auto">
            Sistema listo al instante, sin instalaciones, sin servidores. Tú te enfocas en educar, nosotros en la tecnología.
        </p>

        <div class="flex flex-col sm:flex-row gap-4 justify-center mb-8">
            <a href="{{ route('onboarding') }}"
               class="inline-flex items-center justify-center gap-2.5 px-8 py-4 rounded-xl text-base font-black text-white bg-success hover:bg-success-dark shadow-2xl shadow-green-900/50 btn-success-hover">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/></svg>
                Crear mi escuela ahora
            </a>
            <a href="{{ route('demo.auto') }}"
               class="inline-flex items-center justify-center gap-2.5 px-8 py-4 rounded-xl text-base font-bold text-white border-2 border-white/20 hover:border-white/40 hover:bg-white/10 transition-all duration-200">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z"/></svg>
                Ver demo primero
            </a>
        </div>

        <div class="flex flex-wrap items-center justify-center gap-5">
            @foreach(['Sin tarjeta de crédito','30 días gratis','Cancela cuando quieras','Soporte incluido'] as $t)
            <div class="flex items-center gap-1.5 text-slate-500 text-xs">
                <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
                {{ $t }}
            </div>
            @endforeach
        </div>
    </div>
</section>


{{-- ═══════════════════════════════════════════════
     7. FOOTER
═══════════════════════════════════════════════ --}}
<footer class="bg-slate-950 pt-14 pb-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-10 pb-10 border-b border-slate-800">

            {{-- Brand --}}
            <div class="sm:col-span-2 lg:col-span-1">
                <a href="{{ route('landing') }}" class="flex items-center gap-2.5 mb-4">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-800 to-blue-500 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/></svg>
                    </div>
                    <span class="text-lg font-black text-white">ZuraEdu</span>
                </a>
                <p class="text-sm text-slate-500 leading-relaxed mb-5">Plataforma SaaS educativa todo-en-uno para instituciones modernas.</p>
            </div>

            {{-- Producto --}}
            <div>
                <h4 class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-4">Producto</h4>
                <ul class="space-y-2.5">
                    @foreach([['#beneficios','Beneficios'],['#modulos','Módulos'],['#planes','Planes y precios'],['#demo','Vista previa']] as [$href,$label])
                    <li><a href="{{ $href }}" class="text-sm text-slate-400 hover:text-white transition-colors">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>

            {{-- Demo --}}
            <div>
                <h4 class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-4">Explorar demo</h4>
                <ul class="space-y-2.5">
                    <li><a href="{{ route('demo.auto') }}" class="text-sm text-slate-400 hover:text-white transition-colors">Demo Administrador</a></li>
                    @foreach([['docente','Docente'],['estudiante','Estudiante'],['padre','Representante']] as [$rol,$label])
                    <li><a href="{{ route('demo.login', $rol) }}" class="text-sm text-slate-400 hover:text-white transition-colors">Demo {{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>

            {{-- Acceso --}}
            <div>
                <h4 class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-4">Acceso</h4>
                <ul class="space-y-2.5">
                    <li><a href="{{ route('onboarding') }}" class="text-sm text-emerald-400 hover:text-emerald-300 font-semibold transition-colors">Crear escuela gratis</a></li>
                    <li><a href="{{ route('login') }}" class="text-sm text-slate-400 hover:text-white transition-colors">Iniciar sesión</a></li>
                    <li><a href="{{ route('verificar-matricula') }}" class="text-sm text-slate-400 hover:text-white transition-colors">Verificar matrícula</a></li>
                    <li><a href="{{ route('help.registro') }}" class="text-sm text-slate-400 hover:text-white transition-colors">Ayuda</a></li>
                </ul>
            </div>

        </div>

        {{-- Bottom --}}
        <div class="pt-6 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p class="text-xs text-slate-600">© {{ date('Y') }} ZuraEdu. Todos los derechos reservados.</p>
            <div class="flex gap-5">
                <a href="#" class="text-xs text-slate-600 hover:text-slate-400 transition-colors">Términos de uso</a>
                <a href="#" class="text-xs text-slate-600 hover:text-slate-400 transition-colors">Privacidad</a>
            </div>
        </div>
    </div>
</footer>


{{-- ═══════════════════════════════════════════════
     MODAL DEMO
═══════════════════════════════════════════════ --}}
<div id="demoModal"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
     style="display:none!important;"
     onclick="closeDemoOnBg(event)">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-8">
        <div class="text-center mb-6">
            <div class="text-3xl mb-3">🎮</div>
            <h3 class="text-xl font-black text-slate-900 mb-1">Explorar el demo</h3>
            <p class="text-sm text-slate-500">Docente, Estudiante o Representante — sin registro.</p>
        </div>
        <div class="grid grid-cols-3 gap-3 mb-4">
            @foreach([['docente','👨‍🏫','Docente','Notas y asistencia','bg-violet-50 text-violet-700 hover:bg-violet-100 border-violet-100'],['estudiante','🎓','Estudiante','Mi portal','bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border-emerald-100'],['padre','👨‍👩‍👧','Representante','Info de mi hijo','bg-amber-50 text-amber-700 hover:bg-amber-100 border-amber-100']] as [$rol,$ico,$name,$sub,$cls])
            <a href="{{ route('demo.login', $rol) }}"
               class="flex flex-col items-center gap-2 p-4 rounded-2xl border-2 transition-all {{ $cls }}">
                <span class="text-2xl">{{ $ico }}</span>
                <span class="text-sm font-bold">{{ $name }}</span>
                <span class="text-xs opacity-60">{{ $sub }}</span>
            </a>
            @endforeach
        </div>
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3 mb-4 text-center">
            <p class="text-xs text-slate-500 mb-2">¿Quieres ver el panel completo de administrador?</p>
            <a href="{{ route('onboarding') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-primary hover:opacity-90 transition-all">
                🏫 Registra tu institución gratis
            </a>
        </div>
        <button onclick="closeDemoModal()"
                class="w-full py-3 rounded-xl text-sm font-semibold text-slate-500 border border-slate-200 hover:bg-slate-50 transition-all">
            Cerrar
        </button>
    </div>
</div>


{{-- ═══════════════════════════════════════════════
     SCRIPTS
═══════════════════════════════════════════════ --}}
<script>
// ── Navbar scroll shadow ──────────────────────────────
window.addEventListener('scroll', () => {
    document.getElementById('navbar').style.boxShadow =
        window.scrollY > 10 ? '0 4px 24px rgba(0,0,0,.08)' : '';
}, { passive: true });

// ── Demo modal ────────────────────────────────────────
function openDemoModal() {
    const m = document.getElementById('demoModal');
    m.style.removeProperty('display');
    m.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeDemoModal() {
    document.getElementById('demoModal').style.setProperty('display', 'none', 'important');
    document.body.style.overflow = '';
}
function closeDemoOnBg(e) {
    if (e.target === document.getElementById('demoModal')) closeDemoModal();
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDemoModal(); });

// ── Preview tabs ──────────────────────────────────────
function switchTab(id) {
    // Ocultar todos los contenidos
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    // Resetear todos los botones
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.className = btn.className
            .replace('bg-primary text-white border-primary shadow-md shadow-blue-200', '')
            .trim();
        if (!btn.className.includes('bg-')) {
            btn.className += ' bg-white text-slate-500 border-slate-200 hover:border-blue-200 hover:text-blue-600';
        }
    });
    // Activar el tab seleccionado
    document.getElementById(id).classList.add('active');
    const activeBtn = document.getElementById('btn-' + id);
    activeBtn.className = activeBtn.className
        .replace('bg-white text-slate-500 border-slate-200 hover:border-blue-200 hover:text-blue-600', '')
        .trim();
    activeBtn.className += ' bg-primary text-white border-primary shadow-md shadow-blue-200';
}
</script>

</body>
</html>
