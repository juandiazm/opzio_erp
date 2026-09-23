<?php

namespace App\traits;

use App\Models\client;
use App\Models\license;
use App\Models\license_notification;
use App\Models\notification_tag;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait notification_contacts_trait
{
    private function NotificationContact_InputValue(array $input): array
    {
        $type = strtolower(trim((string) ($input['type'] ?? '')));
        $value = trim((string) ($input['value'] ?? ''));
        $legacyEmail = trim((string) ($input['email'] ?? ''));
        $legacyPhone = trim((string) ($input['phone'] ?? ''));

        if ($value === '') {
            if ($type === 'email') {
                $value = $legacyEmail;
            } elseif ($type === 'phone') {
                $value = $legacyPhone;
            } elseif ($legacyEmail !== '' && $legacyPhone === '') {
                $type = 'email';
                $value = $legacyEmail;
            } elseif ($legacyPhone !== '' && $legacyEmail === '') {
                $type = 'phone';
                $value = $legacyPhone;
            }
        }

        if ($type === '') {
            $type = filter_var($value, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        }
        if (!in_array($type, ['email', 'phone'], true)) {
            throw new \InvalidArgumentException('El tipo de contacto no es valido.');
        }
        if ($value === '') {
            throw new \InvalidArgumentException('El valor del contacto es obligatorio.');
        }
        if ($type === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('El correo del contacto no es valido.');
        }
        if ($type === 'phone') {
            $digits = preg_replace('/\D+/', '', $value);
            if ($digits === '' || strlen($digits) < 7 || strlen($digits) > 15) {
                throw new \InvalidArgumentException('El numero del contacto no es valido.');
            }
        }

        return [
            'type' => $type,
            'value' => $value,
            'email' => $type === 'email' ? $value : '',
            'phone' => $type === 'phone' ? $value : '',
        ];
    }

    private function NotificationContact_Type($contact): string
    {
        $type = strtolower(trim((string) ($contact->type ?? '')));
        if (in_array($type, ['email', 'phone'], true)) {
            return $type;
        }

        return trim((string) ($contact->email ?? '')) !== '' ? 'email' : 'phone';
    }

    private function NotificationContact_Value($contact): string
    {
        $value = trim((string) ($contact->value ?? ''));
        if ($value !== '') {
            return $value;
        }

        return $this->NotificationContact_Type($contact) === 'email'
            ? trim((string) ($contact->email ?? ''))
            : trim((string) ($contact->phone ?? ''));
    }

    private function NotificationContact_Array($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }

            return preg_split('/[,;]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
        }

        return [];
    }

    private function NotificationContact_NormalizeChannels($value, ?string $email = null, ?string $phone = null, ?string $type = null): array
    {
        $channels = [];
        foreach ($this->NotificationContact_Array($value) as $channel) {
            $channel = strtolower(trim((string) $channel));
            $channel = str_replace(['-', '/'], '_', $channel);
            if (in_array($channel, ['smswhatsapp', 'sms_whatsapp'], true)) {
                $channels[] = 'sms';
                $channels[] = 'whatsapp';
                continue;
            }
            if (!in_array($channel, ['email', 'sms', 'whatsapp'], true)) {
                throw new \InvalidArgumentException('El canal de contacto no es valido.');
            }
            if (!in_array($channel, $channels, true)) {
                $channels[] = $channel;
            }
        }

        $email = trim((string) $email);
        $phone = trim((string) $phone);
        $type = strtolower(trim((string) $type));
        if ($type === 'email') {
            if (array_diff($channels, ['email'])) {
                throw new \InvalidArgumentException('Un contacto de correo solo puede usar el canal Email.');
            }
            return ['email'];
        }
        if ($type === 'phone') {
            if (array_diff($channels, ['sms', 'whatsapp'])) {
                throw new \InvalidArgumentException('Un contacto telefonico solo puede usar los canales SMS o WhatsApp.');
            }
            if (!$channels && $phone !== '') {
                $channels[] = 'sms';
            }
            return array_values(array_unique($channels));
        }
        if (!$channels) {
            if ($email !== '') {
                $channels[] = 'email';
            }
            if ($phone !== '') {
                $channels[] = 'sms';
            }
        }

        if (in_array('email', $channels, true) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('El correo del contacto no es valido.');
        }
        if (array_intersect($channels, ['sms', 'whatsapp']) && $phone === '') {
            throw new \InvalidArgumentException('El telefono es obligatorio para el canal seleccionado.');
        }
        if (!$channels) {
            throw new \InvalidArgumentException('El contacto debe tener al menos un canal y un dato de contacto.');
        }

        return $channels;
    }

    private function NotificationContact_TagIds($value): array
    {
        return collect($this->NotificationContact_Array($value))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function NotificationContact_Owner(string $ownerType, $ownerId)
    {
        if ($ownerType === 'client') {
            $owner = client::find((int) $ownerId);
        } elseif ($ownerType === 'license') {
            $owner = license::withTrashed()->find((int) $ownerId);
        } else {
            $owner = null;
        }

        if (!$owner) {
            throw new \InvalidArgumentException('El propietario del contacto no existe.');
        }

        return $owner;
    }

    private function NotificationContact_ContactQuery(string $ownerType, $ownerId, bool $withTrashed = false)
    {
        $column = $ownerType === 'license' ? 'license_id' : 'client_id';
        $query = $withTrashed ? license_notification::withTrashed() : license_notification::query();
        $query->where($column, (int) $ownerId);
        if ($ownerType === 'client') {
            $query->whereNull('license_id');
        }

        return $query->with('tags')->orderBy('name')->orderBy('id');
    }

    private function NotificationContact_Payload(license_notification $contact): array
    {
        $type = $this->NotificationContact_Type($contact);
        $value = $this->NotificationContact_Value($contact);
        $channels = $this->NotificationContact_NormalizeChannels($contact->channels, $contact->email, $contact->phone, $type);
        $tags = [];
        if (Schema::hasTable('notification_tags')) {
            $contact->loadMissing('tags');
            $tags = $contact->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'color' => $tag->color,
            ])->values()->all();
        }

        return [
            'id' => $contact->id,
            'client_id' => $contact->client_id,
            'license_id' => $contact->license_id,
            'name' => $contact->name ?: 'Contacto',
            'type' => $type,
            'value' => $value,
            'email' => $contact->email,
            'phone' => $contact->phone,
            'channels' => $channels,
            'tags' => $tags,
            'active' => (bool) $contact->active,
            'position' => (int) ($contact->position ?? 0),
            'deleted_at' => $contact->deleted_at,
        ];
    }

    private function NotificationContact_SaveTags(license_notification $contact, array $tagIds): void
    {
        if (!Schema::hasTable('notification_tags')) {
            return;
        }

        if ($tagIds) {
            $validTagIds = notification_tag::whereIn('id', $tagIds)->pluck('id')->map(fn ($id) => (int) $id)->all();
            if (count($validTagIds) !== count($tagIds)) {
                throw new \InvalidArgumentException('Una de las etiquetas seleccionadas no existe.');
            }
            $contact->tags()->sync($validTagIds);
        } else {
            $contact->tags()->detach();
        }
    }

    private function NotificationContact_Save(string $ownerType, $ownerId, array $input, $contactId = null): license_notification
    {
        $owner = $this->NotificationContact_Owner($ownerType, $ownerId);
        $contactValue = $this->NotificationContact_InputValue($input);
        $email = $contactValue['email'];
        $phone = $contactValue['phone'];
        $channels = $this->NotificationContact_NormalizeChannels($input['channels'] ?? $input['channel'] ?? [], $email, $phone, $contactValue['type']);
        if (!$channels) {
            throw new \InvalidArgumentException('Debe seleccionar al menos un canal.');
        }
        $name = trim((string) ($input['name'] ?? $input['display_name'] ?? ''));
        if ($name === '') {
            $name = $owner->complete_name ?? $owner->name ?? 'Contacto';
        }

        $contact = $contactId
            ? license_notification::whereKey((int) $contactId)->first()
            : new license_notification();
        if (!$contact) {
            throw new \InvalidArgumentException('El contacto no existe.');
        }
        if ($contactId) {
            $belongsToOwner = $ownerType === 'license'
                ? (int) $contact->license_id === (int) $ownerId
                : (int) $contact->client_id === (int) $ownerId && $contact->license_id === null;
            if (!$belongsToOwner) {
                throw new \InvalidArgumentException('El contacto no pertenece al propietario seleccionado.');
            }
        }

        $contact->name = $name;
        $contact->email = $email !== '' ? $email : null;
        $contact->phone = $phone !== '' ? $phone : null;
        $contact->channels = $channels;
        if (Schema::hasColumn('license_notifications', 'value')) {
            $contact->value = $contactValue['value'];
            $contact->type = $contactValue['type'];
        }
        $contact->active = array_key_exists('active', $input)
            ? filter_var($input['active'], FILTER_VALIDATE_BOOLEAN)
            : ($contact->exists ? (bool) $contact->active : true);
        if ($ownerType === 'license') {
            $contact->license_id = (int) $ownerId;
            $contact->client_id = $owner->client_id;
        } else {
            $contact->license_id = null;
            $contact->client_id = (int) $ownerId;
        }
        if (!$contact->position) {
            $contact->position = (int) ($this->NotificationContact_ContactQuery($ownerType, $ownerId, true)->max('position') ?? 0) + 1;
        }
        $contact->save();
        if (array_key_exists('tag_ids', $input) || array_key_exists('tags', $input)) {
            $this->NotificationContact_SaveTags($contact, $this->NotificationContact_TagIds($input['tag_ids'] ?? $input['tags'] ?? []));
        }

        return $contact->fresh(['tags']);
    }

    public function NotificationContact_AddClient($clientId, array $input): array
    {
        try {
            $contact = $this->NotificationContact_Save('client', $clientId, $input);
            return ['status' => 1, 'message' => 'Contacto agregado', 'contact' => $this->NotificationContact_Payload($contact)];
        } catch (\Throwable $exception) {
            info('NotificationContact_AddClient error: '.$exception->getMessage());
            return ['status' => 0, 'message' => $exception->getMessage()];
        }
    }

    public function NotificationContact_GetClientContacts($clientId, $withTrashed = true): array
    {
        try {
            $this->NotificationContact_Owner('client', $clientId);
            $contacts = $this->NotificationContact_ContactQuery('client', $clientId, (bool) $withTrashed)->get();
            return ['status' => 1, 'message' => 'Contactos obtenidos', 'contacts' => $contacts->map(fn ($contact) => $this->NotificationContact_Payload($contact))->values()->all()];
        } catch (\Throwable $exception) {
            info('NotificationContact_GetClientContacts error: '.$exception->getMessage());
            return ['status' => 0, 'message' => $exception->getMessage()];
        }
    }

    public function NotificationContact_UpdateClient($contactId, array $input): array
    {
        try {
            $contact = license_notification::find((int) $contactId);
            if (!$contact || !$contact->client_id || $contact->license_id !== null) {
                throw new \InvalidArgumentException('El contacto no existe.');
            }
            $contact = $this->NotificationContact_Save('client', $contact->client_id, $input, $contactId);
            return ['status' => 1, 'message' => 'Contacto actualizado', 'contact' => $this->NotificationContact_Payload($contact)];
        } catch (\Throwable $exception) {
            info('NotificationContact_UpdateClient error: '.$exception->getMessage());
            return ['status' => 0, 'message' => $exception->getMessage()];
        }
    }

    public function NotificationContact_AddLicense($licenseId, array $input): array
    {
        try {
            $contact = $this->NotificationContact_Save('license', $licenseId, $input);
            return ['status' => 1, 'message' => 'Contacto agregado', 'contact' => $this->NotificationContact_Payload($contact)];
        } catch (\Throwable $exception) {
            info('NotificationContact_AddLicense error: '.$exception->getMessage());
            return ['status' => 0, 'message' => $exception->getMessage()];
        }
    }

    public function NotificationContact_GetLicenseContacts($licenseId, $withTrashed = true): array
    {
        try {
            $this->NotificationContact_Owner('license', $licenseId);
            $contacts = $this->NotificationContact_ContactQuery('license', $licenseId, (bool) $withTrashed)->get();
            return ['status' => 1, 'message' => 'Contactos obtenidos', 'contacts' => $contacts->map(fn ($contact) => $this->NotificationContact_Payload($contact))->values()->all()];
        } catch (\Throwable $exception) {
            info('NotificationContact_GetLicenseContacts error: '.$exception->getMessage());
            return ['status' => 0, 'message' => $exception->getMessage()];
        }
    }

    public function NotificationContact_UpdateLicense($contactId, array $input): array
    {
        try {
            $contact = license_notification::find((int) $contactId);
            if (!$contact || !$contact->license_id) {
                throw new \InvalidArgumentException('El contacto no existe.');
            }
            $contact = $this->NotificationContact_Save('license', $contact->license_id, $input, $contactId);
            return ['status' => 1, 'message' => 'Contacto actualizado', 'contact' => $this->NotificationContact_Payload($contact)];
        } catch (\Throwable $exception) {
            info('NotificationContact_UpdateLicense error: '.$exception->getMessage());
            return ['status' => 0, 'message' => $exception->getMessage()];
        }
    }

    public function NotificationContact_Delete($contactId): array
    {
        try {
            $contact = license_notification::find((int) $contactId);
            if (!$contact) {
                throw new \InvalidArgumentException('El contacto no existe.');
            }
            $contact->delete();
            return ['status' => 1, 'message' => 'Contacto eliminado'];
        } catch (\Throwable $exception) {
            info('NotificationContact_Delete error: '.$exception->getMessage());
            return ['status' => 0, 'message' => $exception->getMessage()];
        }
    }

    public function NotificationContact_Restore($contactId): array
    {
        try {
            $contact = license_notification::withTrashed()->find((int) $contactId);
            if (!$contact) {
                throw new \InvalidArgumentException('El contacto no existe.');
            }
            $contact->restore();
            return ['status' => 1, 'message' => 'Contacto restaurado'];
        } catch (\Throwable $exception) {
            info('NotificationContact_Restore error: '.$exception->getMessage());
            return ['status' => 0, 'message' => $exception->getMessage()];
        }
    }

    public function NotificationContact_ForceDelete($contactId): array
    {
        try {
            $contact = license_notification::withTrashed()->find((int) $contactId);
            if (!$contact) {
                throw new \InvalidArgumentException('El contacto no existe.');
            }
            if ($contact->deleted_at === null) {
                throw new \InvalidArgumentException('Solo se pueden eliminar permanentemente contactos ya eliminados.');
            }
            $contact->forceDelete();
            return ['status' => 1, 'message' => 'Contacto eliminado permanentemente'];
        } catch (\Throwable $exception) {
            info('NotificationContact_ForceDelete error: '.$exception->getMessage());
            return ['status' => 0, 'message' => $exception->getMessage()];
        }
    }

    public function NotificationContact_AttachTag($contactId, $tagId): array
    {
        try {
            $contact = license_notification::find((int) $contactId);
            $tag = notification_tag::find((int) $tagId);
            if (!$contact || !$tag) {
                throw new \InvalidArgumentException('El contacto o la etiqueta no existe.');
            }
            $contact->tags()->syncWithoutDetaching([$tag->id]);
            return ['status' => 1, 'message' => 'Etiqueta asociada'];
        } catch (\Throwable $exception) {
            info('NotificationContact_AttachTag error: '.$exception->getMessage());
            return ['status' => 0, 'message' => $exception->getMessage()];
        }
    }

    public function NotificationContact_DetachTag($contactId, $tagId): array
    {
        try {
            $contact = license_notification::find((int) $contactId);
            if (!$contact) {
                throw new \InvalidArgumentException('El contacto no existe.');
            }
            $contact->tags()->detach((int) $tagId);
            return ['status' => 1, 'message' => 'Etiqueta desligada'];
        } catch (\Throwable $exception) {
            info('NotificationContact_DetachTag error: '.$exception->getMessage());
            return ['status' => 0, 'message' => $exception->getMessage()];
        }
    }

    public function NotificationContact_GetTags($withTrashed = false): array
    {
        try {
            $query = $withTrashed ? notification_tag::withTrashed() : notification_tag::query();
            return ['status' => 1, 'message' => 'Etiquetas obtenidas', 'tags' => $query->orderBy('name')->get(['id', 'name', 'slug', 'color', 'deleted_at'])];
        } catch (\Throwable $exception) {
            info('NotificationContact_GetTags error: '.$exception->getMessage());
            return ['status' => 0, 'message' => $exception->getMessage()];
        }
    }

    private function NotificationContact_DirectoryIds($value): array
    {
        return collect($this->NotificationContact_Array($value))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function NotificationContact_DirectoryPagination($value): array
    {
        $pagination = $this->NotificationContact_Array($value);
        return [
            'page' => max(1, (int) ($pagination['page'] ?? 1)),
            'per_page' => min(100, max(5, (int) ($pagination['per_page'] ?? $pagination['size'] ?? 10))),
        ];
    }

    private function NotificationContact_DirectoryQuery(array $input = [])
    {
        $contactTable = (new license_notification())->getTable();
        $query = license_notification::query()
            ->select($contactTable.'.*')
            ->with(['client', 'license', 'tags'])
            ->leftJoin('clients as directory_clients', 'directory_clients.id', '=', $contactTable.'.client_id')
            ->orderByRaw("LOWER(COALESCE(directory_clients.name, ''))")
            ->orderByRaw("LOWER(COALESCE(directory_clients.lastname, ''))")
            ->orderByRaw("LOWER(COALESCE({$contactTable}.name, ''))")
            ->orderBy($contactTable.'.id');
        $search = trim((string) ($input['search'] ?? ''));
        $owner = strtolower(trim((string) ($input['owner'] ?? '')));
        $clientIds = $this->NotificationContact_DirectoryIds($input['client_ids'] ?? []);
        $licenseIds = $this->NotificationContact_DirectoryIds($input['license_ids'] ?? []);
        $types = array_values(array_intersect($this->NotificationContact_Array($input['types'] ?? []), ['email', 'phone']));
        $channels = array_values(array_intersect($this->NotificationContact_Array($input['channels'] ?? []), ['email', 'sms', 'whatsapp']));
        $tagIds = $this->NotificationContact_DirectoryIds($input['tag_ids'] ?? []);

        if ($owner === 'client') {
            $query->whereNull($contactTable.'.license_id');
        } elseif ($owner === 'license') {
            $query->whereNotNull($contactTable.'.license_id');
        }
        if ($clientIds) {
            $query->whereIn($contactTable.'.client_id', $clientIds);
        }
        if ($licenseIds) {
            $query->whereIn($contactTable.'.license_id', $licenseIds);
        }
        if ($types) {
            $query->whereIn($contactTable.'.type', $types);
        }
        if ($channels) {
            $query->where(function ($channelQuery) use ($channels, $contactTable) {
                foreach ($channels as $channel) {
                    $channelQuery->orWhereJsonContains($contactTable.'.channels', $channel);
                }
            });
        }
        if ($tagIds) {
            $query->whereHas('tags', fn ($tagQuery) => $tagQuery->whereIn('notification_tags.id', $tagIds));
        }
        if ($search !== '') {
            $query->where(function ($searchQuery) use ($search, $contactTable) {
                $like = '%'.$search.'%';
                $searchQuery->where($contactTable.'.name', 'like', $like)
                    ->orWhere($contactTable.'.value', 'like', $like)
                    ->orWhere($contactTable.'.email', 'like', $like)
                    ->orWhere($contactTable.'.phone', 'like', $like)
                    ->orWhereHas('client', function ($clientQuery) use ($like) {
                        $clientQuery->where('name', 'like', $like)->orWhere('lastname', 'like', $like);
                    })
                    ->orWhereHas('license', function ($licenseQuery) use ($like) {
                        $licenseQuery->where('name', 'like', $like)->orWhere('unique_id', 'like', $like);
                    });
            });
        }

        return $query;
    }

    private function NotificationContact_DirectoryPayload(license_notification $contact): array
    {
        $payload = $this->NotificationContact_Payload($contact);
        $client = $contact->client;
        $payload['owner_type'] = $contact->license_id ? 'license' : 'client';
        $payload['client_name'] = $client
            ? trim($client->name.' '.($client->lastname ?? ''))
            : null;
        $payload['client'] = $client ? [
            'id' => $client->id,
            'photo' => $client->photo,
            'photo_path' => $client->photo_path,
        ] : null;
        $payload['license_name'] = $contact->license?->name;
        $payload['owner_label'] = $contact->license_id ? 'Licencia' : 'Cliente';

        return $payload;
    }

    public function NotificationContact_GetDirectoryFilters(): array
    {
        try {
            $clients = client::query()
                ->orderBy('name')
                ->get(['id', 'name', 'lastname'])
                ->map(fn ($client) => [
                    'id' => $client->id,
                    'label' => trim($client->name.' '.($client->lastname ?? '')),
                ])
                ->values()
                ->all();
            $licenses = license::query()
                ->orderBy('name')
                ->get(['id', 'name', 'unique_id', 'client_id'])
                ->map(fn ($license) => [
                    'id' => $license->id,
                    'label' => trim(($license->name ?: 'Sin nombre').' · '.substr((string) $license->unique_id, -8)),
                    'client_id' => $license->client_id,
                ])
                ->values()
                ->all();
            $tags = notification_tag::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'color'])
                ->values()
                ->all();

            return [
                'status' => 1,
                'message' => 'Filtros de contactos obtenidos',
                'clients' => $clients,
                'licenses' => $licenses,
                'tags' => $tags,
                'types' => [
                    ['value' => 'email', 'label' => 'Correo'],
                    ['value' => 'phone', 'label' => 'Número'],
                ],
                'channels' => [
                    ['value' => 'email', 'label' => 'Email'],
                    ['value' => 'sms', 'label' => 'SMS'],
                    ['value' => 'whatsapp', 'label' => 'WhatsApp'],
                ],
            ];
        } catch (\Throwable $exception) {
            info('NotificationContact_GetDirectoryFilters error: '.$exception->getMessage());
            return ['status' => 0, 'message' => $exception->getMessage()];
        }
    }

    public function NotificationContact_GetDirectoryPage(array $input = []): array
    {
        try {
            $pagination = $this->NotificationContact_DirectoryPagination($input['pagination'] ?? []);
            $result = $this->NotificationContact_DirectoryQuery($input)
                ->paginate($pagination['per_page'], ['*'], 'page', $pagination['page']);
            $contacts = $result->getCollection()
                ->map(fn ($contact) => $this->NotificationContact_DirectoryPayload($contact))
                ->values()
                ->all();

            return [
                'status' => 1,
                'message' => 'Contactos obtenidos',
                'contacts' => $contacts,
                'pagination' => [
                    'page' => $result->currentPage(),
                    'per_page' => $result->perPage(),
                    'total' => $result->total(),
                    'totalPages' => $result->lastPage(),
                ],
            ];
        } catch (\Throwable $exception) {
            info('NotificationContact_GetDirectoryPage error: '.$exception->getMessage());
            return ['status' => 0, 'message' => $exception->getMessage()];
        }
    }

    public function NotificationContact_GetMessageContext($contactId): array
    {
        try {
            $contact = license_notification::with(['client', 'license', 'tags'])->find((int) $contactId);
            if (!$contact) {
                throw new \InvalidArgumentException('El contacto no existe.');
            }
            if (!$contact->active) {
                throw new \InvalidArgumentException('El contacto esta inactivo.');
            }

            return [
                'status' => 1,
                'message' => 'Contacto listo para enviar',
                'contact' => $this->NotificationContact_DirectoryPayload($contact),
            ];
        } catch (\Throwable $exception) {
            info('NotificationContact_GetMessageContext error: '.$exception->getMessage());
            return ['status' => 0, 'message' => $exception->getMessage()];
        }
    }

    public function NotificationContact_GetDirectoryExport(array $input = []): array
    {
        try {
            return [
                'status' => 1,
                'message' => 'Contactos listos para exportar',
                'contacts' => $this->NotificationContact_DirectoryQuery($input)->get(),
            ];
        } catch (\Throwable $exception) {
            info('NotificationContact_GetDirectoryExport error: '.$exception->getMessage());
            return ['status' => 0, 'message' => $exception->getMessage()];
        }
    }

    private function NotificationContact_ImportType($value): ?string
    {
        $type = Str::lower(Str::ascii(trim((string) $value)));
        if ($type === '') {
            return null;
        }
        if (in_array($type, ['email', 'correo', 'correo electronico'], true)) {
            return 'email';
        }
        if (in_array($type, ['phone', 'numero', 'celular', 'telefono', 'movil'], true)) {
            return 'phone';
        }

        throw new \InvalidArgumentException('El tipo debe ser Correo o Número.');
    }

    private function NotificationContact_ImportId($value, string $label): ?int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (!is_numeric($value) || (float) $value <= 0 || (float) $value != (int) $value) {
            throw new \InvalidArgumentException('El '.$label.' debe ser un ID numérico válido.');
        }

        return (int) $value;
    }

    private function NotificationContact_ImportActive($value): bool
    {
        if ($value === null || trim((string) $value) === '') {
            return true;
        }
        if (is_bool($value)) {
            return $value;
        }
        $active = Str::lower(Str::ascii(trim((string) $value)));
        if (in_array($active, ['1', 'true', 'si', 'yes', 'activo', 'activa', 'active'], true)) {
            return true;
        }
        if (in_array($active, ['0', 'false', 'no', 'inactivo', 'inactiva', 'inactive'], true)) {
            return false;
        }

        throw new \InvalidArgumentException('El estado debe ser Activo o Inactivo.');
    }

    private function NotificationContact_ImportTagIds($value): array
    {
        $tokens = collect($this->NotificationContact_Array($value))
            ->map(fn ($token) => trim((string) $token))
            ->filter()
            ->values();
        if ($tokens->isEmpty()) {
            return [];
        }
        if (!Schema::hasTable('notification_tags')) {
            throw new \InvalidArgumentException('La tabla de etiquetas no está disponible.');
        }

        $tags = notification_tag::query()->get(['id', 'name', 'slug']);
        $lookup = [];
        foreach ($tags as $tag) {
            $lookup[Str::lower(trim((string) $tag->name))] = (int) $tag->id;
            $lookup[Str::lower(trim((string) $tag->slug))] = (int) $tag->id;
            $lookup[(string) $tag->id] = (int) $tag->id;
        }

        $tagIds = [];
        foreach ($tokens as $token) {
            $key = Str::lower($token);
            if (!array_key_exists($key, $lookup)) {
                throw new \InvalidArgumentException('La etiqueta "'.$token.'" no existe.');
            }
            $tagIds[] = $lookup[$key];
        }

        return array_values(array_unique($tagIds));
    }

    public function NotificationContact_ImportDirectory(array $rows): array
    {
        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            try {
                $row = is_array($row) ? $row : (array) $row;
                $value = trim((string) ($row['value'] ?? ''));
                if ($value === '') {
                    throw new \InvalidArgumentException('El valor del contacto es obligatorio.');
                }

                $input = [
                    'value' => $value,
                    'type' => $this->NotificationContact_ImportType($row['type'] ?? null),
                    'channels' => array_key_exists('channels', $row)
                        ? $this->NotificationContact_Array($row['channels'])
                        : [],
                ];
                foreach (['name', 'client_id', 'license_id', 'active'] as $key) {
                    if (array_key_exists($key, $row)) {
                        $input[$key] = $row[$key];
                    }
                }
                if (array_key_exists('client_id', $input)) {
                    $input['client_id'] = $this->NotificationContact_ImportId($input['client_id'], 'cliente');
                }
                if (array_key_exists('license_id', $input)) {
                    $input['license_id'] = $this->NotificationContact_ImportId($input['license_id'], 'licencia');
                }
                if (array_key_exists('active', $input)) {
                    $input['active'] = $this->NotificationContact_ImportActive($input['active']);
                }
                if (array_key_exists('tags', $row)) {
                    $input['tag_ids'] = $this->NotificationContact_ImportTagIds($row['tags']);
                }

                $rawContactId = $row['id'] ?? $row['contact_id'] ?? null;
                $contactId = trim((string) $rawContactId) === ''
                    ? null
                    : $this->NotificationContact_ImportId($rawContactId, 'contacto');
                if ($contactId !== null) {
                    $result = $this->NotificationContact_UpdateDirectory($contactId, $input);
                    if (($result['status'] ?? 0) !== 1) {
                        throw new \InvalidArgumentException($result['message'] ?? 'No fue posible actualizar el contacto.');
                    }
                    $updated++;
                    continue;
                }

                $licenseId = $input['license_id'] ?? null;
                $clientId = $input['client_id'] ?? null;
                if (!$licenseId && !$clientId) {
                    throw new \InvalidArgumentException('La fila debe tener Cliente ID o Licencia ID.');
                }
                $result = $licenseId
                    ? $this->NotificationContact_AddLicense($licenseId, $input)
                    : $this->NotificationContact_AddClient($clientId, $input);
                if (($result['status'] ?? 0) !== 1) {
                    throw new \InvalidArgumentException($result['message'] ?? 'No fue posible crear el contacto.');
                }
                $created++;
            } catch (\Throwable $exception) {
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => $exception->getMessage(),
                ];
            }
        }

        if (!$created && !$updated && !$errors) {
            return [
                'status' => 0,
                'message' => 'El archivo no contiene filas de contactos.',
                'created' => 0,
                'updated' => 0,
                'errors' => [],
            ];
        }

        return [
            'status' => $errors ? 0 : 1,
            'message' => $errors ? 'Importación completada con observaciones.' : 'Contactos importados correctamente.',
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }

    public function NotificationContact_AddDirectory(array $input): array
    {
        try {
            $licenseId = !empty($input['license_id']) ? (int) $input['license_id'] : null;
            $clientId = !empty($input['client_id']) ? (int) $input['client_id'] : null;
            if (!$licenseId && !$clientId) {
                throw new \InvalidArgumentException('Debes seleccionar un cliente o una licencia.');
            }

            return $licenseId
                ? $this->NotificationContact_AddLicense($licenseId, $input)
                : $this->NotificationContact_AddClient($clientId, $input);
        } catch (\Throwable $exception) {
            info('NotificationContact_AddDirectory error: '.$exception->getMessage());
            return ['status' => 0, 'message' => $exception->getMessage()];
        }
    }

    public function NotificationContact_UpdateDirectory($contactId, array $input): array
    {
        try {
            $contact = license_notification::find((int) $contactId);
            if (!$contact) {
                throw new \InvalidArgumentException('El contacto no existe.');
            }

            $contactValue = $this->NotificationContact_InputValue($input);
            $channels = $this->NotificationContact_NormalizeChannels(
                $input['channels'] ?? [],
                $contactValue['email'],
                $contactValue['phone'],
                $contactValue['type']
            );
            $clientId = !empty($input['client_id']) ? (int) $input['client_id'] : null;
            $licenseId = !empty($input['license_id']) ? (int) $input['license_id'] : null;
            $clientOwner = $clientId ? client::find($clientId) : null;
            $licenseOwner = $licenseId ? license::withTrashed()->find($licenseId) : null;

            if ($licenseId && !$licenseOwner) {
                throw new \InvalidArgumentException('La licencia seleccionada no existe.');
            }
            if (!$licenseId && !$clientOwner && !$contact->client_id) {
                throw new \InvalidArgumentException('Debes seleccionar un cliente o una licencia.');
            }
            if ($licenseOwner) {
                $clientId = (int) $licenseOwner->client_id;
            } elseif (!$clientOwner) {
                $clientId = (int) $contact->client_id;
                $clientOwner = client::find($clientId);
            }
            if (!$clientOwner) {
                throw new \InvalidArgumentException('El cliente seleccionado no existe.');
            }

            $contact->client_id = $clientId;
            $contact->license_id = $licenseId;
            $contact->name = trim((string) ($input['name'] ?? '')) ?: 'Contacto';
            $contact->email = $contactValue['email'] !== '' ? $contactValue['email'] : null;
            $contact->phone = $contactValue['phone'] !== '' ? $contactValue['phone'] : null;
            $contact->channels = $channels;
            $contact->active = array_key_exists('active', $input)
                ? filter_var($input['active'], FILTER_VALIDATE_BOOLEAN)
                : (bool) $contact->active;
            if (Schema::hasColumn('license_notifications', 'value')) {
                $contact->value = $contactValue['value'];
                $contact->type = $contactValue['type'];
            }
            $contact->save();
            if (array_key_exists('tag_ids', $input) || array_key_exists('tags', $input)) {
                $this->NotificationContact_SaveTags($contact, $this->NotificationContact_TagIds($input['tag_ids'] ?? $input['tags'] ?? []));
            }

            return [
                'status' => 1,
                'message' => 'Contacto actualizado',
                'contact' => $this->NotificationContact_Payload($contact->fresh(['client', 'license', 'tags'])),
            ];
        } catch (\Throwable $exception) {
            info('NotificationContact_UpdateDirectory error: '.$exception->getMessage());
            return ['status' => 0, 'message' => $exception->getMessage()];
        }
    }

    public function NotificationContact_ToggleDirectoryStatus($contactId, $active = null): array
    {
        try {
            $contact = license_notification::withTrashed()->find((int) $contactId);
            if (!$contact || $contact->deleted_at !== null) {
                throw new \InvalidArgumentException('El contacto no existe o esta eliminado.');
            }
            $contact->active = $active === null
                ? !$contact->active
                : filter_var($active, FILTER_VALIDATE_BOOLEAN);
            $contact->save();
            return [
                'status' => 1,
                'message' => $contact->active ? 'Contacto activado' : 'Contacto desactivado',
                'contact' => $this->NotificationContact_Payload($contact->fresh(['client', 'license', 'tags'])),
            ];
        } catch (\Throwable $exception) {
            info('NotificationContact_ToggleDirectoryStatus error: '.$exception->getMessage());
            return ['status' => 0, 'message' => $exception->getMessage()];
        }
    }

    public function NotificationContact_AddTag(array $input): array
    {
        try {
            $name = trim((string) ($input['name'] ?? ''));
            if ($name === '' || mb_strlen($name) > 100) {
                throw new \InvalidArgumentException('El nombre de la etiqueta es obligatorio y debe tener hasta 100 caracteres.');
            }
            $slug = Str::slug($name);
            if ($slug === '') {
                throw new \InvalidArgumentException('El nombre de la etiqueta no es valido.');
            }
            if (notification_tag::withTrashed()->where('slug', $slug)->exists()) {
                throw new \InvalidArgumentException('La etiqueta ya existe.');
            }
            $color = trim((string) ($input['color'] ?? '')) ?: null;
            if ($color !== null && !preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
                throw new \InvalidArgumentException('El color de la etiqueta no es valido.');
            }
            $tag = notification_tag::create([
                'name' => $name,
                'slug' => $slug,
                'color' => $color,
            ]);
            return ['status' => 1, 'message' => 'Etiqueta creada', 'tag' => $tag];
        } catch (\Throwable $exception) {
            info('NotificationContact_AddTag error: '.$exception->getMessage());
            return ['status' => 0, 'message' => $exception->getMessage()];
        }
    }

    public function NotificationContact_UpdateTag($tagId, array $input): array
    {
        try {
            $tag = notification_tag::find((int) $tagId);
            if (!$tag) {
                throw new \InvalidArgumentException('La etiqueta no existe.');
            }
            $name = trim((string) ($input['name'] ?? $tag->name));
            $slug = Str::slug($name);
            if ($name === '' || $slug === '') {
                throw new \InvalidArgumentException('El nombre de la etiqueta no es valido.');
            }
            if (notification_tag::where('slug', $slug)->where('id', '!=', $tag->id)->exists()) {
                throw new \InvalidArgumentException('La etiqueta ya existe.');
            }
            $color = array_key_exists('color', $input) ? (trim((string) $input['color']) ?: null) : $tag->color;
            if ($color !== null && !preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
                throw new \InvalidArgumentException('El color de la etiqueta no es valido.');
            }
            $tag->update([
                'name' => $name,
                'slug' => $slug,
                'color' => $color,
            ]);
            return ['status' => 1, 'message' => 'Etiqueta actualizada', 'tag' => $tag->fresh()];
        } catch (\Throwable $exception) {
            info('NotificationContact_UpdateTag error: '.$exception->getMessage());
            return ['status' => 0, 'message' => $exception->getMessage()];
        }
    }

    public function NotificationContact_DeleteTag($tagId): array
    {
        try {
            $tag = notification_tag::find((int) $tagId);
            if (!$tag) {
                throw new \InvalidArgumentException('La etiqueta no existe.');
            }
            $tag->contacts()->detach();
            $tag->delete();
            return ['status' => 1, 'message' => 'Etiqueta eliminada'];
        } catch (\Throwable $exception) {
            info('NotificationContact_DeleteTag error: '.$exception->getMessage());
            return ['status' => 0, 'message' => $exception->getMessage()];
        }
    }

    public function NotificationContact_EnsureCollectionTag(): ?notification_tag
    {
        if (!Schema::hasTable('notification_tags')) {
            return null;
        }

        return notification_tag::firstOrCreate(
            ['slug' => config('notifications.collection_tag', 'cobranza')],
            ['name' => 'Cobranza', 'color' => '#c2410c']
        );
    }

    public function NotificationContact_EnsureClientPrimary(client $client): ?license_notification
    {
        if (!Schema::hasColumn('license_notifications', 'client_id')) {
            return null;
        }

        $email = trim((string) $client->email);
        $phone = trim((string) $client->phone);
        if ($email === '' && $phone === '') {
            return null;
        }
        $name = trim($client->complete_name ?: $client->name) ?: 'Contacto principal';
        $tag = $this->NotificationContact_EnsureCollectionTag();
        $tagIds = $tag ? [$tag->id] : [];
        $lastContact = null;

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $query = license_notification::where('client_id', $client->id)->whereNull('license_id');
            $existing = Schema::hasColumn('license_notifications', 'value')
                ? (clone $query)->where('type', 'email')->where('value', $email)->first()
                : (clone $query)->where('email', $email)->first();
            $lastContact = $this->NotificationContact_Save('client', $client->id, [
                'name' => $name,
                'type' => 'email',
                'value' => $email,
                'channels' => ['email'],
                'active' => true,
                'tag_ids' => $tagIds,
            ], $existing?->id);
        }

        if ($phone !== '') {
            $query = license_notification::where('client_id', $client->id)->whereNull('license_id');
            $existing = Schema::hasColumn('license_notifications', 'value')
                ? (clone $query)->where('type', 'phone')->where('value', $phone)->first()
                : (clone $query)->where('phone', $phone)->first();
            $existingChannels = $existing
                ? $this->NotificationContact_NormalizeChannels($existing->channels, $existing->email, $existing->phone, 'phone')
                : ['sms'];
            $lastContact = $this->NotificationContact_Save('client', $client->id, [
                'name' => $name,
                'type' => 'phone',
                'value' => $phone,
                'channels' => $existingChannels,
                'active' => true,
                'tag_ids' => $tagIds,
            ], $existing?->id);
        }

        return $lastContact;
    }
}