/* Menú lateral de los portales (docente, estudiante, padre): cada .prt-sidebar-section pasa a ser un grupo plegable.
 * - Abre el grupo que contiene el enlace activo y el primero (el de uso diario); el resto empieza cerrado.
 * - Recuerda lo que la persona abre o cierra (por nombre de grupo, no por posición: el menú cambia según la clase y el hijo).
 * - Sin JavaScript el menú se ve completo, como antes. */
(function () {
    'use strict';
    var aside = document.querySelector('aside.prt-sidebar');
    if (!aside) return;

    var guardar = function (clave, valor) { try { localStorage.setItem(clave, valor); } catch (e) {} };
    var leer = function (clave) { try { return localStorage.getItem(clave); } catch (e) { return null; } };

    var secciones = Array.prototype.slice.call(aside.querySelectorAll(':scope > .prt-sidebar-section'));
    secciones.forEach(function (titulo, indice) {
        // Todo lo que sigue al título, hasta el siguiente título, es el contenido del grupo
        var grupo = document.createElement('div');
        grupo.className = 'prt-sidebar-group';
        var siguiente = titulo.nextSibling;
        while (siguiente && !(siguiente.nodeType === 1 && siguiente.classList.contains('prt-sidebar-section'))) {
            var actual = siguiente;
            siguiente = siguiente.nextSibling;
            grupo.appendChild(actual);
        }
        if (!grupo.querySelector('a, button')) { return; }   // título sin enlaces: no se pliega
        titulo.parentNode.insertBefore(grupo, titulo.nextSibling);

        var clave = 'prtSidebar:' + titulo.textContent.replace(/\s+/g, ' ').trim();
        var activo = !!grupo.querySelector('a.active, a[aria-current="page"]');
        var guardado = leer(clave);
        // Los grupos cortos (cuenta, cerrar sesión) y el primero se ven abiertos; los largos, solo si la persona los abrió o tienen el enlace activo
        var corto = grupo.querySelectorAll('a, button').length <= 3;
        var abierto = activo || (guardado !== null ? guardado === '1' : (indice === 0 || corto));

        titulo.classList.add('prt-sidebar-toggle');
        titulo.setAttribute('role', 'button');
        titulo.setAttribute('tabindex', '0');
        var flecha = document.createElement('i');
        flecha.className = 'bi bi-chevron-down prt-sidebar-chevron';
        titulo.appendChild(flecha);

        var aplicar = function (abrir) {
            grupo.hidden = !abrir;
            titulo.setAttribute('aria-expanded', abrir ? 'true' : 'false');
            flecha.style.transform = abrir ? 'rotate(180deg)' : '';
        };
        aplicar(abierto);

        var alternar = function () {
            var abrir = grupo.hidden;
            aplicar(abrir);
            guardar(clave, abrir ? '1' : '0');
        };
        titulo.addEventListener('click', alternar);
        titulo.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); alternar(); }
        });
    });
})();
