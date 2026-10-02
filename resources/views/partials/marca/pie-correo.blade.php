{{-- Pie de marca para correos (tabla, estilos en línea, logo PNG por URL absoluta). Colocar dentro de una <table>, como una fila más. --}}
<tr>
  <td align="center" style="padding:14px 16px 4px;font-family:Arial,Helvetica,sans-serif;">
    <img src="{{ \App\Support\Marca::logoUrl('png') }}" alt="{{ \App\Support\Marca::nombre() }}" width="104" style="width:104px;height:auto;border:0;display:inline-block;">
  </td>
</tr>
<tr>
  <td align="center" style="padding:0 16px 14px;font-family:Arial,Helvetica,sans-serif;font-size:11px;line-height:1.5;color:#94a3b8;">
    {{ \App\Support\Marca::copyright() }}
  </td>
</tr>
