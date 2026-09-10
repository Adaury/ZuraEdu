import Quill from 'quill';
import 'quill/dist/quill.snow.css';

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('contenido-editor');
    const hidden = document.getElementById('contenido-input');
    if (!container || !hidden) return;

    const quill = new Quill(container, {
        theme: 'snow',
        placeholder: 'Contenido completo de la publicación…',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ header: [2, 3, false] }],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['link'],
                ['clean'],
            ],
        },
    });

    // El servidor sanea el HTML permitido en SanitizeInput (allowedRichFields)
    // — Quill solo produce el marcado, la validación de seguridad real ocurre
    // en el backend, igual que con el resto de campos "cuerpo"/"contenido".
    if (hidden.value.trim()) {
        quill.root.innerHTML = hidden.value;
    }

    const sync = () => { hidden.value = quill.root.innerHTML; };
    quill.on('text-change', sync);
    hidden.closest('form')?.addEventListener('submit', sync);

    // Expuesto para páginas que necesitan interactuar con el editor desde
    // fuera de este módulo (ej. insertar una variable {{x}} en el cursor,
    // ver admin/plantillas/edit.blade.php) -- name-agnostic, un único editor por página.
    window.quillEditor = quill;
});
