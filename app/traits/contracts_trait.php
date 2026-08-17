<?php

namespace App\traits;

use App\Models\client;
use App\Models\contract;
use App\Models\contract_schedule;
use App\Models\contract_template;
use App\Models\contract_type;
use App\Models\department;
use App\Models\employee;
use App\Models\income;
use App\Models\license;
use App\Models\provider;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

trait contracts_trait
{
    use mail_trait;

    private $contractStatuses = [
        'draft',
        'generated',
        'sent',
        'signed',
        'expired',
        'cancelled',
    ];

    private function Contract_Response($message, $data = [], $status = 1)
    {
        return array_merge([
            'status' => $status,
            'message' => $message,
        ], $data);
    }

    private function Contract_AllowedContractables()
    {
        return [
            'client' => client::class,
            'clients' => client::class,
            'employee' => employee::class,
            'employees' => employee::class,
            'provider' => provider::class,
            'providers' => provider::class,
            client::class => client::class,
            employee::class => employee::class,
            provider::class => provider::class,
        ];
    }

    private function Contract_ResolveContractableClass($type)
    {
        $type = trim((string) $type);
        return $this->Contract_AllowedContractables()[$type] ?? null;
    }

    private function Contract_ModelUsesSoftDeletes($class)
    {
        return in_array(SoftDeletes::class, class_uses_recursive($class), true);
    }

    private function Contract_FindContractable($class, $id, $includeTrashed = false)
    {
        if (!$class || !is_numeric($id) || (int) $id < 1) {
            return null;
        }

        $query = $includeTrashed && $this->Contract_ModelUsesSoftDeletes($class)
            ? $class::withTrashed()
            : $class::query();

        return $query->find((int) $id);
    }

    private function Contract_ValidateContractable($type, $id, $includeTrashed = false)
    {
        $class = $this->Contract_ResolveContractableClass($type);
        $model = $this->Contract_FindContractable($class, $id, $includeTrashed);

        if (!$class || !$model) {
            throw new \InvalidArgumentException('La fuente asociada no existe o no esta permitida');
        }

        return [$class, $model];
    }

    private function Contract_ContractableName($model)
    {
        if (!$model) {
            return '';
        }

        $name = trim((string) ($model->name ?? ''));
        $lastName = trim((string) ($model->lastname ?? $model->last_name ?? ''));
        if ($name !== '' || $lastName !== '') {
            return trim($name.' '.$lastName);
        }

        return trim((string) ($model->complete_name ?? ''));
    }

    private function Contract_ContractableEmail($model)
    {
        if (!$model) {
            return '';
        }

        return trim((string) ($model->email ?: $model->work_email ?: $model->personal_email ?: ''));
    }

    private function Contract_Escape($value)
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    private function Contract_Date($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d');
    }

    private function Contract_DateTime($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value);
    }

    private function Contract_Boolean($value, $default = false)
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $parsed === null ? (int) $value === 1 : $parsed;
    }

    private function Contract_VariableDefinitions()
    {
        return [
            ['key' => 'contractable.id', 'label' => 'Titular: ID', 'group' => 'Titular', 'type' => 'number'],
            ['key' => 'contractable.unique_id', 'label' => 'Titular: identificador', 'group' => 'Titular', 'type' => 'text'],
            ['key' => 'contractable.name', 'label' => 'Titular: nombre', 'group' => 'Titular', 'type' => 'text'],
            ['key' => 'contractable.email', 'label' => 'Titular: correo', 'group' => 'Titular', 'type' => 'email'],
            ['key' => 'contractable.phone', 'label' => 'Titular: teléfono', 'group' => 'Titular', 'type' => 'text'],
            ['key' => 'contractable.identification', 'label' => 'Titular: identificación', 'group' => 'Titular', 'type' => 'text'],
            ['key' => 'contract.name', 'label' => 'Contrato: nombre', 'group' => 'Contrato', 'type' => 'text'],
            ['key' => 'contract.subject', 'label' => 'Contrato: asunto', 'group' => 'Contrato', 'type' => 'text'],
            ['key' => 'contract.start_date', 'label' => 'Contrato: fecha de inicio', 'group' => 'Contrato', 'type' => 'date'],
            ['key' => 'contract.end_date', 'label' => 'Contrato: fecha de vencimiento', 'group' => 'Contrato', 'type' => 'date'],
            ['key' => 'contract.type', 'label' => 'Contrato: tipo', 'group' => 'Contrato', 'type' => 'text'],
            ['key' => 'contract.unique_id', 'label' => 'Contrato: identificador', 'group' => 'Contrato', 'type' => 'text'],
            ['key' => 'contract.id', 'label' => 'Contrato: ID', 'group' => 'Contrato', 'type' => 'number'],
            ['key' => 'client.id', 'label' => 'Cliente: ID', 'group' => 'Cliente', 'type' => 'number'],
            ['key' => 'client.unique_id', 'label' => 'Cliente: identificador', 'group' => 'Cliente', 'type' => 'text'],
            ['key' => 'client.name', 'label' => 'Cliente: nombre', 'group' => 'Cliente', 'type' => 'text'],
            ['key' => 'client.lastname', 'label' => 'Cliente: apellido', 'group' => 'Cliente', 'type' => 'text'],
            ['key' => 'client.identification_type', 'label' => 'Cliente: tipo de identificación', 'group' => 'Cliente', 'type' => 'text'],
            ['key' => 'client.complete_name', 'label' => 'Cliente: nombre completo', 'group' => 'Cliente', 'type' => 'text'],
            ['key' => 'client.email', 'label' => 'Cliente: correo', 'group' => 'Cliente', 'type' => 'email'],
            ['key' => 'client.phone', 'label' => 'Cliente: teléfono', 'group' => 'Cliente', 'type' => 'text'],
            ['key' => 'client.identification', 'label' => 'Cliente: identificación', 'group' => 'Cliente', 'type' => 'text'],
            ['key' => 'client.address', 'label' => 'Cliente: dirección', 'group' => 'Cliente', 'type' => 'text'],
            ['key' => 'client.active', 'label' => 'Cliente: activo', 'group' => 'Cliente', 'type' => 'text'],
            ['key' => 'client.verified', 'label' => 'Cliente: verificado', 'group' => 'Cliente', 'type' => 'text'],
            ['key' => 'client.created_date_string', 'label' => 'Cliente: fecha de alta', 'group' => 'Cliente', 'type' => 'date'],
            ['key' => 'employee.id', 'label' => 'Empleado: ID', 'group' => 'Empleado', 'type' => 'number'],
            ['key' => 'employee.uid', 'label' => 'Empleado: identificador', 'group' => 'Empleado', 'type' => 'text'],
            ['key' => 'employee.name', 'label' => 'Empleado: nombre', 'group' => 'Empleado', 'type' => 'text'],
            ['key' => 'employee.last_name', 'label' => 'Empleado: apellido', 'group' => 'Empleado', 'type' => 'text'],
            ['key' => 'employee.complete_name', 'label' => 'Empleado: nombre completo', 'group' => 'Empleado', 'type' => 'text'],
            ['key' => 'employee.id_type', 'label' => 'Empleado: tipo de identificación', 'group' => 'Empleado', 'type' => 'text'],
            ['key' => 'employee.id_type_string', 'label' => 'Empleado: tipo de identificación', 'group' => 'Empleado', 'type' => 'text'],
            ['key' => 'employee.identification', 'label' => 'Empleado: identificación', 'group' => 'Empleado', 'type' => 'text'],
            ['key' => 'employee.phone', 'label' => 'Empleado: teléfono', 'group' => 'Empleado', 'type' => 'text'],
            ['key' => 'employee.personal_email', 'label' => 'Empleado: correo personal', 'group' => 'Empleado', 'type' => 'email'],
            ['key' => 'employee.work_email', 'label' => 'Empleado: correo laboral', 'group' => 'Empleado', 'type' => 'email'],
            ['key' => 'employee.state', 'label' => 'Empleado: estado', 'group' => 'Empleado', 'type' => 'text'],
            ['key' => 'employee.state_string', 'label' => 'Empleado: estado', 'group' => 'Empleado', 'type' => 'text'],
            ['key' => 'employee.department_id', 'label' => 'Empleado: departamento ID', 'group' => 'Empleado', 'type' => 'number'],
            ['key' => 'provider.id', 'label' => 'Proveedor: ID', 'group' => 'Proveedor', 'type' => 'number'],
            ['key' => 'provider.unique_id', 'label' => 'Proveedor: identificador', 'group' => 'Proveedor', 'type' => 'text'],
            ['key' => 'provider.name', 'label' => 'Proveedor: nombre', 'group' => 'Proveedor', 'type' => 'text'],
            ['key' => 'provider.lastname', 'label' => 'Proveedor: apellido', 'group' => 'Proveedor', 'type' => 'text'],
            ['key' => 'provider.complete_name', 'label' => 'Proveedor: nombre completo', 'group' => 'Proveedor', 'type' => 'text'],
            ['key' => 'provider.identification_type', 'label' => 'Proveedor: tipo de identificación', 'group' => 'Proveedor', 'type' => 'text'],
            ['key' => 'provider.email', 'label' => 'Proveedor: correo', 'group' => 'Proveedor', 'type' => 'email'],
            ['key' => 'provider.phone', 'label' => 'Proveedor: teléfono', 'group' => 'Proveedor', 'type' => 'text'],
            ['key' => 'provider.identification', 'label' => 'Proveedor: identificación', 'group' => 'Proveedor', 'type' => 'text'],
            ['key' => 'provider.address', 'label' => 'Proveedor: dirección', 'group' => 'Proveedor', 'type' => 'text'],
            ['key' => 'provider.description', 'label' => 'Proveedor: descripción', 'group' => 'Proveedor', 'type' => 'text'],
            ['key' => 'provider.active', 'label' => 'Proveedor: activo', 'group' => 'Proveedor', 'type' => 'text'],
            ['key' => 'provider.verified', 'label' => 'Proveedor: verificado', 'group' => 'Proveedor', 'type' => 'text'],
            ['key' => 'department.id', 'label' => 'Departamento: ID', 'group' => 'Departamento', 'type' => 'number'],
            ['key' => 'department.unique_id', 'label' => 'Departamento: identificador', 'group' => 'Departamento', 'type' => 'text'],
            ['key' => 'department.name', 'label' => 'Departamento: nombre', 'group' => 'Departamento', 'type' => 'text'],
            ['key' => 'department.budget', 'label' => 'Departamento: presupuesto', 'group' => 'Departamento', 'type' => 'number'],
            ['key' => 'department.director_id', 'label' => 'Departamento: director ID', 'group' => 'Departamento', 'type' => 'number'],
            ['key' => 'department.director_name', 'label' => 'Departamento: director', 'group' => 'Departamento', 'type' => 'text'],
            ['key' => 'license.id', 'label' => 'Licencia: ID', 'group' => 'Licencias', 'type' => 'number'],
            ['key' => 'license.unique_id', 'label' => 'Licencia: identificador', 'group' => 'Licencias', 'type' => 'text'],
            ['key' => 'license.name', 'label' => 'Licencia: nombre', 'group' => 'Licencias', 'type' => 'text'],
            ['key' => 'license.value', 'label' => 'Licencia: valor', 'group' => 'Licencias', 'type' => 'number'],
            ['key' => 'license.value_string', 'label' => 'Licencia: valor formateado', 'group' => 'Licencias', 'type' => 'text'],
            ['key' => 'license.description', 'label' => 'Licencia: descripción', 'group' => 'Licencias', 'type' => 'text'],
            ['key' => 'license.type', 'label' => 'Licencia: tipo', 'group' => 'Licencias', 'type' => 'text'],
            ['key' => 'license.type_string', 'label' => 'Licencia: tipo', 'group' => 'Licencias', 'type' => 'text'],
            ['key' => 'license.active', 'label' => 'Licencia: activa', 'group' => 'Licencias', 'type' => 'text'],
            ['key' => 'license.active_string', 'label' => 'Licencia: estado', 'group' => 'Licencias', 'type' => 'text'],
            ['key' => 'license.recurrence_months', 'label' => 'Licencia: meses de recurrencia', 'group' => 'Licencias', 'type' => 'number'],
            ['key' => 'license.billing_day', 'label' => 'Licencia: día de cobro', 'group' => 'Licencias', 'type' => 'number'],
            ['key' => 'license.days_to_expire', 'label' => 'Licencia: días para vencer', 'group' => 'Licencias', 'type' => 'number'],
            ['key' => 'license.last_billing_date', 'label' => 'Licencia: último cobro', 'group' => 'Licencias', 'type' => 'date'],
            ['key' => 'license.next_billing_date', 'label' => 'Licencia: próximo cobro', 'group' => 'Licencias', 'type' => 'date'],
            ['key' => 'license.last_payed_date', 'label' => 'Licencia: último pago', 'group' => 'Licencias', 'type' => 'date'],
            ['key' => 'license.remaining_days', 'label' => 'Licencia: días restantes', 'group' => 'Licencias', 'type' => 'number'],
            ['key' => 'licence.name', 'label' => 'Licencia: nombre', 'group' => 'Licencias', 'type' => 'text'],
            ['key' => 'licence.value', 'label' => 'Licencia: valor', 'group' => 'Licencias', 'type' => 'number'],
            ['key' => 'licence.description', 'label' => 'Licencia: descripción', 'group' => 'Licencias', 'type' => 'text'],
            ['key' => 'licenses.count', 'label' => 'Licencias: cantidad', 'group' => 'Licencias', 'type' => 'number'],
            ['key' => 'licenses.total_value', 'label' => 'Licencias: valor total', 'group' => 'Licencias', 'type' => 'number'],
            ['key' => 'licenses.names', 'label' => 'Licencias: nombres', 'group' => 'Licencias', 'type' => 'text'],
            ['key' => 'licenses.first_name', 'label' => 'Licencias: primera licencia', 'group' => 'Licencias', 'type' => 'text'],
            ['key' => 'licences.count', 'label' => 'Licencias: cantidad', 'group' => 'Licencias', 'type' => 'number'],
            ['key' => 'licences.total_value', 'label' => 'Licencias: valor total', 'group' => 'Licencias', 'type' => 'number'],
            ['key' => 'licences.names', 'label' => 'Licencias: nombres', 'group' => 'Licencias', 'type' => 'text'],
            ['key' => 'licences.first_name', 'label' => 'Licencias: primera licencia', 'group' => 'Licencias', 'type' => 'text'],
            ['key' => 'income.description', 'label' => 'Ingreso: descripción', 'group' => 'Ingresos', 'type' => 'text'],
            ['key' => 'income.total', 'label' => 'Ingreso: total', 'group' => 'Ingresos', 'type' => 'number'],
            ['key' => 'income.total_string', 'label' => 'Ingreso: total formateado', 'group' => 'Ingresos', 'type' => 'text'],
            ['key' => 'income.unique_id', 'label' => 'Ingreso: identificador', 'group' => 'Ingresos', 'type' => 'text'],
            ['key' => 'income.client_name', 'label' => 'Ingreso: cliente', 'group' => 'Ingresos', 'type' => 'text'],
            ['key' => 'income.timely_payment', 'label' => 'Ingreso: pago oportuno', 'group' => 'Ingresos', 'type' => 'date'],
            ['key' => 'income.cutoff_date', 'label' => 'Ingreso: fecha de corte', 'group' => 'Ingresos', 'type' => 'date'],
            ['key' => 'income.state_text', 'label' => 'Ingreso: estado', 'group' => 'Ingresos', 'type' => 'text'],
            ['key' => 'income.payment_state_text', 'label' => 'Ingreso: estado de pago', 'group' => 'Ingresos', 'type' => 'text'],
            ['key' => 'income.payment_date', 'label' => 'Ingreso: fecha de pago', 'group' => 'Ingresos', 'type' => 'date'],
            ['key' => 'incomes.count', 'label' => 'Ingresos: cantidad', 'group' => 'Ingresos', 'type' => 'number'],
            ['key' => 'incomes.total', 'label' => 'Ingresos: total acumulado', 'group' => 'Ingresos', 'type' => 'number'],
            ['key' => 'incomes.first_description', 'label' => 'Ingresos: último detalle', 'group' => 'Ingresos', 'type' => 'text'],
            ['key' => 'incomes.first_total', 'label' => 'Ingresos: último total', 'group' => 'Ingresos', 'type' => 'number'],
        ];
    }

    private function Contract_ValidateVariableValue($value, $type, $label)
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return;
        }
        if ($type === 'number' && !is_numeric($value)) {
            throw new \InvalidArgumentException('El valor de '.$label.' debe ser numerico');
        }
        if ($type === 'date') {
            try {
                Carbon::parse($value);
            } catch (\Exception $exception) {
                throw new \InvalidArgumentException('El valor de '.$label.' debe ser una fecha valida');
            }
        }
        if ($type === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('El valor de '.$label.' debe ser un correo valido');
        }
    }

    private function Contract_NormalizeTemplateVariables($variables)
    {
        if ($variables === null || $variables === '') {
            return [];
        }
        if (is_string($variables)) {
            $variables = json_decode($variables, true);
        }
        if (!is_array($variables)) {
            throw new \InvalidArgumentException('La definicion de variables no es valida');
        }

        $reserved = ['contractable', 'contract', 'client', 'employee', 'provider', 'department', 'license', 'licenses', 'income', 'incomes', 'custom'];
        $normalized = [];
        $keys = [];
        foreach ($variables as $variable) {
            if (!is_array($variable)) {
                continue;
            }
            $rawKey = trim((string) ($variable['key'] ?? ''));
            $rawKey = preg_replace('/^\{\{\s*|\s*\}\}$/', '', $rawKey);
            $rawKey = preg_replace('/^custom\./i', '', $rawKey);
            $rawKey = strtolower(trim((string) $rawKey));
            if ($rawKey === '') {
                continue;
            }
            $rawKey = preg_replace('/[^a-z0-9_]+/', '_', $rawKey);
            $rawKey = trim((string) $rawKey, '_');
            if (!preg_match('/^[a-z][a-z0-9_]{0,49}$/', $rawKey)) {
                throw new \InvalidArgumentException('El nombre de una variable personalizada no es valido');
            }
            if (in_array($rawKey, $reserved, true)) {
                throw new \InvalidArgumentException('El nombre de una variable personalizada esta reservado');
            }
            $key = 'custom.'.$rawKey;
            if (isset($keys[$key])) {
                throw new \InvalidArgumentException('No se pueden repetir variables personalizadas');
            }
            $type = strtolower(trim((string) ($variable['type'] ?? 'text')));
            if (!in_array($type, ['text', 'number', 'date', 'email'], true)) {
                $type = 'text';
            }
            $label = trim((string) ($variable['label'] ?? ''));
            $label = mb_substr($label !== '' ? $label : str_replace('_', ' ', $rawKey), 0, 100);
            $default = (string) ($variable['default_value'] ?? $variable['default'] ?? '');
            $this->Contract_ValidateVariableValue($default, $type, $label);
            $normalized[] = [
                'key' => $key,
                'label' => $label,
                'type' => $type,
                'default_value' => mb_substr($default, 0, 2000),
                'required' => $this->Contract_Boolean($variable['required'] ?? false),
            ];
            $keys[$key] = true;
        }

        return $normalized;
    }

    private function Contract_NormalizeCustomVariables($template, $values, $validateRequired = false)
    {
        $definitions = $this->Contract_NormalizeTemplateVariables($template->variables ?? []);
        if ($values === null || $values === '') {
            $values = [];
        }
        if (is_string($values)) {
            $values = json_decode($values, true);
        }
        if (!is_array($values)) {
            throw new \InvalidArgumentException('Los valores personalizados no son validos');
        }

        $input = [];
        foreach ($values as $key => $value) {
            $key = strtolower(trim((string) $key));
            $key = preg_replace('/^custom\./', '', $key);
            if ($key !== '') {
                $input['custom.'.$key] = is_scalar($value) || $value === null ? (string) ($value ?? '') : '';
            }
        }

        $normalized = [];
        foreach ($definitions as $definition) {
            $key = $definition['key'];
            $value = array_key_exists($key, $input) ? $input[$key] : $definition['default_value'];
            if ($validateRequired && $definition['required'] && trim((string) $value) === '') {
                throw new \InvalidArgumentException('Debe ingresar la variable '.$definition['label']);
            }
            $this->Contract_ValidateVariableValue($value, $definition['type'], $definition['label']);
            $normalized[$key] = mb_substr((string) $value, 0, 10000);
        }

        return $normalized;
    }

    private function Contract_SanitizeHtml($html)
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $allowedTags = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'blockquote', 'div', 'span', 'a', 'table', 'thead', 'tbody', 'tr', 'th', 'td'];
        $allowedAttributes = ['style', 'href', 'target', 'rel', 'colspan', 'rowspan'];
        if (!class_exists(\DOMDocument::class)) {
            $html = preg_replace('/\s+on[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
            $html = preg_replace('/(?:javascript|vbscript)\s*:/i', '', $html);
            return strip_tags($html, '<'.implode('><', $allowedTags).'>');
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><div id="contract-html-root">'.$html.'</div>', LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
        $root = (new \DOMXPath($document))->query('//*[@id="contract-html-root"]')->item(0);
        if (!$root) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            return '';
        }

        $allowedStyles = ['text-align', 'font-weight', 'font-style', 'text-decoration', 'color', 'background-color', 'font-size', 'font-family', 'line-height', 'padding', 'margin'];
        $forbiddenTags = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'meta', 'link'];
        $sanitizeNode = function ($node) use (&$sanitizeNode, $allowedTags, $allowedAttributes, $allowedStyles, $forbiddenTags) {
            for ($child = $node->firstChild; $child;) {
                $next = $child->nextSibling;
                if ($child instanceof \DOMElement) {
                    $tag = strtolower($child->tagName);
                    if (in_array($tag, $forbiddenTags, true)) {
                        $node->removeChild($child);
                        $child = $next;
                        continue;
                    }
                    if (!in_array($tag, $allowedTags, true)) {
                        while ($child->firstChild) {
                            $node->insertBefore($child->firstChild, $child);
                        }
                        $node->removeChild($child);
                        $child = $next;
                        continue;
                    }
                    for ($index = $child->attributes->length - 1; $index >= 0; $index--) {
                        $attribute = $child->attributes->item($index);
                        $name = strtolower($attribute->name);
                        if (!in_array($name, $allowedAttributes, true)) {
                            $child->removeAttribute($attribute->name);
                            continue;
                        }
                        if ($name === 'style') {
                            $styles = [];
                            foreach (explode(';', $attribute->value) as $declaration) {
                                $parts = explode(':', $declaration, 2);
                                $property = strtolower(trim($parts[0] ?? ''));
                                $value = trim($parts[1] ?? '');
                                if ($property === '' || $value === '' || !in_array($property, $allowedStyles, true) || preg_match('/url\s*\(|expression\s*\(|javascript\s*:|vbscript\s*:|[<>]/i', $value)) {
                                    continue;
                                }
                                $styles[] = $property.': '.$value;
                            }
                            if ($styles) {
                                $child->setAttribute('style', implode('; ', $styles));
                            } else {
                                $child->removeAttribute('style');
                            }
                        }
                        if ($name === 'href' && !preg_match('/^(https?:|mailto:|\/|#)/i', trim($attribute->value))) {
                            $child->removeAttribute('href');
                        }
                    }
                    $sanitizeNode($child);
                }
                $child = $next;
            }
        };
        $sanitizeNode($root);

        $result = '';
        for ($child = $root->firstChild; $child; $child = $child->nextSibling) {
            $result .= $document->saveHTML($child);
        }
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        return trim($result);
    }

    private function Contract_TemplateUsesPrefix($template, $prefix)
    {
        $source = (string) ($template->subject ?? '')."\n".(string) ($template->content ?? '');
        return preg_match('/\{\{\s*'.preg_quote($prefix, '/').'(?:\.|\}\})/i', $source) === 1;
    }

    private function Contract_ModelData($model, array $fields)
    {
        $data = [];
        foreach ($fields as $field) {
            $value = null;
            if ($model) {
                $value = method_exists($model, 'getAttribute') ? $model->getAttribute($field) : ($model->{$field} ?? null);
            }
            if ($value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d');
            }
            if (is_scalar($value) || $value === null) {
                $data[$field] = $value;
            }
        }
        return $data;
    }

    private function Contract_CollectionData(array $rows, $totalKey, $totalValue)
    {
        $context = [
            'count' => count($rows),
            $totalKey => $totalValue,
        ];
        foreach (array_values($rows) as $index => $row) {
            $context[$index] = $row;
        }
        return $context;
    }

    private function Contract_BuildTemplateContext($template, $contractable, $name, $subject, $startDate, $endDate, $contract = null)
    {
        $contractableName = $this->Contract_ContractableName($contractable);
        $contractableEmail = $this->Contract_ContractableEmail($contractable);
        $contractableData = $this->Contract_ModelData($contractable, [
            'id', 'unique_id', 'name', 'lastname', 'last_name', 'email', 'work_email', 'personal_email', 'phone', 'identification', 'address',
        ]);
        $contractableData['name'] = $contractableName;
        $contractableData['email'] = $contractableEmail;

        $typeName = $template->type->name ?? '';
        $context = [
            'contractable' => $contractableData,
            'contract' => [
                'name' => $name,
                'subject' => $subject ?: ($template->subject ?? ''),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'type' => $typeName,
                'unique_id' => $contract->unique_id ?? '',
                'id' => $contract->id ?? '',
            ],
            'client' => [],
            'employee' => [],
            'provider' => [],
            'department' => [],
            'license' => [],
            'licenses' => ['count' => 0, 'total_value' => 0, 'names' => '', 'first_name' => ''],
            'income' => [],
            'incomes' => ['count' => 0, 'total' => 0, 'first_description' => '', 'first_total' => ''],
        ];

        $licenseModels = [];
        $incomeModels = [];
        if ($contractable instanceof client) {
            $context['client'] = $this->Contract_ModelData($contractable, [
                'id', 'unique_id', 'name', 'lastname', 'last_name', 'complete_name', 'identification_type', 'identification', 'email', 'phone', 'address', 'active', 'verified', 'created_at_string', 'created_date_string',
            ]);
            $context['client']['complete_name'] = $contractableName;
            if ($this->Contract_TemplateUsesPrefix($template, 'license') || $this->Contract_TemplateUsesPrefix($template, 'licence') || $this->Contract_TemplateUsesPrefix($template, 'licenses') || $this->Contract_TemplateUsesPrefix($template, 'licences')) {
                $licenseModels = license::where('client_id', $contractable->id)->orderBy('name')->get();
            }
            if ($this->Contract_TemplateUsesPrefix($template, 'income') || $this->Contract_TemplateUsesPrefix($template, 'incomes')) {
                $incomeModels = income::where('client_id', $contractable->id)->orderByDesc('id')->get();
            }
        } elseif ($contractable instanceof employee) {
            $context['employee'] = $this->Contract_ModelData($contractable, [
                'id', 'uid', 'name', 'last_name', 'complete_name', 'id_type', 'id_type_string', 'identification', 'phone', 'personal_email', 'work_email', 'state', 'state_string', 'department_id', 'created_at',
            ]);
            $context['employee']['complete_name'] = $contractableName;
            if ($this->Contract_TemplateUsesPrefix($template, 'license') || $this->Contract_TemplateUsesPrefix($template, 'licence') || $this->Contract_TemplateUsesPrefix($template, 'licenses') || $this->Contract_TemplateUsesPrefix($template, 'licences')) {
                $licenseModels = license::where('employee_id', $contractable->id)->orderBy('name')->get();
            }
            if ($this->Contract_TemplateUsesPrefix($template, 'department') && $contractable->department_id) {
                $departmentModel = department::find($contractable->department_id);
                if ($departmentModel) {
                    $context['department'] = $this->Contract_ModelData($departmentModel, ['id', 'unique_id', 'name', 'budget', 'director_id']);
                    $director = $departmentModel->director_id ? employee::find($departmentModel->director_id) : null;
                    $context['department']['director_name'] = $this->Contract_ContractableName($director);
                }
            }
        } elseif ($contractable instanceof provider) {
            $context['provider'] = $this->Contract_ModelData($contractable, [
                'id', 'unique_id', 'name', 'lastname', 'last_name', 'complete_name', 'email', 'phone', 'identification_type', 'identification', 'address', 'description', 'active', 'verified', 'created_at',
            ]);
            $context['provider']['complete_name'] = $contractableName;
        }

        $licenseRows = [];
        foreach ($licenseModels as $licenseModel) {
            $licenseRows[] = $this->Contract_ModelData($licenseModel, [
                'id', 'unique_id', 'name', 'value', 'value_string', 'description', 'type', 'type_string', 'active', 'active_string', 'recurrence_months', 'billing_day', 'days_to_expire', 'last_billing_date', 'next_billing_date', 'last_payed_date', 'remaining_days', 'client_id', 'employee_id',
            ]);
        }
        $firstLicense = $licenseRows[0] ?? [];
        $context['license'] = $firstLicense;
        $context['licenses'] = $this->Contract_CollectionData(
            $licenseRows,
            'total_value',
            array_sum(array_map(function ($row) { return (float) ($row['value'] ?? 0); }, $licenseRows))
        );
        $context['licenses']['names'] = implode(', ', array_filter(array_map(function ($row) { return $row['name'] ?? ''; }, $licenseRows)));
        $context['licenses']['first_name'] = $firstLicense['name'] ?? '';
        $context['licence'] = $context['license'];
        $context['licences'] = $context['licenses'];

        $incomeRows = [];
        foreach ($incomeModels as $incomeModel) {
            $incomeRows[] = $this->Contract_ModelData($incomeModel, [
                'id', 'unique_id', 'client_identification', 'client_name', 'timely_payment', 'cutoff_date', 'description', 'total', 'total_string', 'state', 'state_text', 'payment_state', 'payment_state_text', 'payment_date', 'payment_reference', 'bill_name', 'bill_final_value', 'bill_final_value_string', 'created_at', 'created_at_string',
            ]);
        }
        $firstIncome = $incomeRows[0] ?? [];
        $context['income'] = $firstIncome;
        $context['incomes'] = $this->Contract_CollectionData(
            $incomeRows,
            'total',
            array_sum(array_map(function ($row) { return (float) ($row['total'] ?? 0); }, $incomeRows))
        );
        $context['incomes']['first_description'] = $firstIncome['description'] ?? '';
        $context['incomes']['first_total'] = $firstIncome['total'] ?? '';

        return $context;
    }

    private function Contract_ContextValue(array $context, $path)
    {
        $value = $context;
        foreach (explode('.', (string) $path) as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } elseif (is_object($value) && isset($value->{$segment})) {
                $value = $value->{$segment};
            } else {
                return [false, null];
            }
        }
        return [true, $value];
    }

    private function Contract_StringifyVariableValue($value)
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        if (is_array($value)) {
            return implode(', ', array_filter(array_map(function ($item) {
                return is_scalar($item) ? (string) $item : '';
            }, $value)));
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        return (string) ($value ?? '');
    }

    private function Contract_RenderTemplate($template, $contractable, $name, $subject, $startDate, $endDate, array $customVariables = [], $contract = null)
    {
        $contractableName = $this->Contract_ContractableName($contractable);
        $contractableEmail = $this->Contract_ContractableEmail($contractable);
        $customVariables = $this->Contract_NormalizeCustomVariables($template, $customVariables, true);
        $context = $this->Contract_BuildTemplateContext($template, $contractable, $name, $subject, $startDate, $endDate, $contract);
        $customContext = [];
        foreach ($customVariables as $key => $value) {
            $customContext[substr($key, strlen('custom.'))] = $value;
        }
        $context['custom'] = $customContext;

        $knownKeys = array_fill_keys(array_map(function ($definition) { return $definition['key']; }, $this->Contract_VariableDefinitions()), true);
        foreach ($this->Contract_NormalizeTemplateVariables($template->variables ?? []) as $definition) {
            $knownKeys[$definition['key']] = true;
        }
        $render = function ($value) use ($context, $knownKeys) {
            return preg_replace_callback('/\{\{\s*([a-zA-Z][a-zA-Z0-9_.]*)\s*\}\}/', function ($matches) use ($context, $knownKeys) {
                $key = strtolower($matches[1]);
                [$found, $resolved] = $this->Contract_ContextValue($context, $key);
                if (!$found && isset($knownKeys[$key])) {
                    $found = true;
                    $resolved = '';
                }
                return $found ? $this->Contract_Escape($this->Contract_StringifyVariableValue($resolved)) : $matches[0];
            }, (string) $value);
        };

        $templateContent = $this->Contract_SanitizeHtml($template->content ?? '');
        $effectiveSubject = $subject ?: ($template->subject ?? '');
        return [
            'subject' => $render($effectiveSubject),
            'content' => $render($templateContent),
            'data' => [
                'contractable_name' => $contractableName,
                'contractable_email' => $contractableEmail,
                'contract_name' => $name,
                'contract_subject' => $effectiveSubject,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'template_id' => $template->id,
                'template_version' => $template->version,
                'custom_variables' => $customVariables,
            ],
        ];
    }

    private function Contract_ApplyContractData(contract $contract, array $data, $forceGenerate = null)
    {
        $typeId = array_key_exists('contract_type_id', $data)
            ? $data['contract_type_id']
            : $contract->contract_type_id;
        $type = contract_type::withTrashed()->find($typeId);
        if (!$type || $type->deleted_at !== null) {
            throw new \InvalidArgumentException('El tipo de contrato no existe');
        }

        $templateId = array_key_exists('contract_template_id', $data)
            ? ($data['contract_template_id'] ?: null)
            : $contract->contract_template_id;
        $template = $templateId ? contract_template::withTrashed()->with('type')->find($templateId) : null;
        if ($template && ($template->deleted_at !== null || (int) $template->contract_type_id !== (int) $type->id)) {
            throw new \InvalidArgumentException('La plantilla no pertenece al tipo de contrato seleccionado');
        }

        $contractableType = array_key_exists('contractable_type', $data)
            ? $data['contractable_type']
            : $contract->contractable_type;
        $contractableId = array_key_exists('contractable_id', $data)
            ? $data['contractable_id']
            : $contract->contractable_id;
        [$contractableClass, $contractable] = $this->Contract_ValidateContractable($contractableType, $contractableId);

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $name = $type->name.' - '.$this->Contract_ContractableName($contractable);
        }

        $startDate = $this->Contract_Date($data['start_date'] ?? $contract->start_date);
        $endDate = $this->Contract_Date($data['end_date'] ?? $contract->end_date);
        if ($startDate && $endDate && $endDate < $startDate) {
            throw new \InvalidArgumentException('La fecha final no puede ser anterior a la fecha inicial');
        }

        $subject = trim((string) ($data['subject'] ?? ''));
        if ($subject === '' && $template) {
            $subject = $template->subject;
        }
        if ($subject === '') {
            throw new \InvalidArgumentException('Debe ingresar el asunto del contrato');
        }

        $shouldGenerate = $forceGenerate !== null
            ? $forceGenerate
            : ($template !== null && ($data['generate'] ?? '1') !== '0');
        $content = array_key_exists('content', $data) ? (string) $data['content'] : (string) ($contract->content ?? '');
        $generationData = is_array($contract->generation_data) ? $contract->generation_data : [];
        $customVariables = [];
        if ($template) {
            $rawCustomVariables = array_key_exists('custom_variables', $data)
                ? $data['custom_variables']
                : data_get($generationData, 'custom_variables', []);
            $customVariables = $this->Contract_NormalizeCustomVariables($template, $rawCustomVariables);
            $generationData['custom_variables'] = $customVariables;
        }
        if ($shouldGenerate) {
            if (!$template) {
                throw new \InvalidArgumentException('Debe seleccionar una plantilla para generar el contrato');
            }
            $rendered = $this->Contract_RenderTemplate($template, $contractable, $name, $subject, $startDate, $endDate, $customVariables, $contract);
            $subject = $rendered['subject'];
            $content = $rendered['content'];
            $generationData = $rendered['data'];
            $contract->generated_at = now();
        } else {
            $content = $this->Contract_SanitizeHtml($content);
        }
        if (trim(strip_tags($content)) === '') {
            throw new \InvalidArgumentException('Debe ingresar el contenido del contrato');
        }

        $status = $data['status'] ?? ($contract->status ?: ($shouldGenerate ? 'generated' : 'draft'));
        if (!in_array($status, $this->contractStatuses, true)) {
            throw new \InvalidArgumentException('El estado del contrato no es valido');
        }
        if ($shouldGenerate && (!array_key_exists('status', $data) || $status === 'draft')) {
            $status = 'generated';
        }

        $contract->contract_type_id = $type->id;
        $contract->contract_template_id = $template?->id;
        $contract->contractable_type = $contractableClass;
        $contract->contractable_id = $contractable->id;
        $contract->name = $name;
        $contract->subject = $subject;
        $contract->content = $content;
        $contract->status = $status;
        $contract->start_date = $startDate;
        $contract->end_date = $endDate;
        $contract->notes = $data['notes'] ?? $contract->notes;
        $contract->generation_data = $generationData;

        return $contract;
    }

    public function Contract_GetCatalogs()
    {
        try {
            $clients = client::where('active', 1)->orderBy('name')->get(['id', 'name', 'lastname', 'email', 'phone']);
            $employees = employee::where('state', 1)->orderBy('name')->get(['id', 'name', 'last_name', 'work_email', 'personal_email', 'phone']);
            $providers = provider::where('active', 1)->orderBy('name')->get(['id', 'name', 'lastname', 'email', 'phone']);
            $types = contract_type::where('active', 1)->orderBy('name')->get(['id', 'name']);
            $templates = contract_template::where('active', 1)->orderBy('name')->get(['id', 'contract_type_id', 'name', 'subject', 'version', 'variables']);
            $variables = $this->Contract_VariableDefinitions();

            return $this->Contract_Response('Catalogos obtenidos', compact('clients', 'employees', 'providers', 'types', 'templates', 'variables'));
        } catch (\Exception $e) {
            info('Contract_GetCatalogs error: '.$e->getMessage());
            return $this->Contract_Response($e->getMessage(), [], 0);
        }
    }

    public function Contract_GetPage($pagination = [], $search = null, $typeId = null, $status = null, $contractableType = null)
    {
        try {
            $page = max(1, (int) data_get($pagination, 'page', 1));
            $perPage = min(50, max(5, (int) data_get($pagination, 'per_page', 10)));
            $query = contract::with(['type', 'template', 'contractable'])->orderByDesc('id');
            $search = trim((string) $search);

            if ($search !== '') {
                $query->where(function ($builder) use ($search) {
                    $builder->where('unique_id', 'like', '%'.$search.'%')
                        ->orWhere('name', 'like', '%'.$search.'%')
                        ->orWhere('subject', 'like', '%'.$search.'%');
                });
            }
            if ($typeId) {
                $query->where('contract_type_id', $typeId);
            }
            if ($status) {
                $query->where('status', $status);
            }
            if ($contractableType) {
                $class = $this->Contract_ResolveContractableClass($contractableType);
                if ($class) {
                    $query->where('contractable_type', $class);
                }
            }

            $contracts = $query->paginate($perPage, ['*'], 'page', $page);
            return $this->Contract_Response('Contratos obtenidos', [
                'contracts' => $contracts->items(),
                'pagination' => [
                    'page' => $contracts->currentPage(),
                    'per_page' => $contracts->perPage(),
                    'total' => $contracts->total(),
                    'totalPages' => $contracts->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            info('Contract_GetPage error: '.$e->getMessage());
            return $this->Contract_Response($e->getMessage(), [], 0);
        }
    }

    public function Contract_GetById($id)
    {
        try {
            $contract = contract::withTrashed()
                ->with(['type', 'template', 'contractable'])
                ->find($id);
            if (!$contract) {
                return $this->Contract_Response('El contrato no existe', [], 0);
            }
            return $this->Contract_Response('Contrato obtenido', ['contract' => $contract]);
        } catch (\Exception $e) {
            info('Contract_GetById error: '.$e->getMessage());
            return $this->Contract_Response($e->getMessage(), [], 0);
        }
    }

    public function Contract_CreateContract(array $data, $scheduleId = null, $scheduledFor = null, $scheduleKey = null)
    {
        try {
            $contract = new contract();
            $contract->unique_id = strtoupper(Str::uuid()->toString());
            $this->Contract_ApplyContractData($contract, $data, $data['force_generate'] ?? null);
            $contract->schedule_id = $scheduleId;
            $contract->scheduled_for = $scheduledFor;
            $contract->schedule_key = $scheduleKey;
            $contract->save();
            $contract->load(['type', 'template', 'contractable']);
            return $this->Contract_Response('Contrato creado', ['contract' => $contract]);
        } catch (\Exception $e) {
            info('Contract_CreateContract error: '.$e->getMessage());
            return $this->Contract_Response($e->getMessage(), [], 0);
        }
    }

    public function Contract_UpdateContract($id, array $data)
    {
        try {
            $contract = contract::find($id);
            if (!$contract) {
                return $this->Contract_Response('El contrato no existe', [], 0);
            }
            $this->Contract_ApplyContractData($contract, $data, $data['force_generate'] ?? null);
            $contract->save();
            $contract->load(['type', 'template', 'contractable']);
            return $this->Contract_Response('Contrato actualizado', ['contract' => $contract]);
        } catch (\Exception $e) {
            info('Contract_UpdateContract error: '.$e->getMessage());
            return $this->Contract_Response($e->getMessage(), [], 0);
        }
    }

    public function Contract_DeleteContract($id)
    {
        try {
            $contract = contract::find($id);
            if (!$contract) {
                return $this->Contract_Response('El contrato no existe', [], 0);
            }
            $contract->delete();
            return $this->Contract_Response('Contrato eliminado', ['contract' => $contract]);
        } catch (\Exception $e) {
            info('Contract_DeleteContract error: '.$e->getMessage());
            return $this->Contract_Response($e->getMessage(), [], 0);
        }
    }

    public function Contract_RestoreContract($id)
    {
        try {
            $contract = contract::withTrashed()->find($id);
            if (!$contract) {
                return $this->Contract_Response('El contrato no existe', [], 0);
            }
            $contract->restore();
            return $this->Contract_Response('Contrato restaurado', ['contract' => $contract]);
        } catch (\Exception $e) {
            info('Contract_RestoreContract error: '.$e->getMessage());
            return $this->Contract_Response($e->getMessage(), [], 0);
        }
    }

    public function Contract_GenerateContract($id)
    {
        try {
            $contract = contract::find($id);
            if (!$contract) {
                return $this->Contract_Response('El contrato no existe', [], 0);
            }
            $data = [
                'contract_type_id' => $contract->contract_type_id,
                'contract_template_id' => $contract->contract_template_id,
                'contractable_type' => $contract->contractable_type,
                'contractable_id' => $contract->contractable_id,
                'name' => $contract->name,
                'subject' => $contract->subject,
                'start_date' => $contract->start_date,
                'end_date' => $contract->end_date,
                'notes' => $contract->notes,
                'force_generate' => true,
                'status' => 'generated',
            ];
            $this->Contract_ApplyContractData($contract, $data, true);
            $contract->save();
            $contract->load(['type', 'template', 'contractable']);
            return $this->Contract_Response('Contrato generado', ['contract' => $contract]);
        } catch (\Exception $e) {
            info('Contract_GenerateContract error: '.$e->getMessage());
            return $this->Contract_Response($e->getMessage(), [], 0);
        }
    }

    public function Contract_SendContract($id)
    {
        try {
            $contract = contract::with(['type', 'template', 'contractable'])->find($id);
            if (!$contract) {
                return $this->Contract_Response('El contrato no existe', [], 0);
            }
            if (trim((string) $contract->content) === '') {
                return $this->Contract_Response('Debe generar el contenido antes de enviar el contrato', [], 0);
            }
            $email = $this->Contract_ContractableEmail($contract->contractable);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->Contract_Response('La fuente asociada no tiene un correo valido', [], 0);
            }

            $mailResponse = $this->SendMail(
                ['subject' => $contract->subject],
                [['address' => $email, 'name' => $this->Contract_ContractableName($contract->contractable)]],
                'emails.contract',
                [
                    'contract' => [
                        'unique_id' => $contract->unique_id,
                        'name' => $contract->name,
                        'subject' => $contract->subject,
                        'content' => $contract->content,
                    ],
                    'recipient_name' => $this->Contract_ContractableName($contract->contractable),
                ],
                null,
                null
            );
            if ($mailResponse['status'] != 1) {
                return $this->Contract_Response($mailResponse['message'], [], 0);
            }

            $contract->status = 'sent';
            $contract->sent_at = now();
            $contract->save();
            return $this->Contract_Response('Contrato enviado', ['contract' => $contract]);
        } catch (\Exception $e) {
            info('Contract_SendContract error: '.$e->getMessage());
            return $this->Contract_Response($e->getMessage(), [], 0);
        }
    }

    public function Contract_GetAssociated($type, $id)
    {
        try {
            [$class, $model] = $this->Contract_ValidateContractable($type, $id, true);
            $contracts = contract::withTrashed()
                ->with(['type', 'template'])
                ->where('contractable_type', $class)
                ->where('contractable_id', $model->id)
                ->orderByDesc('id')
                ->get();
            return $this->Contract_Response('Contratos asociados obtenidos', ['contracts' => $contracts]);
        } catch (\Exception $e) {
            info('Contract_GetAssociated error: '.$e->getMessage());
            return $this->Contract_Response($e->getMessage(), [], 0);
        }
    }

    public function Contract_GetTypes()
    {
        try {
            $types = contract_type::withTrashed()
                ->withCount(['templates', 'contracts'])
                ->orderBy('name')
                ->get();
            return $this->Contract_Response('Tipos obtenidos', ['types' => $types]);
        } catch (\Exception $e) {
            info('Contract_GetTypes error: '.$e->getMessage());
            return $this->Contract_Response($e->getMessage(), [], 0);
        }
    }

    public function Contract_CreateType($name, $description, $active = 1)
    {
        try {
            $name = trim((string) $name);
            if ($name === '') {
                throw new \InvalidArgumentException('Debe ingresar el nombre del tipo');
            }
            if (contract_type::withTrashed()->where('name', $name)->exists()) {
                throw new \InvalidArgumentException('El tipo de contrato ya existe');
            }
            $type = contract_type::create([
                'name' => $name,
                'description' => $description,
                'active' => $this->Contract_Boolean($active, true),
            ]);
            return $this->Contract_Response('Tipo creado', ['type' => $type]);
        } catch (\Exception $e) {
            info('Contract_CreateType error: '.$e->getMessage());
            return $this->Contract_Response($e->getMessage(), [], 0);
        }
    }

    public function Contract_UpdateType($id, $name, $description, $active = 1)
    {
        try {
            $type = contract_type::find($id);
            if (!$type) {
                return $this->Contract_Response('El tipo de contrato no existe', [], 0);
            }
            $name = trim((string) $name);
            if ($name === '') {
                throw new \InvalidArgumentException('Debe ingresar el nombre del tipo');
            }
            if (contract_type::where('name', $name)->where('id', '!=', $id)->exists()) {
                throw new \InvalidArgumentException('El tipo de contrato ya existe');
            }
            $type->name = $name;
            $type->description = $description;
            $type->active = $this->Contract_Boolean($active, true);
            $type->save();
            return $this->Contract_Response('Tipo actualizado', ['type' => $type]);
        } catch (\Exception $e) {
            info('Contract_UpdateType error: '.$e->getMessage());
            return $this->Contract_Response($e->getMessage(), [], 0);
        }
    }

    public function Contract_DeleteType($id)
    {
        try {
            $type = contract_type::find($id);
            if (!$type) {
                return $this->Contract_Response('El tipo de contrato no existe', [], 0);
            }
            $type->delete();
            return $this->Contract_Response('Tipo eliminado', ['type' => $type]);
        } catch (\Exception $e) {
            info('Contract_DeleteType error: '.$e->getMessage());
            return $this->Contract_Response($e->getMessage(), [], 0);
        }
    }

    public function Contract_RestoreType($id)
    {
        try {
            $type = contract_type::withTrashed()->find($id);
            if (!$type) {
                return $this->Contract_Response('El tipo de contrato no existe', [], 0);
            }
            $type->restore();
            return $this->Contract_Response('Tipo restaurado', ['type' => $type]);
        } catch (\Exception $e) {
            info('Contract_RestoreType error: '.$e->getMessage());
            return $this->Contract_Response($e->getMessage(), [], 0);
        }
    }

    public function Contract_GetTemplates($typeId = null)
    {
        try {
            $query = contract_template::withTrashed()->with('type')->orderBy('name');
            if ($typeId) {
                $query->where('contract_type_id', $typeId);
            }
            return $this->Contract_Response('Plantillas obtenidas', ['templates' => $query->get()]);
        } catch (\Exception $e) {
            info('Contract_GetTemplates error: '.$e->getMessage());
            return $this->Contract_Response($e->getMessage(), [], 0);
        }
    }

    public function Contract_CreateTemplate($typeId, $name, $subject, $content, $active = 1, $variables = [])
    {
        try {
            $type = contract_type::find($typeId);
            if (!$type) {
                throw new \InvalidArgumentException('El tipo de contrato no existe');
            }
            $name = trim((string) $name);
            $subject = trim((string) $subject);
            $content = $this->Contract_SanitizeHtml($content);
            $variables = $this->Contract_NormalizeTemplateVariables($variables);
            if ($name === '' || $subject === '' || trim($content) === '') {
                throw new \InvalidArgumentException('Nombre, asunto y contenido son obligatorios');
            }
            $version = ((int) contract_template::withTrashed()->where('contract_type_id', $type->id)->max('version')) + 1;
            $template = contract_template::create([
                'contract_type_id' => $type->id,
                'name' => $name,
                'subject' => $subject,
                'content' => $content,
                'variables' => $variables,
                'version' => $version,
                'active' => $this->Contract_Boolean($active, true),
            ]);
            $template->load('type');
            return $this->Contract_Response('Plantilla creada', ['template' => $template]);
        } catch (\Exception $e) {
            info('Contract_CreateTemplate error: '.$e->getMessage());
            return $this->Contract_Response($e->getMessage(), [], 0);
        }
    }

    public function Contract_UpdateTemplate($id, $typeId, $name, $subject, $content, $active = 1, $variables = [])
    {
        try {
            $template = contract_template::find($id);
            $type = contract_type::find($typeId);
            if (!$template || !$type) {
                return $this->Contract_Response('La plantilla o el tipo no existe', [], 0);
            }
            $name = trim((string) $name);
            $subject = trim((string) $subject);
            $content = $this->Contract_SanitizeHtml($content);
            $variables = $this->Contract_NormalizeTemplateVariables($variables);
            if ($name === '' || $subject === '' || trim($content) === '') {
                throw new \InvalidArgumentException('Nombre, asunto y contenido son obligatorios');
            }
            $typeChanged = (int) $template->contract_type_id !== (int) $type->id;
            $contentChanged = $template->name !== $name
                || $template->subject !== $subject
                || $template->content !== $content
                || json_encode($this->Contract_NormalizeTemplateVariables($template->variables ?? [])) !== json_encode($variables);
            if ($typeChanged) {
                $template->version = ((int) contract_template::withTrashed()->where('contract_type_id', $type->id)->max('version')) + 1;
            } elseif ($contentChanged) {
                $template->version = ((int) $template->version) + 1;
            }
            $template->contract_type_id = $type->id;
            $template->name = $name;
            $template->subject = $subject;
            $template->content = $content;
            $template->variables = $variables;
            $template->active = $this->Contract_Boolean($active, true);
            $template->save();
            $template->load('type');
            return $this->Contract_Response('Plantilla actualizada', ['template' => $template]);
        } catch (\Exception $e) {
            info('Contract_UpdateTemplate error: '.$e->getMessage());
            return $this->Contract_Response($e->getMessage(), [], 0);
        }
    }

    public function Contract_DeleteTemplate($id)
    {
        try {
            $template = contract_template::find($id);
            if (!$template) {
                return $this->Contract_Response('La plantilla no existe', [], 0);
            }
            $template->delete();
            return $this->Contract_Response('Plantilla eliminada', ['template' => $template]);
        } catch (\Exception $e) {
            info('Contract_DeleteTemplate error: '.$e->getMessage());
            return $this->Contract_Response($e->getMessage(), [], 0);
        }
    }

    public function Contract_RestoreTemplate($id)
    {
        try {
            $template = contract_template::withTrashed()->find($id);
            if (!$template) {
                return $this->Contract_Response('La plantilla no existe', [], 0);
            }
            $template->restore();
            return $this->Contract_Response('Plantilla restaurada', ['template' => $template]);
        } catch (\Exception $e) {
            info('Contract_RestoreTemplate error: '.$e->getMessage());
            return $this->Contract_Response($e->getMessage(), [], 0);
        }
    }

    private function Contract_ValidateSchedule(array $data, $schedule = null)
    {
        $typeId = array_key_exists('contract_type_id', $data) ? $data['contract_type_id'] : $schedule->contract_type_id;
        $templateId = array_key_exists('contract_template_id', $data) ? $data['contract_template_id'] : $schedule->contract_template_id;
        $type = contract_type::find($typeId);
        $template = contract_template::find($templateId);
        if (!$type || !$template || (int) $template->contract_type_id !== (int) $type->id) {
            throw new \InvalidArgumentException('El tipo y la plantilla de la programacion no son validos');
        }

        $contractableType = array_key_exists('contractable_type', $data) ? $data['contractable_type'] : $schedule->contractable_type;
        $contractableId = array_key_exists('contractable_id', $data) ? ($data['contractable_id'] ?: null) : $schedule->contractable_id;
        $class = $this->Contract_ResolveContractableClass($contractableType);
        if (!$class) {
            throw new \InvalidArgumentException('La fuente de la programacion no es valida');
        }
        if ($contractableId !== null && !$this->Contract_FindContractable($class, $contractableId)) {
            throw new \InvalidArgumentException('El titular de la programacion no existe');
        }

        $frequency = strtolower(trim((string) (array_key_exists('frequency', $data) ? $data['frequency'] : $schedule->frequency)));
        if (!in_array($frequency, ['daily', 'weekly', 'monthly', 'yearly'], true)) {
            throw new \InvalidArgumentException('La frecuencia no es valida');
        }
        $interval = max(1, (int) (array_key_exists('interval_value', $data) ? $data['interval_value'] : $schedule->interval_value));
        $nextRunAt = $this->Contract_DateTime(array_key_exists('next_run_at', $data) ? $data['next_run_at'] : $schedule->next_run_at);
        $endsAt = $this->Contract_DateTime(array_key_exists('ends_at', $data) ? $data['ends_at'] : $schedule->ends_at);
        if (!$nextRunAt || ($endsAt && $endsAt->lt($nextRunAt))) {
            throw new \InvalidArgumentException('La proxima ejecucion y el limite de la programacion no son validos');
        }

        return [
            'contract_type_id' => $type->id,
            'contract_template_id' => $template->id,
            'contractable_type' => $class,
            'contractable_id' => $contractableId,
            'name' => trim((string) (array_key_exists('name', $data) ? $data['name'] : $schedule->name)),
            'frequency' => $frequency,
            'interval_value' => $interval,
            'next_run_at' => $nextRunAt,
            'ends_at' => $endsAt,
            'send_automatically' => $this->Contract_Boolean(array_key_exists('send_automatically', $data) ? $data['send_automatically'] : $schedule->send_automatically, true),
            'active' => $this->Contract_Boolean(array_key_exists('active', $data) ? $data['active'] : $schedule->active, true),
        ];
    }

    public function Contract_GetSchedules()
    {
        try {
            $schedules = contract_schedule::withTrashed()
                ->with(['type', 'template', 'contractable'])
                ->orderBy('next_run_at')
                ->get()
                ->each(function ($schedule) {
                    $schedule->contractable_name = $schedule->contractable
                        ? $this->Contract_ContractableName($schedule->contractable)
                        : 'Todos';
                });
            return $this->Contract_Response('Programaciones obtenidas', ['schedules' => $schedules]);
        } catch (\Exception $e) {
            info('Contract_GetSchedules error: '.$e->getMessage());
            return $this->Contract_Response($e->getMessage(), [], 0);
        }
    }

    public function Contract_CreateSchedule(array $data)
    {
        try {
            $schedule = new contract_schedule();
            $values = $this->Contract_ValidateSchedule($data, $schedule);
            if ($values['name'] === '') {
                throw new \InvalidArgumentException('Debe ingresar el nombre de la programacion');
            }
            $schedule->fill($values);
            $schedule->save();
            $schedule->load(['type', 'template', 'contractable']);
            return $this->Contract_Response('Programacion creada', ['schedule' => $schedule]);
        } catch (\Exception $e) {
            info('Contract_CreateSchedule error: '.$e->getMessage());
            return $this->Contract_Response($e->getMessage(), [], 0);
        }
    }

    public function Contract_UpdateSchedule($id, array $data)
    {
        try {
            $schedule = contract_schedule::find($id);
            if (!$schedule) {
                return $this->Contract_Response('La programacion no existe', [], 0);
            }
            $values = $this->Contract_ValidateSchedule($data, $schedule);
            if ($values['name'] === '') {
                throw new \InvalidArgumentException('Debe ingresar el nombre de la programacion');
            }
            $schedule->fill($values);
            $schedule->save();
            $schedule->load(['type', 'template', 'contractable']);
            return $this->Contract_Response('Programacion actualizada', ['schedule' => $schedule]);
        } catch (\Exception $e) {
            info('Contract_UpdateSchedule error: '.$e->getMessage());
            return $this->Contract_Response($e->getMessage(), [], 0);
        }
    }

    public function Contract_DeleteSchedule($id)
    {
        try {
            $schedule = contract_schedule::find($id);
            if (!$schedule) {
                return $this->Contract_Response('La programacion no existe', [], 0);
            }
            $schedule->delete();
            return $this->Contract_Response('Programacion eliminada', ['schedule' => $schedule]);
        } catch (\Exception $e) {
            info('Contract_DeleteSchedule error: '.$e->getMessage());
            return $this->Contract_Response($e->getMessage(), [], 0);
        }
    }

    public function Contract_RestoreSchedule($id)
    {
        try {
            $schedule = contract_schedule::withTrashed()->find($id);
            if (!$schedule) {
                return $this->Contract_Response('La programacion no existe', [], 0);
            }
            $schedule->restore();
            return $this->Contract_Response('Programacion restaurada', ['schedule' => $schedule]);
        } catch (\Exception $e) {
            info('Contract_RestoreSchedule error: '.$e->getMessage());
            return $this->Contract_Response($e->getMessage(), [], 0);
        }
    }

    private function Contract_NextRun(Carbon $runAt, $frequency, $interval)
    {
        return match ($frequency) {
            'daily' => $runAt->copy()->addDays($interval),
            'weekly' => $runAt->copy()->addWeeks($interval),
            'monthly' => $runAt->copy()->addMonthsNoOverflow($interval),
            'yearly' => $runAt->copy()->addYearsNoOverflow($interval),
        };
    }

    private function Contract_GetScheduleTargets(contract_schedule $schedule)
    {
        $class = $this->Contract_ResolveContractableClass($schedule->contractable_type);
        if (!$class) {
            throw new \InvalidArgumentException('La fuente de la programacion no es valida');
        }
        if ($schedule->contractable_id !== null) {
            $target = $this->Contract_FindContractable($class, $schedule->contractable_id);
            return $target ? collect([$target]) : collect();
        }

        $query = $class::query();
        if ($class === employee::class) {
            $query->where('state', 1);
        } else {
            $query->where('active', 1);
        }
        return $query->orderBy('id')->get();
    }

    private function Contract_ProcessSchedule(contract_schedule $schedule, Carbon $now, array &$result)
    {
        $scheduledFor = Carbon::parse($schedule->next_run_at);
        $targets = $this->Contract_GetScheduleTargets($schedule);
        foreach ($targets as $target) {
            $scheduleKey = hash('sha256', $schedule->id.'|'.$schedule->contractable_type.'|'.$target->id.'|'.$scheduledFor->format('Y-m-d H:i:s'));
            if (contract::withTrashed()->where('schedule_key', $scheduleKey)->exists()) {
                $result['skipped']++;
                continue;
            }

            $createResponse = $this->Contract_CreateContract([
                'contract_type_id' => $schedule->contract_type_id,
                'contract_template_id' => $schedule->contract_template_id,
                'contractable_type' => $schedule->contractable_type,
                'contractable_id' => $target->id,
                'name' => $schedule->name,
                'start_date' => $scheduledFor->format('Y-m-d'),
                'force_generate' => true,
                'status' => 'generated',
            ], $schedule->id, $scheduledFor, $scheduleKey);
            if ($createResponse['status'] != 1) {
                $result['failed']++;
                $result['errors'][] = $createResponse['message'];
                continue;
            }

            $result['created']++;
            if ($schedule->send_automatically) {
                $sendResponse = $this->Contract_SendContract($createResponse['contract']->id);
                if ($sendResponse['status'] == 1) {
                    $result['sent']++;
                } else {
                    $result['failed']++;
                    $result['errors'][] = $sendResponse['message'];
                }
            }
        }

        $nextRunAt = $this->Contract_NextRun($scheduledFor, $schedule->frequency, $schedule->interval_value);
        $schedule->last_run_at = $now;
        $schedule->next_run_at = $nextRunAt;
        $schedule->last_error = count($result['errors']) > 0 ? substr(implode('; ', $result['errors']), 0, 1000) : null;
        if ($schedule->ends_at && $nextRunAt->gt(Carbon::parse($schedule->ends_at))) {
            $schedule->active = false;
        }
        $schedule->save();
    }

    public function Contract_ProcessSchedules($now = null)
    {
        $result = [
            'processed' => 0,
            'created' => 0,
            'sent' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [],
        ];
        $now = $now ? Carbon::parse($now) : Carbon::now();
        $schedules = contract_schedule::where('active', 1)
            ->where('next_run_at', '<=', $now)
            ->where(function ($query) use ($now) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->orderBy('id')
            ->get();

        foreach ($schedules as $schedule) {
            $result['processed']++;
            try {
                $this->Contract_ProcessSchedule($schedule, $now, $result);
            } catch (\Exception $e) {
                $result['failed']++;
                $result['errors'][] = $e->getMessage();
                $schedule->last_error = substr($e->getMessage(), 0, 1000);
                $schedule->last_run_at = $now;
                $schedule->next_run_at = $this->Contract_NextRun(Carbon::parse($schedule->next_run_at), $schedule->frequency, $schedule->interval_value);
                $schedule->save();
                info('Contract_ProcessSchedules schedule error: '.$e->getMessage());
            }
        }

        return $this->Contract_Response('Programaciones procesadas', ['data' => $result]);
    }
}