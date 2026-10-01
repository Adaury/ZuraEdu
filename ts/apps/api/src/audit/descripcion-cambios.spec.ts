import { ESTUDIANTE_CAMPOS_AUDITADOS } from '@zuraedu/shared';
import { NOMBRES_COLUMNAS_EDITABLES } from '../estudiantes/estudiante-columnas';
import {
  camposCambiadosComoLaravel,
  cambiosAuditados,
  descripcionEdicionEstudiante,
  descripcionObserverActualizado,
  EstudianteAuditable,
} from './descripcion-cambios';

const base: EstudianteAuditable = {
  cedula: '00100000001',
  nombres: 'Ana',
  apellidos: 'Perez',
  fecha_nacimiento: '2012-05-01',
  estado: 'activo',
};

describe('cambiosAuditados (formato idéntico al de EstudianteController@update de Laravel)', () => {
  it('sin cambios devuelve una lista vacía (no se audita nada)', () => {
    expect(cambiosAuditados(base, { ...base })).toEqual([]);
  });

  it('un cambio: "campo: antes → después"', () => {
    expect(cambiosAuditados(base, { ...base, estado: 'inactivo' })).toEqual(['estado: activo → inactivo']);
  });

  it('varios cambios salen en el orden fijo de campos auditados (no en el orden en que se enviaron)', () => {
    const despues = { ...base, estado: 'egresado', nombres: 'Anita', cedula: '999' };
    expect(cambiosAuditados(base, despues)).toEqual([
      'cedula: 00100000001 → 999',
      'nombres: Ana → Anita',
      'estado: activo → egresado',
    ]);
  });

  it('un valor nulo se muestra como "—" (en ambos sentidos)', () => {
    expect(cambiosAuditados({ ...base, cedula: null }, { ...base, cedula: '123' })).toEqual(['cedula: — → 123']);
    expect(cambiosAuditados(base, { ...base, cedula: null })).toEqual(['cedula: 00100000001 → —']);
  });

  it('null y cadena vacía son lo mismo, como en PHP ((string) null === "")', () => {
    expect(cambiosAuditados({ ...base, cedula: null }, { ...base, cedula: '' })).toEqual([]);
  });

  it('la fecha de nacimiento se escribe como Laravel (Carbon → "Y-m-d 00:00:00")', () => {
    expect(cambiosAuditados(base, { ...base, fecha_nacimiento: '2013-01-15' })).toEqual([
      'fecha_nacimiento: 2012-05-01 00:00:00 → 2013-01-15 00:00:00',
    ]);
  });

  it('una fecha que pasa de nula a un valor también lleva la hora', () => {
    expect(cambiosAuditados({ ...base, fecha_nacimiento: null }, base)).toEqual([
      'fecha_nacimiento: — → 2012-05-01 00:00:00',
    ]);
  });

  it('cambiar solo mayúsculas SÍ es un cambio (la comparación es exacta)', () => {
    expect(cambiosAuditados(base, { ...base, nombres: 'ANA' })).toEqual(['nombres: Ana → ANA']);
  });
});

describe('descripcionEdicionEstudiante', () => {
  it('une los cambios con " | " tras el prefijo "Estudiante #id: "', () => {
    expect(descripcionEdicionEstudiante(12, ['cedula: — → 001', 'estado: activo → inactivo'])).toBe(
      'Estudiante #12: cedula: — → 001 | estado: activo → inactivo',
    );
  });
});

describe('registro del observer (EstudianteObserver::updated de Laravel)', () => {
  it('descripcionObserverActualizado: "Estudiante actualizado: Apellidos, Nombres | Campos: ..."', () => {
    expect(descripcionObserverActualizado('Perez', 'Ana', ['estado', 'updated_at'])).toBe(
      'Estudiante actualizado: Perez, Ana | Campos: estado, updated_at',
    );
  });

  it('lista solo las columnas que cambiaron, en orden de Laravel, y siempre cierra con updated_at', () => {
    const despues = { ...base, estado: 'egresado', nombres: 'Anita', cedula: '999' };
    expect(camposCambiadosComoLaravel(base, despues, ESTUDIANTE_CAMPOS_AUDITADOS)).toEqual(['cedula', 'nombres', 'estado', 'updated_at']);
  });

  it('sin cambios devuelve una lista vacía (Laravel no dispara el evento si el modelo no está sucio)', () => {
    expect(camposCambiadosComoLaravel(base, { ...base }, ESTUDIANTE_CAMPOS_AUDITADOS)).toEqual([]);
  });

  it('null y cadena vacía no cuentan como cambio, igual que en la auditoría del controlador', () => {
    expect(camposCambiadosComoLaravel({ ...base, cedula: null }, { ...base, cedula: '' }, ESTUDIANTE_CAMPOS_AUDITADOS)).toEqual([]);
  });

  it('el orden de campos coincide con el de cambiosAuditados', () => {
    const despues = { ...base, estado: 'inactivo', apellidos: 'Gomez' };
    const campos = camposCambiadosComoLaravel(base, despues, ESTUDIANTE_CAMPOS_AUDITADOS).filter((c) => c !== 'updated_at');
    const nombresEnDescripcion = cambiosAuditados(base, despues).map((c) => c.split(':')[0]);
    expect(campos).toEqual(nombresEnDescripcion);
  });

  describe('con todas las columnas editables (en el orden de la tabla)', () => {
    const antes = { numero_matricula: 'M-1', telefono: null, email: 'a@b.com', sector: 'Centro', estado: 'activo', notas_medicas: null };

    it('sigue el orden de las columnas de la tabla, no el orden en que llegan los campos', () => {
      const despues = { ...antes, notas_medicas: 'Alergia', estado: 'inactivo', telefono: '8095551234', numero_matricula: 'M-2' };
      expect(camposCambiadosComoLaravel(antes, despues, NOMBRES_COLUMNAS_EDITABLES)).toEqual([
        'numero_matricula',
        'telefono',
        'estado',
        'notas_medicas',
        'updated_at',
      ]);
    });

    it('un campo no sensible (teléfono) cuenta como cambio aunque no entre en el detalle de auditoría', () => {
      expect(camposCambiadosComoLaravel(antes, { ...antes, telefono: '809' }, NOMBRES_COLUMNAS_EDITABLES)).toEqual(['telefono', 'updated_at']);
    });

    it('ignora columnas que no están en la lista editable (id, tenant_id, deleted_at...)', () => {
      expect(camposCambiadosComoLaravel({ ...antes, tenant_id: '1' }, { ...antes, tenant_id: '2' }, NOMBRES_COLUMNAS_EDITABLES)).toEqual([]);
    });

    it('la lista editable sigue el orden de las columnas de la tabla estudiantes', () => {
      expect([...NOMBRES_COLUMNAS_EDITABLES]).toEqual([
        'numero_matricula', 'cedula', 'nombres', 'apellidos', 'fecha_nacimiento', 'sexo', 'nacionalidad',
        'lugar_nacimiento', 'telefono', 'email', 'direccion', 'sector', 'municipio', 'provincia', 'estado',
        'tutor_nombre', 'tutor_parentesco', 'tutor_telefono', 'tutor_trabajo', 'notas_medicas',
      ]);
    });
  });
});
