<?php

namespace App\traits;

use App\Models\client;
use App\Models\contract;
use App\Models\contract_schedule;
use App\Models\contract_template;
use App\Models\contract_type;
use App\Models\employee;
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

    private function Contract_RenderTemplate($template, $contractable, $name, $subject, $startDate, $endDate)
    {
        $contractableName = $this->Contract_ContractableName($contractable);
        $contractableEmail = $this->Contract_ContractableEmail($contractable);
        $values = [
            '{{contractable.name}}' => $this->Contract_Escape($contractableName),
            '{{contractable.email}}' => $this->Contract_Escape($contractableEmail),
            '{{contractable.phone}}' => $this->Contract_Escape($contractable->phone ?? ''),
            '{{contractable.identification}}' => $this->Contract_Escape($contractable->identification ?? ''),
            '{{contract.name}}' => $this->Contract_Escape($name),
            '{{contract.subject}}' => $this->Contract_Escape($subject),
            '{{contract.start_date}}' => $this->Contract_Escape($startDate ?? ''),
            '{{contract.end_date}}' => $this->Contract_Escape($endDate ?? ''),
            '{{contract.type}}' => $this->Contract_Escape($template->type->name ?? ''),
        ];

        $renderedSubject = strtr($subject ?: $template->subject, $values);
        $renderedContent = strtr($template->content, $values);

        return [
            'subject' => $renderedSubject,
            'content' => $renderedContent,
            'data' => [
                'contractable_name' => $contractableName,
                'contractable_email' => $contractableEmail,
                'contract_name' => $name,
                'contract_subject' => $subject ?: $template->subject,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'template_id' => $template->id,
                'template_version' => $template->version,
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
        $generationData = $contract->generation_data;
        if ($shouldGenerate) {
            if (!$template) {
                throw new \InvalidArgumentException('Debe seleccionar una plantilla para generar el contrato');
            }
            $rendered = $this->Contract_RenderTemplate($template, $contractable, $name, $subject, $startDate, $endDate);
            $subject = $rendered['subject'];
            $content = $rendered['content'];
            $generationData = $rendered['data'];
            $contract->generated_at = now();
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
            $templates = contract_template::where('active', 1)->orderBy('name')->get(['id', 'contract_type_id', 'name', 'subject', 'version']);

            return $this->Contract_Response('Catalogos obtenidos', compact('clients', 'employees', 'providers', 'types', 'templates'));
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

    public function Contract_CreateTemplate($typeId, $name, $subject, $content, $active = 1)
    {
        try {
            $type = contract_type::find($typeId);
            if (!$type) {
                throw new \InvalidArgumentException('El tipo de contrato no existe');
            }
            $name = trim((string) $name);
            $subject = trim((string) $subject);
            if ($name === '' || $subject === '' || trim((string) $content) === '') {
                throw new \InvalidArgumentException('Nombre, asunto y contenido son obligatorios');
            }
            $version = ((int) contract_template::withTrashed()->where('contract_type_id', $type->id)->max('version')) + 1;
            $template = contract_template::create([
                'contract_type_id' => $type->id,
                'name' => $name,
                'subject' => $subject,
                'content' => $content,
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

    public function Contract_UpdateTemplate($id, $typeId, $name, $subject, $content, $active = 1)
    {
        try {
            $template = contract_template::find($id);
            $type = contract_type::find($typeId);
            if (!$template || !$type) {
                return $this->Contract_Response('La plantilla o el tipo no existe', [], 0);
            }
            $name = trim((string) $name);
            $subject = trim((string) $subject);
            if ($name === '' || $subject === '' || trim((string) $content) === '') {
                throw new \InvalidArgumentException('Nombre, asunto y contenido son obligatorios');
            }
            $typeChanged = (int) $template->contract_type_id !== (int) $type->id;
            $contentChanged = $template->name !== $name || $template->subject !== $subject || $template->content !== $content;
            if ($typeChanged) {
                $template->version = ((int) contract_template::withTrashed()->where('contract_type_id', $type->id)->max('version')) + 1;
            } elseif ($contentChanged) {
                $template->version = ((int) $template->version) + 1;
            }
            $template->contract_type_id = $type->id;
            $template->name = $name;
            $template->subject = $subject;
            $template->content = $content;
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