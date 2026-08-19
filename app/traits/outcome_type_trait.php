<?php

namespace App\traits;

use App\Models\outcome;
use App\Models\outcome_type;
use Illuminate\Support\Str;

trait outcome_type_trait
{
    public function OutcomeType_GetTypes(): array
    {
        try {
            return [
                'status' => 1,
                'message' => 'Tipos de egreso obtenidos correctamente.',
                'data' => outcome_type::query()->orderBy('name')->get(),
            ];
        } catch (\Throwable $exception) {
            info('OutcomeType_GetTypes error: ' . $exception->getMessage());

            return [
                'status' => 0,
                'message' => 'No se pudieron obtener los tipos de egreso.',
            ];
        }
    }

    public function OutcomeType_AddType(string $name): array
    {
        $name = $this->normalizeOutcomeTypeName($name);
        if ($name === '' || $this->isGenericOutcomeTypeName($name)) {
            return [
                'status' => 0,
            'message' => 'Debes indicar un tipo de egreso específico.',
            ];
        }

        try {
            if ($this->outcomeTypeNameExists($name)) {
                return [
                    'status' => 0,
                    'message' => 'Ese tipo de egreso ya existe.',
                ];
            }

            $type = new outcome_type();
            $type->name = $name;
            $type->save();

            return [
                'status' => 1,
                'message' => 'Tipo de egreso agregado correctamente.',
                'data' => $type,
            ];
        } catch (\Throwable $exception) {
            info('OutcomeType_AddType error: ' . $exception->getMessage());

            return [
                'status' => 0,
                'message' => 'No se pudo agregar el tipo de egreso.',
            ];
        }
    }

    public function OutcomeType_UpdateType(int $id, string $name): array
    {
        $name = $this->normalizeOutcomeTypeName($name);
        if ($name === '' || $this->isGenericOutcomeTypeName($name)) {
            return [
                'status' => 0,
            'message' => 'Debes indicar un tipo de egreso específico.',
            ];
        }

        try {
            $type = outcome_type::find($id);
            if (!$type) {
                return [
                    'status' => 0,
                    'message' => 'No se encontró el tipo de egreso.',
                ];
            }

            if ($this->outcomeTypeNameExists($name, $id)) {
                return [
                    'status' => 0,
                    'message' => 'Ese tipo de egreso ya existe.',
                ];
            }

            $type->name = $name;
            $type->save();

            return [
                'status' => 1,
                'message' => 'Tipo de egreso actualizado correctamente.',
                'data' => $type,
            ];
        } catch (\Throwable $exception) {
            info('OutcomeType_UpdateType error: ' . $exception->getMessage());

            return [
                'status' => 0,
                'message' => 'No se pudo actualizar el tipo de egreso.',
            ];
        }
    }

    public function OutcomeType_DeleteType(int $id): array
    {
        try {
            $type = outcome_type::find($id);
            if (!$type) {
                return [
                    'status' => 0,
                    'message' => 'No se encontró el tipo de egreso.',
                ];
            }

            if (outcome::withTrashed()->where('outcome_type_id', $id)->exists()) {
                return [
                    'status' => 0,
                    'message' => 'No se puede eliminar un tipo que ya está asociado a egresos.',
                ];
            }

            $type->delete();

            return [
                'status' => 1,
                'message' => 'Tipo de egreso eliminado correctamente.',
                'data' => $type,
            ];
        } catch (\Throwable $exception) {
            info('OutcomeType_DeleteType error: ' . $exception->getMessage());

            return [
                'status' => 0,
                'message' => 'No se pudo eliminar el tipo de egreso.',
            ];
        }
    }

    private function normalizeOutcomeTypeName(string $name): string
    {
        return Str::squish(strip_tags($name));
    }

    private function outcomeTypeNameExists(string $name, ?int $exceptId = null): bool
    {
        $query = outcome_type::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name, 'UTF-8')]);

        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        return $query->exists();
    }

    private function isGenericOutcomeTypeName(string $name): bool
    {
        return in_array(mb_strtolower($name, 'UTF-8'), ['otro', 'sin tipo', 'pendiente'], true);
    }
}