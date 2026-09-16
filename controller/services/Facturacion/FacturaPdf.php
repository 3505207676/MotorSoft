<?php

/**
 * Documento interno de cobro (PDF 1.4, sin librerías).
 * No es factura electrónica DIAN.
 */
class FacturaPdf
{
    private const PAGE_W = 612.0;
    private const PAGE_H = 792.0;
    private const M = 42.0;

    /** @var string[] */
    private array $paginas = [];
    private string $buf = '';
    private float $y = 0.0;
    /** @var array{bytes:string,w:int,h:int}|null */
    private ?array $imagen = null;

    /** @param array<string,mixed> $factura */
    public function generar(array $factura, array $empresa): string
    {
        $this->paginas = [];
        $this->buf = '';
        $this->imagen = $this->jpegDesdeRuta((string) ($empresa['logo_ruta'] ?? ''));

        $this->nuevaPagina();
        $this->membrete($empresa, $factura);
        $this->bloqueCliente($factura);
        $this->tabla($factura);
        $this->totales($factura);
        $this->cierre($empresa, $factura);
        $this->cerrarPagina();

        return $this->ensamblar($factura);
    }

    /** @param array<string,mixed> $empresa */
    private function membrete(array $empresa, array $factura): void
    {
        $this->rect(0, 708, self::PAGE_W, 84, [0.145, 0.388, 0.922]);
        $nombre = $this->txt($empresa['nombre'] ?? 'Taller El Paisa');
        $numero = $this->txt($factura['numero'] ?? ('FAC-' . ($factura['id_factura'] ?? '')));

        if ($this->imagen) {
            $ih = 52.0;
            $iw = $this->imagen['w'] > 0
                ? $ih * ($this->imagen['h'] > 0 ? ($this->imagen['w'] / $this->imagen['h']) : 1)
                : $ih;
            if ($iw > 72) {
                $iw = 72;
                $ih = $iw * ($this->imagen['h'] / max(1, $this->imagen['w']));
            }
            $this->imagenPdf(self::M, 722, $iw, $ih);
            $this->texto(self::M + $iw + 12, 758, $nombre, 16, true, [1, 1, 1]);
            $this->texto(self::M + $iw + 12, 740, $this->lineaEmpresa($empresa), 9, false, [0.85, 0.90, 1]);
        } else {
            $this->rect(self::M, 722, 52, 52, [1, 1, 1]);
            $ini = $this->iniciales($nombre);
            $this->texto(self::M + (52 - $this->ancho($ini, 14)) / 2, 740, $ini, 14, true, [0.145, 0.388, 0.922]);
            $this->texto(self::M + 64, 758, $nombre, 16, true, [1, 1, 1]);
            $this->texto(self::M + 64, 740, $this->lineaEmpresa($empresa), 9, false, [0.85, 0.90, 1]);
        }

        $this->texto(420, 758, 'DOCUMENTO DE COBRO', 9, true, [1, 1, 1]);
        $this->texto(420, 740, $numero, 14, true, [1, 1, 1]);
        $this->y = 688;
    }

    /** @param array<string,mixed> $empresa */
    private function lineaEmpresa(array $empresa): string
    {
        $bits = [];
        if (!empty($empresa['nit'])) {
            $bits[] = 'NIT ' . $this->txt($empresa['nit']);
        }
        if (!empty($empresa['telefono'])) {
            $bits[] = 'Tel. ' . $this->txt($empresa['telefono']);
        }
        $dir = $this->txt($empresa['direccion'] ?? '');
        $linea = implode('  ·  ', $bits);
        if ($dir !== '') {
            $linea = $linea !== '' ? $linea . '  ·  ' . $dir : $dir;
        }
        return $linea !== '' ? $linea : 'Cumaribo, Vichada';
    }

    /** @param array<string,mixed> $factura */
    private function bloqueCliente(array $factura): void
    {
        $cli = is_array($factura['cliente_obj'] ?? null) ? $factura['cliente_obj'] : [];
        $this->texto(self::M, $this->y, 'Cliente', 8, true, [0.39, 0.45, 0.55]);
        $this->texto(330, $this->y, 'Fecha', 8, true, [0.39, 0.45, 0.55]);
        $this->y -= 14;
        $this->texto(self::M, $this->y, $this->txt($factura['cliente'] ?? ($cli['nombre'] ?? 'Consumidor final')), 12, true);
        $this->texto(330, $this->y, $this->fecha((string) ($factura['fecha_emision'] ?? '')), 11, false);
        $this->y -= 16;

        $meta = [];
        $doc = $this->txt($cli['documento'] ?? '');
        if ($doc !== '') {
            $meta[] = 'Documento ' . $doc;
        }
        $tel = $this->txt($cli['telefono'] ?? '');
        if ($tel !== '') {
            $meta[] = 'Tel. ' . $tel;
        }
        $tipo = $this->txt($factura['tipo_factura'] ?? '');
        if ($tipo !== '') {
            $meta[] = $tipo;
        }
        $estado = $this->txt($factura['estado'] ?? '');
        if ($estado !== '') {
            $meta[] = $estado;
        }
        $pago = $this->txt($factura['metodo_pago'] ?? '');
        if ($pago !== '') {
            $meta[] = 'Pago: ' . $pago;
        }
        if ($meta) {
            $this->texto(self::M, $this->y, implode('   ·   ', $meta), 9, false, [0.29, 0.33, 0.39]);
            $this->y -= 18;
        } else {
            $this->y -= 6;
        }
    }

    /** @param array<string,mixed> $factura */
    private function tabla(array $factura): void
    {
        $this->encabezadoTabla();
        $n = 0;
        foreach (($factura['detalles'] ?? []) as $d) {
            if (!is_array($d)) {
                continue;
            }
            $n++;
            $desc = $this->txt($d['descripcion'] ?? $d['nombre'] ?? $d['tipo_referencia'] ?? 'Ítem');
            $monto = (float) ($d['monto'] ?? $d['subtotal'] ?? 0);
            $lineas = $this->envolver($desc, 360, 9);
            $alto = max(16, count($lineas) * 12 + 6);
            $this->necesitar($alto + 8);
            if ($n % 2 === 1) {
                $this->rect(self::M, $this->y - $alto + 4, self::PAGE_W - self::M * 2, $alto, [0.97, 0.98, 0.99]);
            }
            $yy = $this->y - 2;
            foreach ($lineas as $i => $ln) {
                $this->texto(self::M + 8, $yy - ($i * 12), $ln, 9);
            }
            $this->texto(self::PAGE_W - self::M - 8 - $this->ancho($this->dinero($monto), 9), $this->y - 2, $this->dinero($monto), 9, true);
            $this->y -= $alto;
        }
        if ($n === 0) {
            $this->necesitar(20);
            $this->texto(self::M + 8, $this->y, 'Sin líneas de detalle', 9, false, [0.39, 0.45, 0.55]);
            $this->y -= 18;
        }
        $this->linea(self::M, $this->y, self::PAGE_W - self::M, $this->y, [0.82, 0.86, 0.90]);
        $this->y -= 14;
    }

    private function encabezadoTabla(): void
    {
        $this->necesitar(28);
        $this->rect(self::M, $this->y - 6, self::PAGE_W - self::M * 2, 18, [0.94, 0.96, 0.99]);
        $this->texto(self::M + 8, $this->y, 'Descripción', 8, true, [0.29, 0.33, 0.39]);
        $this->texto(self::PAGE_W - self::M - 50, $this->y, 'Valor', 8, true, [0.29, 0.33, 0.39]);
        $this->y -= 22;
    }

    /** @param array<string,mixed> $factura */
    private function totales(array $factura): void
    {
        $this->necesitar(78);
        $xLabel = 360;
        $xVal = self::PAGE_W - self::M - 8;
        $iva = (float) ($factura['IVA'] ?? $factura['iva'] ?? 0);
        $filas = [
            ['Subtotal', (float) ($factura['subtotal'] ?? 0), false],
            ['IVA', $iva, false],
            ['TOTAL', (float) ($factura['total'] ?? 0), true],
        ];
        foreach ($filas as [$label, $valor, $negrita]) {
            $s = $this->dinero((float) $valor);
            $this->texto($xLabel, $this->y, $label, $negrita ? 12 : 10, $negrita);
            $this->texto($xVal - $this->ancho($s, $negrita ? 12 : 10), $this->y, $s, $negrita ? 12 : 10, true);
            $this->y -= $negrita ? 18 : 15;
        }
        $this->y -= 8;
    }

    /** @param array<string,mixed> $empresa */
    private function cierre(array $empresa, array $factura): void
    {
        $this->necesitar(70);
        $this->linea(self::M, $this->y + 10, self::PAGE_W - self::M, $this->y + 10, [0.82, 0.86, 0.90]);
        $ciudad = $this->txt($empresa['ciudad'] ?? 'Cumaribo, Vichada');
        $this->texto(self::M, $this->y, 'Documento interno de cobro. No es factura electrónica DIAN.', 8, false, [0.39, 0.45, 0.55]);
        $this->y -= 12;
        $this->texto(self::M, $this->y, $ciudad . ' — ' . $this->txt($empresa['nombre'] ?? 'Taller El Paisa'), 8, false, [0.39, 0.45, 0.55]);
        $estado = strtolower((string) ($factura['estado'] ?? ''));
        if ($estado === 'anulada') {
            $this->selloAnulada();
        }
    }

    private function selloAnulada(): void
    {
        $this->buf .= "q\n1 0 0 1 200 400 cm\n0.866 0.5 -0.5 0.866 0 0 cm\n";
        $this->buf .= "BT /F2 36 Tf 0.86 0.15 0.15 rg 0 0 Td (ANULADA) Tj ET\nQ\n";
    }

    private function nuevaPagina(): void
    {
        if ($this->buf !== '') {
            $this->cerrarPagina();
        }
        $this->buf = '';
        if (count($this->paginas) > 0) {
            $this->y = 750;
            $this->texto(self::M, $this->y, 'Documento de cobro (continuación)', 10, true, [0.145, 0.388, 0.922]);
            $this->y = 728;
            $this->encabezadoTabla();
        } else {
            $this->y = 688;
        }
    }

    private function cerrarPagina(): void
    {
        $n = count($this->paginas) + 1;
        $marca = 'Página ' . $n;
        $this->texto(self::PAGE_W - self::M - $this->ancho($marca, 8), 28, $marca, 8, false, [0.55, 0.60, 0.66]);
        $this->paginas[] = $this->buf;
        $this->buf = '';
    }

    private function necesitar(float $alto): void
    {
        if ($this->y - $alto < 56) {
            $this->nuevaPagina();
        }
    }

    /** @param float[] $rgb */
    private function rect(float $x, float $y, float $w, float $h, array $rgb): void
    {
        $this->buf .= sprintf(
            "%.3f %.3f %.3f rg %.2f %.2f %.2f %.2f re f\n0 g\n",
            $rgb[0], $rgb[1], $rgb[2], $x, $y, $w, $h
        );
    }

    /** @param float[] $rgb */
    private function linea(float $x1, float $y1, float $x2, float $y2, array $rgb): void
    {
        $this->buf .= sprintf(
            "%.3f %.3f %.3f RG %.2f %.2f m %.2f %.2f l S\n0 G\n",
            $rgb[0], $rgb[1], $rgb[2], $x1, $y1, $x2, $y2
        );
    }

    /** @param float[] $rgb */
    private function texto(float $x, float $y, string $txt, float $size, bool $bold = false, array $rgb = [0.06, 0.09, 0.16]): void
    {
        $font = $bold ? 'F2' : 'F1';
        $t = $this->pdfString($txt);
        $this->buf .= sprintf(
            "BT /%s %.2f Tf %.3f %.3f %.3f rg %.2f %.2f Td (%s) Tj ET\n0 g\n",
            $font, $size, $rgb[0], $rgb[1], $rgb[2], $x, $y, $t
        );
    }

    private function imagenPdf(float $x, float $y, float $w, float $h): void
    {
        $this->buf .= sprintf("q %.2f 0 0 %.2f %.2f %.2f cm /Im1 Do Q\n", $w, $h, $x, $y);
    }

    /** @return string[] */
    private function envolver(string $texto, float $ancho, float $size): array
    {
        $palabras = preg_split('/\s+/', $texto) ?: [$texto];
        $lineas = [];
        $actual = '';
        foreach ($palabras as $p) {
            $prueba = $actual === '' ? $p : $actual . ' ' . $p;
            if ($this->ancho($prueba, $size) <= $ancho) {
                $actual = $prueba;
                continue;
            }
            if ($actual !== '') {
                $lineas[] = $actual;
            }
            $actual = $p;
        }
        if ($actual !== '') {
            $lineas[] = $actual;
        }
        return $lineas ?: [''];
    }

    private function ancho(string $texto, float $size): float
    {
        return mb_strlen($texto) * $size * 0.5;
    }

    private function iniciales(string $nombre): string
    {
        $partes = preg_split('/\s+/', trim($nombre)) ?: [];
        $ini = '';
        foreach ($partes as $p) {
            if ($p === '') {
                continue;
            }
            $ini .= mb_strtoupper(mb_substr($p, 0, 1));
            if (mb_strlen($ini) >= 3) {
                break;
            }
        }
        return $ini !== '' ? $ini : 'TP';
    }

    private function fecha(string $valor): string
    {
        $ts = strtotime($valor);
        if ($ts === false) {
            return substr($valor, 0, 10);
        }
        return date('d/m/Y', $ts);
    }

    private function jpegDesdeRuta(string $ruta): ?array
    {
        if ($ruta === '' || !is_file($ruta)) {
            return null;
        }
        if (!function_exists('imagecreatefromstring')) {
            $ext = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg'], true)) {
                return null;
            }
            $bytes = (string) file_get_contents($ruta);
            $info = @getimagesize($ruta);
            if (!$info || empty($info[0])) {
                return null;
            }
            return ['bytes' => $bytes, 'w' => (int) $info[0], 'h' => (int) $info[1]];
        }
        $src = @imagecreatefromstring((string) file_get_contents($ruta));
        if (!$src) {
            return null;
        }
        $w = imagesx($src);
        $h = imagesy($src);
        $max = 480;
        $nw = $w;
        $nh = $h;
        if ($w > $max || $h > $max) {
            $esc = min($max / max(1, $w), $max / max(1, $h));
            $nw = max(1, (int) round($w * $esc));
            $nh = max(1, (int) round($h * $esc));
        }
        $dst = imagecreatetruecolor($nw, $nh);
        $fondo = imagecolorallocate($dst, 37, 99, 235);
        imagefilledrectangle($dst, 0, 0, $nw, $nh, $fondo);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($src);
        if (strcasecmp(basename($ruta), 'logo-taller.png') === 0) {
            $cx = $nw / 2;
            $cy = $nh / 2;
            $r2 = (min($nw, $nh) / 2) ** 2;
            for ($y = 0; $y < $nh; $y++) {
                for ($x = 0; $x < $nw; $x++) {
                    $dx = $x - $cx + 0.5;
                    $dy = $y - $cy + 0.5;
                    if (($dx * $dx) + ($dy * $dy) > $r2) {
                        imagesetpixel($dst, $x, $y, $fondo);
                    }
                }
            }
        }
        ob_start();
        imagejpeg($dst, null, 86);
        $bytes = (string) ob_get_clean();
        imagedestroy($dst);
        $w = $nw;
        $h = $nh;
        if ($bytes === '') {
            return null;
        }
        return ['bytes' => $bytes, 'w' => $w, 'h' => $h];
    }

    /** @param array<string,mixed> $factura */
    private function ensamblar(array $factura): string
    {
        $objs = [];
        $objs[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $font1 = 3;
        $font2 = 4;
        $imgN = $this->imagen ? 5 : null;
        $primeroPagina = $this->imagen ? 6 : 5;

        $kids = [];
        $pageObjs = [];
        $contentObjs = [];
        $n = $primeroPagina;
        foreach ($this->paginas as $i => $_c) {
            $kids[] = $n . ' 0 R';
            $pageObjs[$i] = $n;
            $contentObjs[$i] = $n + 1;
            $n += 2;
        }
        $objs[] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
        $objs[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objs[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        if ($this->imagen) {
            $len = strlen($this->imagen['bytes']);
            $objs[] = '<< /Type /XObject /Subtype /Image /Width ' . (int) $this->imagen['w']
                . ' /Height ' . (int) $this->imagen['h']
                . ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ' . $len
                . " >>\nstream\n" . $this->imagen['bytes'] . "\nendstream";
        }

        $xobj = $imgN ? " /XObject << /Im1 {$imgN} 0 R >>" : '';
        foreach ($this->paginas as $i => $contenido) {
            $len = strlen($contenido);
            $cObj = $contentObjs[$i];
            $objs[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 " . self::PAGE_W . " " . self::PAGE_H . "] /Contents {$cObj} 0 R /Resources << /Font << /F1 {$font1} 0 R /F2 {$font2} 0 R >>{$xobj} >> >>";
            $objs[] = "<< /Length {$len} >>\nstream\n{$contenido}\nendstream";
        }

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objs as $i => $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n{$obj}\nendobj\n";
        }
        $xref = strlen($pdf);
        $count = count($objs) + 1;
        $pdf .= "xref\n0 {$count}\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i < $count; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $titulo = $this->pdfString('Cobro ' . ($factura['numero'] ?? ''));
        $pdf .= "trailer << /Size {$count} /Root 1 0 R /Info << /Title ({$titulo}) /Creator (MotorSoft) >> >>\nstartxref\n{$xref}\n%%EOF";
        return $pdf;
    }

    private function pdfString(string $texto): string
    {
        $iso = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $texto);
        if ($iso === false) {
            $iso = utf8_decode($texto);
        }
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $iso);
    }

    private function txt($valor): string
    {
        return trim(str_replace(["\r", "\n"], ' ', (string) $valor));
    }

    private function dinero(float $valor): string
    {
        return '$' . number_format($valor, 0, ',', '.');
    }
}
