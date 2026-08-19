<?php

namespace App\traits;

use App\Domain\Servers\Models\servers_project;
use App\Models\client;
use Illuminate\Support\Facades\DB;

trait servers_trait
{
    public function Servers_GetProjectConfig($projectId)
    {
        try {
            $project = servers_project::with([
                'host',
                'client',
                'notificationRecipients',
            ])->find($projectId);

            if (! $project) {
                return [
                    'status' => 0,
                    'message' => 'El proyecto no existe',
                ];
            }

            return [
                'status' => 1,
                'message' => 'Configuración del proyecto obtenida',
                'data' => $this->Servers_ProjectConfigurationData($project),
            ];
        } catch (\Throwable $exception) {
            info('Servers_GetProjectConfig error: '.$exception->getMessage());

            return [
                'status' => 0,
                'message' => 'No fue posible obtener la configuración del proyecto',
            ];
        }
    }

    public function Servers_GetProjectRecipients($clientId)
    {
        try {
            $client = $this->Servers_GetActiveClientWithRecipients($clientId);

            if (! $client) {
                return [
                    'status' => 0,
                    'message' => 'El cliente no existe o está inactivo',
                ];
            }

            return [
                'status' => 1,
                'message' => 'Notificadores obtenidos',
                'data' => [
                    'client' => $this->Servers_ClientSummary($client),
                    'recipients' => $this->Servers_BuildRecipientsForClient($client)->values(),
                ],
            ];
        } catch (\Throwable $exception) {
            info('Servers_GetProjectRecipients error: '.$exception->getMessage());

            return [
                'status' => 0,
                'message' => 'No fue posible obtener los notificadores del cliente',
            ];
        }
    }

    public function Servers_UpdateProjectConfig($projectId, $clientId, $notificationsEnabled, $recipientKeys)
    {
        try {
            $project = servers_project::with('notificationRecipients')->find($projectId);

            if (! $project) {
                return [
                    'status' => 0,
                    'message' => 'El proyecto no existe',
                ];
            }

            $clientId = $clientId === null || $clientId === '' ? null : (int) $clientId;
            $client = $clientId ? $this->Servers_GetActiveClientWithRecipients($clientId) : null;

            if ($clientId && ! $client) {
                return [
                    'status' => 0,
                    'message' => 'El cliente no existe o está inactivo',
                ];
            }

            $requestedKeys = collect((array) $recipientKeys)
                ->map(function ($key) {
                    return trim((string) $key);
                })
                ->filter()
                ->unique()
                ->values();
            $available = $client ? $this->Servers_BuildRecipientsForClient($client) : collect();
            $allowed = $available->keyBy('key');

            if ($clientId !== null && (int) $project->client_id === $clientId) {
                foreach ($project->notificationRecipients as $storedRecipient) {
                    if (! $allowed->has($storedRecipient->source_key)) {
                        $allowed->put(
                            $storedRecipient->source_key,
                            $this->Servers_StoredRecipient($storedRecipient)
                        );
                    }
                }
            }

            $invalidKeys = $requestedKeys->reject(function ($key) use ($allowed) {
                return $allowed->has($key);
            })->values();

            if ($invalidKeys->isNotEmpty()) {
                return [
                    'status' => 0,
                    'message' => 'Uno o más notificadores no pertenecen al cliente seleccionado',
                ];
            }

            $selected = $requestedKeys->map(function ($key) use ($allowed) {
                return $allowed->get($key);
            })->filter()->values();
            $notificationsEnabled = filter_var($notificationsEnabled, FILTER_VALIDATE_BOOLEAN);

            if ($notificationsEnabled && $selected->isEmpty()) {
                return [
                    'status' => 0,
                    'message' => 'Debe seleccionar al menos un notificador para encender las notificaciones',
                ];
            }

            DB::transaction(function () use ($project, $clientId, $notificationsEnabled, $selected) {
                $project->client_id = $clientId;
                $project->notifications_enabled = $notificationsEnabled && $selected->isNotEmpty();
                $project->save();
                $project->notificationRecipients()->delete();

                foreach ($selected as $recipient) {
                    $project->notificationRecipients()->create([
                        'source_type' => $recipient['source_type'],
                        'source_id' => $recipient['source_id'],
                        'source_key' => $recipient['key'],
                        'channel' => $recipient['channel'],
                        'value' => $recipient['value'],
                        'recipient_name' => $recipient['name'],
                    ]);
                }
            });

            $project->load([
                'host',
                'client',
                'notificationRecipients',
            ]);

            return [
                'status' => 1,
                'message' => 'Configuración del proyecto actualizada',
                'data' => $this->Servers_ProjectConfigurationData($project),
            ];
        } catch (\Throwable $exception) {
            info('Servers_UpdateProjectConfig error: '.$exception->getMessage());

            return [
                'status' => 0,
                'message' => 'No fue posible actualizar la configuración del proyecto',
            ];
        }
    }

    private function Servers_ProjectConfigurationData($project)
    {
        $selected = $project->notificationRecipients
            ->map(function ($recipient) {
                return $this->Servers_StoredRecipient($recipient);
            })
            ->values();
        $selectedKeys = $selected->pluck('key')->all();
        $available = $project->client
            ? $this->Servers_BuildRecipientsForClient($project->client)
                ->map(function ($recipient) use ($selectedKeys) {
                    $recipient['selected'] = in_array($recipient['key'], $selectedKeys, true);
                    return $recipient;
                })
                ->values()
            : collect();

        return [
            'project' => [
                'id' => (int) $project->id,
                'name' => $project->name,
                'key' => $project->key,
                'host_name' => $project->host ? $project->host->name : null,
                'environment' => $project->environment,
                'client_id' => $project->client_id ? (int) $project->client_id : null,
                'notifications_enabled' => (bool) $project->notifications_enabled,
            ],
            'clients' => client::where('active', 1)
                ->orderBy('name')
                ->orderBy('lastname')
                ->get(['id', 'name', 'lastname'])
                ->map(function ($client) {
                    return $this->Servers_ClientSummary($client);
                })
                ->values(),
            'client' => $project->client ? $this->Servers_ClientSummary($project->client) : null,
            'available_recipients' => $available,
            'selected_recipients' => $selected,
            'has_recipients' => $selected->isNotEmpty(),
            'recipients_count' => $selected->count(),
        ];
    }

    private function Servers_GetActiveClientWithRecipients($clientId)
    {
        $client = client::where('active', 1)->whereKey((int) $clientId)->first();

        if (! $client) {
            return null;
        }

        $client->load([
            'licenses' => function ($query) {
                $query->where('active', 1)
                    ->orderBy('name')
                    ->with([
                        'license_notifications' => function ($notificationQuery) {
                            $notificationQuery->where('active', 1)->orderBy('id');
                        },
                    ]);
            },
        ]);

        return $client;
    }

    private function Servers_BuildRecipientsForClient($client)
    {
        $recipients = collect();
        $seen = [];
        $recipientName = $this->Servers_ClientName($client);
        $append = function ($sourceType, $sourceId, $channel, $value, $sourceLabel) use (&$recipients, &$seen, $recipientName) {
            $value = trim((string) $value);
            if ($value === '') {
                return;
            }

            if ($channel === 'email' && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return;
            }

            $normalized = $channel === 'email'
                ? strtolower($value)
                : preg_replace('/\\D+/', '', $value);
            if ($normalized === '' || ($channel === 'phone' && (strlen($normalized) < 7 || strlen($normalized) > 15))) {
                return;
            }

            $dedupeKey = $channel.':'.$normalized;
            if (isset($seen[$dedupeKey])) {
                return;
            }
            $seen[$dedupeKey] = true;

            $recipients->push([
                'key' => $sourceType.':'.$sourceId.':'.$channel,
                'source_type' => $sourceType,
                'source_id' => (int) $sourceId,
                'channel' => $channel,
                'value' => $value,
                'name' => $recipientName,
                'source_label' => $sourceLabel,
                'selected' => false,
            ]);
        };

        $append('client', $client->id, 'email', $client->email, 'Datos del cliente');
        $append('client', $client->id, 'phone', $client->phone, 'Datos del cliente');

        foreach ($client->licenses as $license) {
            foreach ($license->license_notifications as $notification) {
                $sourceLabel = 'Licencia: '.($license->name ?: 'Sin nombre');
                $append('license_notification', $notification->id, 'email', $notification->email, $sourceLabel);
                $append('license_notification', $notification->id, 'phone', $notification->phone, $sourceLabel);
            }
        }

        return $recipients;
    }

    private function Servers_StoredRecipient($recipient)
    {
        return [
            'key' => $recipient->source_key,
            'source_type' => $recipient->source_type,
            'source_id' => $recipient->source_id ? (int) $recipient->source_id : null,
            'channel' => $recipient->channel,
            'value' => $recipient->value,
            'name' => $recipient->recipient_name,
            'source_label' => 'Configurado anteriormente',
            'selected' => true,
            'available' => false,
        ];
    }

    private function Servers_ClientName($client)
    {
        return trim((string) $client->name.' '.(string) ($client->lastname ?: ''));
    }

    private function Servers_ClientSummary($client)
    {
        return [
            'id' => (int) $client->id,
            'name' => $client->name,
            'lastname' => $client->lastname,
            'complete_name' => $this->Servers_ClientName($client),
        ];
    }
}
