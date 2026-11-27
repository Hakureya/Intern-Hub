<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Penulis dokumen .docx sederhana (paragraf + tabel) tanpa dependensi tambahan; hanya memerlukan ekstensi zip PHP.
 * Ukuran kertas A4 potret, margin 0.5". Satuan lebar tabel: DXA (1440 = 1 inci).
 *
 * Spesifikasi paragraf: string, atau array dengan kunci
 *   text | runs[[text,b,i,sz,color]], b, i, sz (setengah poin), color (hex), align (left|center|right),
 *   before, after (DXA), keepNext (bool)
 * Spesifikasi sel: string, atau array dengan kunci
 *   vmerge (restart|continue, gabung sel vertikal), p (daftar spesifikasi paragraf) | text, fill (hex), span (jumlah kolom), align, b, valign (top|center)
 */
class DocxBuilder
{
    /** Lebar area teks (A4 11906 - margin kiri/kanan 720 x 2). */
    public const TEXT_WIDTH = 10466;

    private $body = '';
    private $font;
    private $baseSize;

    public function __construct(string $font = 'Roboto', int $baseSize = 16)
    {
        $this->font = $font;
        $this->baseSize = $baseSize;
    }

    public static function esc($text): string
    {
        $clean = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', (string) $text);
        if ($clean === null) {
            $clean = '';
        }

        return htmlspecialchars($clean, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function run(string $text, array $o = []): string
    {
        $rpr = '';
        if (! empty($o['b'])) {
            $rpr .= '<w:b/><w:bCs/>';
        }
        if (! empty($o['i'])) {
            $rpr .= '<w:i/><w:iCs/>';
        }
        if (! empty($o['color'])) {
            $rpr .= '<w:color w:val="' . self::esc($o['color']) . '"/>';
        }
        if (! empty($o['sz'])) {
            $rpr .= '<w:sz w:val="' . (int) $o['sz'] . '"/><w:szCs w:val="' . (int) $o['sz'] . '"/>';
        }
        $rpr = $rpr !== '' ? '<w:rPr>' . $rpr . '</w:rPr>' : '';

        $parts = explode("\n", $text);
        $xml = '';
        foreach ($parts as $i => $part) {
            if ($i > 0) {
                $xml .= '<w:r>' . $rpr . '<w:br/></w:r>';
            }
            $xml .= '<w:r>' . $rpr . '<w:t xml:space="preserve">' . self::esc($part) . '</w:t></w:r>';
        }

        return $xml;
    }

    /** XML satu paragraf. $defaults dipakai bila spesifikasi tidak menyebut nilainya. */
    public function paragraphXml($spec, array $defaults = []): string
    {
        if (! is_array($spec)) {
            $spec = ['text' => (string) $spec];
        }
        $o = $spec + $defaults;

        $ppr = '';
        if (! empty($o['keepNext'])) {
            $ppr .= '<w:keepNext/>';
        }
        $ppr .= '<w:spacing w:before="' . (int) ($o['before'] ?? 0) . '" w:after="' . (int) ($o['after'] ?? 0) . '" w:line="' . (int) ($o['line'] ?? 252) . '" w:lineRule="auto"/>';
        if (! empty($o['indent'])) {
            $ppr .= '<w:ind w:left="' . (int) $o['indent'] . '" w:hanging="' . (int) ($o['hanging'] ?? 0) . '"/>';
        }
        $align = $o['align'] ?? 'left';
        if (in_array($align, ['center', 'right', 'both'], true)) {
            $ppr .= '<w:jc w:val="' . $align . '"/>';
        }

        $runs = '';
        if (isset($o['runs'])) {
            foreach ($o['runs'] as $r) {
                $runs .= $this->run((string) $r['text'], $r + ['sz' => $o['sz'] ?? $this->baseSize]);
            }
        } else {
            $runs = $this->run((string) ($o['text'] ?? ''), [
                'b' => $o['b'] ?? false,
                'i' => $o['i'] ?? false,
                'color' => $o['color'] ?? null,
                'sz' => $o['sz'] ?? $this->baseSize,
            ]);
        }

        return '<w:p><w:pPr>' . $ppr . '</w:pPr>' . $runs . '</w:p>';
    }

    public function paragraph($spec, array $defaults = []): self
    {
        $this->body .= $this->paragraphXml($spec, $defaults);

        return $this;
    }

    public function spacer(int $after = 120): self
    {
        $this->body .= '<w:p><w:pPr><w:spacing w:before="0" w:after="' . $after . '" w:line="120" w:lineRule="exact"/></w:pPr></w:p>';

        return $this;
    }

    public function pageBreak(): self
    {
        $this->body .= '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';

        return $this;
    }

    /**
     * Tabel dengan garis hitam tipis. $rows = daftar baris; tiap baris = ['cells' => [...], 'header' => bool].
     * Baris header diberi latar abu-abu, huruf tebal, rata tengah, dan diulang di tiap halaman.
     */
    public function table(array $widths, array $rows, array $opts = []): self
    {
        $total = array_sum($widths);
        $borders = '';
        foreach (['top', 'left', 'bottom', 'right', 'insideH', 'insideV'] as $edge) {
            $borders .= '<w:' . $edge . ' w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        }
        $noBorder = ! empty($opts['borderless']);
        if ($noBorder) {
            $borders = '';
            foreach (['top', 'left', 'bottom', 'right', 'insideH', 'insideV'] as $edge) {
                $borders .= '<w:' . $edge . ' w:val="nil"/>';
            }
        }

        $xml = '<w:tbl><w:tblPr><w:tblW w:w="' . $total . '" w:type="dxa"/><w:tblBorders>' . $borders . '</w:tblBorders>'
            . '<w:tblLayout w:type="fixed"/><w:tblCellMar><w:top w:w="40" w:type="dxa"/><w:left w:w="90" w:type="dxa"/><w:bottom w:w="40" w:type="dxa"/><w:right w:w="90" w:type="dxa"/></w:tblCellMar></w:tblPr>';
        $xml .= '<w:tblGrid>';
        foreach ($widths as $w) {
            $xml .= '<w:gridCol w:w="' . (int) $w . '"/>';
        }
        $xml .= '</w:tblGrid>';

        foreach ($rows as $row) {
            $header = ! empty($row['header']);
            $xml .= '<w:tr>' . ($header ? '<w:trPr><w:cantSplit/><w:tblHeader/></w:trPr>' : '');

            $col = 0;
            foreach ($row['cells'] as $cell) {
                if (! is_array($cell)) {
                    $cell = ['text' => (string) $cell];
                }
                $span = max(1, (int) ($cell['span'] ?? 1));
                $w = 0;
                for ($i = 0; $i < $span; $i++) {
                    $w += (int) ($widths[$col + $i] ?? 0);
                }
                $col += $span;

                $tcpr = '<w:tcW w:w="' . $w . '" w:type="dxa"/>';
                if ($span > 1) {
                    $tcpr .= '<w:gridSpan w:val="' . $span . '"/>';
                }
                if (($cell['vmerge'] ?? '') === 'restart') {
                    $tcpr .= '<w:vMerge w:val="restart"/>';
                } elseif (($cell['vmerge'] ?? '') === 'continue') {
                    $tcpr .= '<w:vMerge/>';
                }
                $fill = $cell['fill'] ?? ($header ? 'F2F2F2' : null);
                if ($fill) {
                    $tcpr .= '<w:shd w:val="clear" w:color="auto" w:fill="' . self::esc($fill) . '"/>';
                }
                $tcpr .= '<w:vAlign w:val="' . (($cell['valign'] ?? (($header || ! empty($cell['vmerge']) || ($cell['align'] ?? '') === 'center') ? 'center' : 'top')) === 'center' ? 'center' : 'top') . '"/>';

                $defaults = [
                    'align' => $cell['align'] ?? ($header ? 'center' : 'left'),
                    'b' => $cell['b'] ?? $header,
                ];
                $paras = $cell['p'] ?? [['text' => (string) ($cell['text'] ?? '')]];
                $pxml = '';
                foreach ($paras as $p) {
                    $pxml .= $this->paragraphXml($p, $defaults);
                }
                if ($pxml === '') {
                    $pxml = '<w:p/>';
                }

                $xml .= '<w:tc><w:tcPr>' . $tcpr . '</w:tcPr>' . $pxml . '</w:tc>';
            }

            $xml .= '</w:tr>';
        }

        $xml .= '</w:tbl>';
        $this->body .= $xml;

        return $this;
    }

    public function save(string $path): void
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekstensi zip PHP belum aktif.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Tidak bisa membuat berkas dokumen.');
        }

        $ns = 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"';

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            . '<Override PartName="/word/footer1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/>'
            . '</Types>');

        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '</Relationships>');

        $zip->addFromString('word/_rels/document.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer" Target="footer1.xml"/>'
            . '</Relationships>');

        $font = self::esc($this->font);
        $zip->addFromString('word/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:styles ' . $ns . '><w:docDefaults><w:rPrDefault><w:rPr>'
            . '<w:rFonts w:ascii="' . $font . '" w:hAnsi="' . $font . '" w:cs="' . $font . '" w:eastAsia="' . $font . '"/>'
            . '<w:sz w:val="' . $this->baseSize . '"/><w:szCs w:val="' . $this->baseSize . '"/><w:lang w:val="id-ID"/>'
            . '</w:rPr></w:rPrDefault><w:pPrDefault><w:pPr><w:spacing w:after="0" w:line="252" w:lineRule="auto"/></w:pPr></w:pPrDefault></w:docDefaults>'
            . '<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/><w:qFormat/></w:style>'
            . '</w:styles>');

        $zip->addFromString('word/footer1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:ftr ' . $ns . '><w:p><w:pPr><w:jc w:val="center"/></w:pPr>'
            . '<w:r><w:rPr><w:color w:val="7F7F7F"/><w:sz w:val="14"/></w:rPr><w:t xml:space="preserve">Halaman </w:t></w:r>'
            . '<w:r><w:rPr><w:color w:val="7F7F7F"/><w:sz w:val="14"/></w:rPr><w:fldChar w:fldCharType="begin"/></w:r>'
            . '<w:r><w:rPr><w:color w:val="7F7F7F"/><w:sz w:val="14"/></w:rPr><w:instrText xml:space="preserve"> PAGE </w:instrText></w:r>'
            . '<w:r><w:rPr><w:color w:val="7F7F7F"/><w:sz w:val="14"/></w:rPr><w:fldChar w:fldCharType="separate"/></w:r>'
            . '<w:r><w:rPr><w:color w:val="7F7F7F"/><w:sz w:val="14"/></w:rPr><w:t>1</w:t></w:r>'
            . '<w:r><w:rPr><w:color w:val="7F7F7F"/><w:sz w:val="14"/></w:rPr><w:fldChar w:fldCharType="end"/></w:r>'
            . '</w:p></w:ftr>');

        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document ' . $ns . '><w:body>' . $this->body
            . '<w:sectPr><w:footerReference w:type="default" r:id="rId2"/>'
            . '<w:pgSz w:w="11906" w:h="16838"/>'
            . '<w:pgMar w:top="720" w:right="720" w:bottom="900" w:left="720" w:header="400" w:footer="400" w:gutter="0"/></w:sectPr>'
            . '</w:body></w:document>');

        $zip->close();
    }
}
