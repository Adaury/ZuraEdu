"""Prueba de clasificar() de carga.py: python scripts/carga/test_clasificar.py (sale con 1 si algún caso falla)."""
import importlib.util, os, sys
spec = importlib.util.spec_from_file_location('carga', os.path.join(os.path.dirname(os.path.abspath(__file__)), 'carga.py'))
m = importlib.util.module_from_spec(spec); spec.loader.exec_module(m)
c = m.clasificar
casos = [
    ('JSON chunked completo',      (200, b'HTTP/1.1 200 OK\r\n\r\nb\r\n{"count":0}\r\n0\r\n\r\n'), 'ok'),
    ('JSON sin chunked',           (200, b'{"count":3}'), 'ok'),
    ('lista JSON chunked',         (200, b'\r\n3\r\n[1]\r\n0\r\n\r\n'), 'ok'),
    ('HTML completo',              (200, b'<html></html>\r\n0\r\n\r\n'), 'ok'),
    ('JSON cortado',               (200, b'{"count":'), 'incompleta'),
    ('cortado justo antes del 0',  (200, b'{"cou\r\n0\r\n\r\n'), 'incompleta'),
    ('redireccion',                (302, b''), 'redireccion'),
    ('5xx',                        (503, b'x'), '5xx'),
]
mal = 0
for nombre, (est, cuerpo), esperado in casos:
    r = c(est, cuerpo)
    print(('OK  ' if r == esperado else 'MAL ') + nombre, '->', r)
    mal += r != esperado
sys.exit(1 if mal else 0)
