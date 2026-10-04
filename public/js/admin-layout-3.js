(function () {
    'use strict';

    const LANG_ES = {
        decimal: ',', thousands: '.',
        emptyTable:       'No hay datos disponibles',
        info:             'Mostrando _START_–_END_ de _TOTAL_ registros',
        infoEmpty:        'Sin registros',
        infoFiltered:     '(filtrado de _MAX_ total)',
        loadingRecords:   'Cargando…',
        processing:       'Procesando…',
        search:           '',
        searchPlaceholder:'Buscar en tabla…',
        zeroRecords:      'Sin resultados',
        paginate: { first: '«', previous: '‹', next: '›', last: '»' }
    };

    // dom: f = filter, t = table, i = info (sin paginación visual — el scroll es la navegación)
    const DT_DOM = '<"dt-top d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2"f>t<"dt-bottom d-flex align-items-center gap-2 mt-1"i>';

    function dtInit(el) {
        if (typeof DataTable === 'undefined') return;
        if (DataTable.isDataTable(el)) return;

        const tbody = el.querySelector('tbody');
        if (!tbody) return;
        const rowCount = tbody.querySelectorAll('tr').length;
        if (rowCount < 5) return;

        // Altura dinámica según número de filas
        const scrollH = rowCount > 40 ? '62vh' : rowCount > 20 ? '48vh' : '340px';

        new DataTable(el, {
            language:      LANG_ES,
            dom:           DT_DOM,
            deferRender:   true,   // renderiza filas solo al hacerse visibles (lazy DOM)
            scrollY:       scrollH,
            scrollX:       true,
            scrollCollapse:true,
            scroller:      true,   // virtual scroll — carga filas a demanda al desplazarse
            pageLength:    50,
            orderCellsTop: true,
            initComplete: function () {
                const wrap = el.closest('.table-responsive');
                if (wrap) wrap.style.overflow = 'hidden';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('table.table:not([data-no-dt])').forEach(dtInit);
    });

    // API pública para inicialización manual desde vistas
    window.SGE = window.SGE || {};
    window.SGE.dtInit = dtInit;
})();
