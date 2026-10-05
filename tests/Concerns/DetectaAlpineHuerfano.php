<?php

namespace Tests\Concerns;

/**
 * Alpine solo procesa lo que está DENTRO de un elemento con x-data. Si un botón o un formulario con x-show / @click / x-model queda
 * fuera, se ve pero no hace nada y no da ningún error en consola (le pasó a la cafetería: los formularios de venta, recarga y ajuste
 * quedaron fuera del x-data que guardaba `modalVenta`). Se analiza el HTML YA RENDERIZADO: el código fuente de Blade, con sus ramas
 * @if/@else, no se puede recorrer con fiabilidad.
 */
trait DetectaAlpineHuerfano
{
    /** @return string[] un texto por cada elemento con directivas de Alpine fuera de cualquier x-data */
    private function alpineHuerfanos(string $html): array
    {
        $html = preg_replace('#<script\b.*?</script>#si', '', $html);
        $html = preg_replace('#<style\b.*?</style>#si', '', $html);
        // libxml no admite «@» en nombres de atributo: @click → x-on:click
        $html = preg_replace('/(\s)@([a-zA-Z])/', '$1x-on:$2', $html);

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $out = [];
        foreach ((new \DOMXPath($dom))->query('//*') as $el) {
            $dentro = false;
            for ($n = $el; $n instanceof \DOMElement; $n = $n->parentNode) {
                if ($n->hasAttribute('x-data')) {
                    $dentro = true;
                    break;
                }
            }
            if ($dentro) {
                continue;
            }
            foreach ($el->attributes as $a) {
                if ($a->name !== 'x-cloak' && preg_match('/^(x-show|x-if|x-model|x-text|x-html|x-for|x-on:|x-bind:)/', $a->name)) {
                    $out[] = '<' . $el->nodeName . ' ' . $a->name . '="' . mb_substr($a->value, 0, 30) . '">';
                    break;
                }
            }
        }

        return $out;
    }
}
