// Guiones de los videos de ZuraEdu. Cada escena recibe `v` (ver lib.cjs) y recorre pantallas reales del sistema con textos en pantalla.
// Correos de las cuentas de demostración: se pueden cambiar con variables de entorno (la contraseña sale de VIDEO_CLAVE / VIDEO_CLAVE_FILE).
const CUENTAS = {
    admin: process.env.VIDEO_ADMIN || 'rol-administrador@demo.test',
    docente: process.env.VIDEO_DOCENTE || 'rol-docente@demo.test',
    padre: process.env.VIDEO_PADRE || 'representantes.demo@sge.test',
};

const cierre = (v, texto = 'ZuraEdu · gestión escolar completa') =>
    v.tarjeta({ titulo: texto, subtitulo: 'Pruébalo gratis y crea tu escuela en minutos', tono: 'azul' }, 2600);

const titulo = (v, nombre, sub, chips = []) => v.tarjeta({ titulo: nombre, subtitulo: sub, lineas: chips, tono: 'azul' }, 3200);

module.exports = {
    // ─────────────────────────────── Página pública: explicación rápida (~90 s) ───────────────────────────────
    'zuraedu-presentacion': {
        opciones: {},
        async escena(v) {
            await v.tarjeta({ titulo: 'ZuraEdu', subtitulo: 'Gestión escolar y aula virtual en una sola plataforma', tono: 'azul' }, 3600);
            await v.ir('/', 'Una plataforma para todo tu centro educativo', 3200);
            await v.bajar(560, 1800);
            await v.cap('Notas por competencias, asistencia, pagos, horarios y comunicación con las familias', 4200);
            await v.bajar(650, 2000);
            await v.cap('Cada persona ve solo lo suyo: dirección, docentes, familias y estudiantes', 3800);
            await v.bajar(700, 2000);
            await v.cap('Empiezas en tres pasos, sin instalar nada', 3000);
            await v.bajar(700, 2000);
            await v.sinTexto();

            await v.login(CUENTAS.admin);
            await v.ir('/admin/dashboard', 'Dirección: el estado del colegio de un vistazo', 4200);
            await v.bajar(380, 1600);
            await v.ir('/admin/estudiantes', 'Estudiantes, matrículas y expedientes en un solo lugar', 3600);
            await v.ir('/admin/asistencia', 'Asistencia diaria con avisos automáticos a las familias', 3600);
            await v.ir('/admin/calificaciones', 'Notas por período y boletines listos para imprimir', 3600);
            await v.ir('/admin/pagos/dashboard', 'Pagos, becas y deudores con su estado de cuenta', 3600);
            await v.ir('/admin/cafeteria', 'Cafetería con saldo recargable para cada estudiante', 3400);

            await v.login(CUENTAS.docente);
            await v.ir('/portal/docente', 'Los docentes pasan lista, califican y planifican desde su portal', 4200);
            await v.tarjeta({ titulo: 'Y las familias, siempre informadas', subtitulo: 'Notas, asistencia, pagos y avisos desde el celular', lineas: ['Boletines', 'Asistencia', 'Pagos', 'Comunicados'], tono: 'oscuro' }, 4200);
            await cierre(v, 'Crea tu escuela gratis');
        },
    },

    // ─────────────────────────────── Publicitario (~35 s) ───────────────────────────────
    'zuraedu-promo': {
        opciones: {},
        async escena(v) {
            await v.tarjeta({ titulo: '¿Notas en Excel, pagos en papel y avisos por WhatsApp?', tono: 'oscuro' }, 3400);
            await v.tarjeta({ titulo: 'Todo tu colegio en un solo lugar', subtitulo: 'ZuraEdu', tono: 'azul' }, 3000);
            await v.login(CUENTAS.admin);
            await v.ir('/admin/dashboard', 'Indicadores en tiempo real', 3200);
            await v.ir('/admin/calificaciones', 'Notas y boletines en minutos', 3000);
            await v.ir('/admin/asistencia', 'Asistencia con aviso a las familias', 3000);
            await v.ir('/admin/pagos/dashboard', 'Cobros y becas bajo control', 3000);
            await v.ir('/admin/cafeteria', 'Cafetería sin efectivo', 2800);
            await v.tarjeta({ titulo: 'Más tiempo para educar', subtitulo: 'Menos papeleo, familias contentas', lineas: ['Dirección', 'Docentes', 'Familias', 'Estudiantes'], tono: 'claro' }, 4000);
            await cierre(v, 'ZuraEdu. Pruébalo hoy');
        },
    },

    // ─────────────────────────────── Un video por módulo ───────────────────────────────
    'modulo-inscripcion-matricula': {
        opciones: {},
        async escena(v) {
            await titulo(v, 'Inscripción y matrícula', 'De la pre-matrícula en línea al estudiante matriculado', ['Pre-matrícula', 'Inscripción', 'Matrícula']);
            await v.ir('/inscripcion', 'Las familias se pre-matriculan en línea, desde el celular', 4200);
            await v.bajar(520, 1800);
            await v.sinTexto();
            await v.login(CUENTAS.admin);
            await v.ir('/admin/pre-matriculas', 'Dirección recibe las solicitudes con un código de seguimiento', 4400);
            await v.ir('/admin/inscripciones', 'Se convierten en inscripciones con un clic', 3800);
            await v.ir('/admin/matriculas', 'La matrícula asigna el grupo y valida el cupo disponible', 4400);
            await v.bajar(420, 1500);
            await v.ir('/admin/estudiantes', 'Cada estudiante tiene su expediente completo', 3800);
            await cierre(v);
        },
    },

    'modulo-asistencia': {
        opciones: {},
        async escena(v) {
            await titulo(v, 'Asistencia', 'Pasar lista en segundos y avisar a las familias', ['Lista rápida', 'Reportes', 'Alertas']);
            await v.login(CUENTAS.docente);
            await v.ir('/portal/docente', 'El docente entra a su portal', 3000);
            await v.ir('/portal/docente/asistencia-rapida', 'Asistencia rápida: todos sus grupos en una pantalla', 4800);
            await v.bajar(300, 1400);
            const href = await v.primerEnlace('a[href*="/portal/docente/asignacion/"][href*="/asistencia"]');
            if (href) await v.ir(href, 'Presente, ausente, tarde o excusa con un toque', 4600);
            await v.login(CUENTAS.admin);
            await v.ir('/admin/asistencia', 'Dirección ve la asistencia de todos los grupos', 4200);
            await v.ir('/admin/alertas', 'Alertas de inasistencias para actuar a tiempo', 3800);
            await cierre(v);
        },
    },

    'modulo-notas-boletines': {
        opciones: {},
        async escena(v) {
            await titulo(v, 'Notas y boletines', 'Calificaciones por competencias hasta el boletín final', ['Registro de notas', 'Resumen', 'Boletines']);
            await v.login(CUENTAS.admin);
            await v.ir('/admin/calificaciones', 'Registro de notas por asignatura y período', 4400);
            await v.ir('/admin/calificaciones/resumen', 'Resumen de notas de todo el grupo', 4000);
            await v.ir('/admin/calificaciones/ranking', 'Ranking académico y cuadro de honor', 3800);
            await v.ir('/admin/boletines', 'Boletines listos para imprimir o enviar a las familias', 4400);
            await v.bajar(380, 1500);
            await v.ir('/admin/rendimiento', 'Rendimiento académico con semáforo por grupo', 4200);
            await cierre(v);
        },
    },

    'modulo-pagos': {
        opciones: {},
        async escena(v) {
            await titulo(v, 'Pagos y colegiaturas', 'Cobros, becas y estado de cuenta sin papeles', ['Cuotas', 'Deudores', 'Becas']);
            await v.login(CUENTAS.admin);
            await v.ir('/admin/pagos/dashboard', 'Panel financiero: lo cobrado y lo pendiente', 4600);
            await v.bajar(420, 1600);
            await v.ir('/admin/pagos', 'Cada pago queda registrado con su recibo', 4000);
            await v.ir('/admin/pagos/deudores', 'Deudores con recordatorio automático', 4000);
            await v.ir('/admin/becas/dashboard', 'Becas y descuentos aplicados a las cuotas', 4000);
            await cierre(v);
        },
    },

    'modulo-cafeteria': {
        opciones: {},
        async escena(v) {
            await titulo(v, 'Cafetería', 'Saldo recargable y ventas rápidas por estudiante', ['Recargas', 'Ventas', 'Saldo']);
            await v.login(CUENTAS.admin);
            await v.ir('/admin/cafeteria', 'Panel con las ventas y recargas del día', 4400);
            await v.intentar(async () => { await v.clic('button:has-text("Recarga"), a:has-text("Recarga")', 1800); await v.cap('Se busca al estudiante y se recarga su saldo', 3600); await v.page.keyboard.press('Escape'); await v.pausa(600); });
            await v.ir('/admin/cafeteria/ventas', 'Las ventas descuentan del saldo del estudiante', 4400);
            await v.bajar(380, 1400);
            await cierre(v);
        },
    },

    'modulo-comunicados': {
        opciones: {},
        async escena(v) {
            await titulo(v, 'Comunicados y mensajes', 'Toda la comunidad educativa informada', ['Comunicados', 'Mensajes', 'Encuestas']);
            await v.login(CUENTAS.admin);
            await v.ir('/admin/comunicados/dashboard', 'Comunicados para familias, docentes o todo el colegio', 4400);
            await v.ir('/admin/comunicaciones', 'Mensajes internos entre el personal', 3800);
            await v.ir('/admin/avisos-emergencia', 'Avisos de emergencia de alta prioridad', 3800);
            await v.ir('/admin/encuestas/dashboard', 'Encuestas para conocer la opinión de las familias', 4000);
            await v.ir('/admin/calendario', 'Calendario académico compartido', 3800);
            await cierre(v);
        },
    },

    'modulo-portal-docente': {
        opciones: {},
        async escena(v) {
            await titulo(v, 'Portal del docente', 'Todo lo de la clase en un solo lugar', ['Asistencia', 'Notas', 'Planificación', 'Classroom']);
            await v.login(CUENTAS.docente);
            await v.ir('/portal/docente', 'Inicio: sus clases, su horario y lo pendiente', 4600);
            await v.bajar(420, 1500);
            const href = await v.primerEnlace('a[href*="/portal/docente/asignacion/"][href*="/calificaciones"]');
            if (href) {
                await v.ir(href, 'Califica por período y competencia', 4400);
                await v.bajar(380, 1400);
                await v.ir(href.replace('/calificaciones', '/planes-clase'), 'Planes de clase y planificación anual', 4000);
                await v.ir(href.replace('/calificaciones', '/tareas'), 'Tareas y agenda con recordatorios para los estudiantes', 4000);
            }
            await v.ir('/portal/docente/classroom', 'Aula virtual para compartir materiales y recibir entregas', 4000);
            await cierre(v);
        },
    },

    'modulo-portal-familias': {
        opciones: { ancho: 390, alto: 844, movil: true },
        async escena(v) {
            await v.tarjeta({ titulo: 'Portal de familias', subtitulo: 'Desde el celular', lineas: ['Notas', 'Asistencia', 'Pagos'], tono: 'azul' }, 3200);
            await v.login(CUENTAS.padre);
            await v.ir('/portal/padre', 'Tus hijos y su información en un vistazo', 4200);
            const hijo = await v.primerEnlace('a[href*="/portal/padre/hijo/"]');
            if (hijo) {
                await v.ir(hijo, 'Resumen del hijo: notas, asistencia y alertas', 4200);
                await v.bajar(420, 1500);
                await v.ir(hijo.replace(/\/$/, '') + '/boletin', 'Boletín de calificaciones', 3800);
                await v.ir(hijo.replace(/\/$/, '') + '/asistencia', 'Asistencia al día', 3600);
                await v.ir(hijo.replace(/\/$/, '') + '/estado-cuenta', 'Estado de cuenta y pagos', 3800);
            }
            await v.tarjeta({ titulo: 'ZuraEdu', subtitulo: 'Siempre informado', tono: 'azul' }, 2400);
        },
    },
};
