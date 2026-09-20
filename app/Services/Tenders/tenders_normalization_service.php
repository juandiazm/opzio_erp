<?php

namespace App\Services\Tenders;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

class tenders_normalization_service
{
    private const NON_POSTULABLE_MARKERS = [
        'adjudicad', 'aprobacion', 'aprobado', 'cancelad', 'celebrad',
        'cerrad', 'descartad', 'desiert', 'evaluacion', 'fallid', 'liquidado',
        'no ofert', 'rechazad', 'revocad', 'seleccionad', 'sin ofert',
        'suspendid', 'terminad', 'anulad', 'borrador',
    ];

    public function normalize(string $source, array $row): ?array
    {
        return $source === 'secop1'
            ? $this->normalizeSecop1($row)
            : $this->normalizeSecop2($row);
    }

    public function isPostulable(?string $status): bool
    {
        $normalized = $this->normalizeText($status);
        foreach (self::NON_POSTULABLE_MARKERS as $marker) {
            if (str_contains($normalized, $marker)) {
                return false;
            }
        }

        return true;
    }

    public function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return Carbon::parse((string) $value)->utc();
        } catch (\Throwable) {
            return null;
        }
    }

    public function parseAmount(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $normalized = str_replace(['$', ' '], '', (string) $value);
        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $normalized = strrpos($normalized, ',') > strrpos($normalized, '.')
                ? str_replace(',', '.', str_replace('.', '', $normalized))
                : str_replace(',', '', $normalized);
        } elseif (substr_count($normalized, ',') > 1) {
            $normalized = str_replace(',', '', $normalized);
        } elseif (str_contains($normalized, ',')) {
            [$integer, $fraction] = array_pad(explode(',', $normalized, 2), 2, '');
            $normalized = strlen($fraction) <= 2 ? $integer.'.'.$fraction : str_replace(',', '', $normalized);
        }

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function normalizeSecop2(array $row): ?array
    {
        $sourceId = $this->text($row['id_del_proceso'] ?? null);
        if ($sourceId === null) {
            return null;
        }
        $sourceStatus = $this->meaningful($row['estado_del_procedimiento'] ?? null);
        $openingStatus = $this->meaningful($row['estado_de_apertura_del_proceso'] ?? null);
        $status = strtolower((string) $openingStatus) === 'abierto' && $this->isPostulable($sourceStatus)
            ? 'open'
            : 'closed';

        return [
            'source' => 'secop2',
            'source_id' => $sourceId,
            'source_process_id' => $this->text($row['id_del_portafolio'] ?? null),
            'reference' => $this->meaningful($row['referencia_del_proceso'] ?? null),
            'title' => $this->meaningful($row['nombre_del_procedimiento'] ?? null) ?: 'Proceso SECOP II sin titulo',
            'description' => $this->meaningful($row['descripci_n_del_procedimiento'] ?? null),
            'entity' => $this->meaningful($row['entidad'] ?? null) ?: 'Entidad no identificada',
            'entity_nit' => $this->meaningful($row['nit_entidad'] ?? null),
            'department' => $this->meaningful($row['departamento_entidad'] ?? null),
            'city' => $this->meaningful($row['ciudad_entidad'] ?? null) ?: $this->meaningful($row['ciudad_de_la_unidad_de'] ?? null),
            'amount' => $this->parseAmount($row['precio_base'] ?? null),
            'currency' => 'COP',
            'phase' => $this->meaningful($row['fase'] ?? null),
            'procurement_method' => $this->meaningful($row['modalidad_de_contratacion'] ?? null),
            'contract_type' => $this->meaningful($row['tipo_de_contrato'] ?? null),
            'category_code' => $this->meaningful($row['codigo_principal_de_categoria'] ?? null),
            'category_text' => $this->joinMeaningful([$row['categorias_adicionales'] ?? null, $row['subtipo_de_contrato'] ?? null]),
            'status' => $status,
            'source_status' => $sourceStatus,
            'opening_status' => $openingStatus,
            'source_url' => $this->nestedUrl($row['urlproceso'] ?? null),
            'published_at' => $this->parseDate($row['fecha_de_publicacion_del'] ?? null),
            'last_published_at' => $this->parseDate($row['fecha_de_ultima_publicaci'] ?? null),
            'deadline_at' => $this->parseDate($row['fecha_de_recepcion_de'] ?? null)
                ?: $this->parseDate($row['fecha_de_apertura_de_respuesta'] ?? null),
        ];
    }

    private function normalizeSecop1(array $row): ?array
    {
        $sourceId = $this->text($row['uid'] ?? null) ?: $this->text($row['numero_de_constancia'] ?? null);
        if ($sourceId === null) {
            return null;
        }
        $sourceStatus = $this->meaningful($row['estado_del_proceso'] ?? null);
        $normalizedStatus = $this->normalizeText($sourceStatus);
        $status = $this->isPostulable($sourceStatus)
            && collect(['convocado', 'publicado', 'abierto', 'proceso'])->contains(fn ($value) => str_contains($normalizedStatus, $value))
            ? 'open'
            : 'closed';
        $amount = $this->parseAmount($row['cuantia_proceso'] ?? null);
        if ($amount === null) {
            $amount = $this->parseAmount($row['cuantia_contrato'] ?? null);
        }

        return [
            'source' => 'secop1',
            'source_id' => $sourceId,
            'source_process_id' => $this->text($row['numero_de_constancia'] ?? null),
            'reference' => $this->meaningful($row['numero_de_proceso'] ?? null) ?: $this->meaningful($row['numero_de_constancia'] ?? null),
            'title' => $this->meaningful($row['objeto_a_contratar'] ?? null) ?: 'Proceso SECOP I sin titulo',
            'description' => $this->meaningful($row['detalle_del_objeto_a_contratar'] ?? null)
                ?: $this->meaningful($row['objeto_a_contratar'] ?? null),
            'entity' => $this->meaningful($row['nombre_entidad'] ?? null) ?: 'Entidad no identificada',
            'entity_nit' => $this->meaningful($row['nit_de_la_entidad'] ?? null),
            'department' => $this->meaningful($row['departamento_entidad'] ?? null),
            'city' => $this->meaningful($row['municipio_entidad'] ?? null)
                ?: $this->meaningful($row['municipio_de_obtencion'] ?? null)
                ?: $this->meaningful($row['municipio_de_entrega'] ?? null),
            'amount' => $amount,
            'currency' => $this->meaningful($row['moneda'] ?? null) ?: 'COP',
            'phase' => null,
            'procurement_method' => $this->meaningful($row['modalidad_de_contratacion'] ?? null),
            'contract_type' => $this->meaningful($row['tipo_de_contrato'] ?? null),
            'category_code' => null,
            'category_text' => $this->joinMeaningful([$row['nombre_grupo'] ?? null, $row['nombre_familia'] ?? null, $row['nombre_clase'] ?? null]),
            'status' => $status,
            'source_status' => $sourceStatus,
            'opening_status' => null,
            'source_url' => $this->nestedUrl($row['ruta_proceso_en_secop_i'] ?? null),
            'published_at' => $this->parseDate($row['fecha_de_cargue_en_el_secop'] ?? null),
            'last_published_at' => $this->parseDate($row['ultima_actualizacion'] ?? null),
            'deadline_at' => null,
        ];
    }

    private function meaningful(mixed $value): ?string
    {
        $text = $this->text($value);
        if ($text === null || in_array($this->normalizeText($text), [
            '-', '--', '_', 'n/a', 'na', 'no aplica', 'no definido', 'no definida',
            'no especificado', 'no especificada', 'null', 'none', 'unspecified',
        ], true)) {
            return null;
        }

        return $text;
    }

    private function text(mixed $value): ?string
    {
        if ($value === null || is_array($value)) {
            return null;
        }
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function nestedUrl(mixed $value): ?string
    {
        if (is_array($value)) {
            return $this->text($value['url'] ?? $value['uri'] ?? $value['href'] ?? null);
        }

        return $this->text($value);
    }

    private function joinMeaningful(array $values): ?string
    {
        $values = collect($values)->map(fn ($value) => $this->meaningful($value))->filter()->values()->all();

        return $values === [] ? null : implode(' | ', $values);
    }

    private function normalizeText(?string $value): string
    {
        return Str::ascii(Str::lower(trim((string) $value)));
    }
}
