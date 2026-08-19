<?php

namespace App\traits;

use App\Domain\Servers\Models\servers_project;
use App\Models\client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

    public function Servers_UpdateProjectConfig($projectId, $clientId, $notificationsEnabled, $recipientKeys, $notificationName = null)
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
            $client = $clientId ? client::where('active', 1)->whereKey($clientId)->first() : null;

            if ($clientId && ! $client) {
                return [
                    'status' => 0,
                    'message' => 'El cliente no existe o está inactivo',
                ];
            }

            $hasStoredRecipients = $project->notificationRecipients->isNotEmpty();
            $initialImport = ! (bool) $project->notification_recipients_initialized && ! $hasStoredRecipients;
            $legacyConfiguration = ! (bool) $project->notification_recipients_initialized && $hasStoredRecipients;
            $selected = collect();
            if ($initialImport && $client) {
                $client = $this->Servers_GetActiveClientWithRecipients($clientId);
                $available = $this->Servers_BuildRecipientsForClient($client);
                $allowed = $available->keyBy('key');
                $requestedKeys = collect((array) $recipientKeys)
                    ->map(function ($key) {
                        return trim((string) $key);
                    })
                    ->filter()
                    ->unique()
                    ->values();
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
            }

            $notificationsEnabled = filter_var($notificationsEnabled, FILTER_VALIDATE_BOOLEAN);
            $recipientCount = $project->notificationRecipients->count() + $selected->count();

            if ($notificationsEnabled && $recipientCount === 0) {
                return [
                    'status' => 0,
                    'message' => 'Debe seleccionar al menos un notificador para encender las notificaciones',
                ];
            }

            DB::transaction(function () use ($project, $clientId, $notificationsEnabled, $selected, $initialImport, $legacyConfiguration, $recipientCount, $notificationName) {
                $project->client_id = $clientId;
                $project->notifications_enabled = $notificationsEnabled && $recipientCount > 0;
                $project->notification_name = trim((string) $notificationName) ?: null;
                if ($initialImport || $legacyConfiguration) {
                    $project->notification_recipients_initialized = true;
                }
                if ($initialImport) {
                    foreach ($selected as $recipient) {
                        $project->notificationRecipients()->create([
                            'source_type' => 'project',
                            'source_id' => null,
                            'source_key' => 'project:'.Str::uuid()->toString(),
                            'channel' => $recipient['channel'],
                            'value' => $recipient['value'],
                            'recipient_name' => $recipient['name'],
                        ]);
                    }
                }
                $project->save();
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

    public function Servers_AddProjectNotification($projectId, $channel, $value, $recipientName = null)
    {
        try {
            $project = servers_project::find($projectId);
            if (! $project) {
                return [
                    'status' => 0,
                    'message' => 'El proyecto no existe',
                ];
            }

            $notificationData = $this->Servers_ValidateProjectNotification($channel, $value);
            $duplicate = $project->notificationRecipients()
                ->where('channel', $notificationData['channel'])
                ->where('value', $notificationData['value'])
                ->exists();
            if ($duplicate) {
                return [
                    'status' => 0,
                    'message' => 'Este destinatario ya está registrado en el proyecto',
                ];
            }

            DB::transaction(function () use ($project, $notificationData, $recipientName) {
                $project->notificationRecipients()->create([
                    'source_type' => 'project',
                    'source_id' => null,
                    'source_key' => 'project:'.Str::uuid()->toString(),
                    'channel' => $notificationData['channel'],
                    'value' => $notificationData['value'],
                    'recipient_name' => trim((string) $recipientName) ?: null,
                ]);
                $project->notification_recipients_initialized = true;
                $project->save();
            });

            return $this->Servers_ProjectNotificationResponse($project, 'Destinatario agregado');
        } catch (\InvalidArgumentException $exception) {
            return [
                'status' => 0,
                'message' => $exception->getMessage(),
            ];
        } catch (\Throwable $exception) {
            info('Servers_AddProjectNotification error: '.$exception->getMessage());

            return [
                'status' => 0,
                'message' => 'No fue posible agregar el destinatario',
            ];
        }
    }

    public function Servers_UpdateProjectNotification($projectId, $notificationId, $channel, $value, $recipientName = null)
    {
        try {
            $project = servers_project::find($projectId);
            if (! $project) {
                return [
                    'status' => 0,
                    'message' => 'El proyecto no existe',
                ];
            }

            $notification = $project->notificationRecipients()->whereKey($notificationId)->first();
            if (! $notification) {
                return [
                    'status' => 0,
                    'message' => 'El destinatario no existe en este proyecto',
                ];
            }

            $notificationData = $this->Servers_ValidateProjectNotification($channel, $value);
            $duplicate = $project->notificationRecipients()
                ->whereKeyNot($notification->id)
                ->where('channel', $notificationData['channel'])
                ->where('value', $notificationData['value'])
                ->exists();
            if ($duplicate) {
                return [
                    'status' => 0,
                    'message' => 'Este destinatario ya está registrado en el proyecto',
                ];
            }

            $notification->update([
                'source_type' => 'project',
                'source_id' => null,
                'source_key' => $notification->source_type === 'project'
                    ? $notification->source_key
                    : 'project:'.Str::uuid()->toString(),
                'channel' => $notificationData['channel'],
                'value' => $notificationData['value'],
                'recipient_name' => trim((string) $recipientName) ?: null,
            ]);
            $project->notification_recipients_initialized = true;
            $project->save();

            return $this->Servers_ProjectNotificationResponse($project, 'Destinatario actualizado');
        } catch (\InvalidArgumentException $exception) {
            return [
                'status' => 0,
                'message' => $exception->getMessage(),
            ];
        } catch (\Throwable $exception) {
            info('Servers_UpdateProjectNotification error: '.$exception->getMessage());

            return [
                'status' => 0,
                'message' => 'No fue posible actualizar el destinatario',
            ];
        }
    }

    public function Servers_DeleteProjectNotification($projectId, $notificationId)
    {
        try {
            $project = servers_project::find($projectId);
            if (! $project) {
                return [
                    'status' => 0,
                    'message' => 'El proyecto no existe',
                ];
            }

            $notification = $project->notificationRecipients()->whereKey($notificationId)->first();
            if (! $notification) {
                return [
                    'status' => 0,
                    'message' => 'El destinatario no existe en este proyecto',
                ];
            }

            DB::transaction(function () use ($project, $notification) {
                $notification->delete();
                $project->notification_recipients_initialized = true;
                if (! $project->notificationRecipients()->exists()) {
                    $project->notifications_enabled = false;
                }
                $project->save();
            });

            return $this->Servers_ProjectNotificationResponse($project, 'Destinatario eliminado');
        } catch (\Throwable $exception) {
            info('Servers_DeleteProjectNotification error: '.$exception->getMessage());

            return [
                'status' => 0,
                'message' => 'No fue posible eliminar el destinatario',
            ];
        }
    }

    private function Servers_ProjectConfigurationData($project)
    {
        $this->Servers_NormalizeStoredRecipients($project);
        $hasStoredRecipients = $project->notificationRecipients->isNotEmpty();
        $initialized = (bool) $project->notification_recipients_initialized || $hasStoredRecipients;
        if ($hasStoredRecipients && ! $project->notification_recipients_initialized) {
            $project->forceFill([
                'notification_recipients_initialized' => true,
            ])->saveQuietly();
        }
        $initialImportRequired = ! $initialized;
        if ($initialImportRequired && $project->client_id) {
            $initialClient = $this->Servers_GetActiveClientWithRecipients($project->client_id);
            $project->setRelation('client', $initialClient);
        }
        $selected = $project->notificationRecipients
            ->map(function ($recipient) {
                return $this->Servers_StoredRecipient($recipient);
            })
            ->values();
        $available = $initialImportRequired && $project->client
            ? $this->Servers_BuildRecipientsForClient($project->client)
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
                'notification_name' => $project->notification_name,
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
            'notification_recipients_initialized' => $initialized,
            'needs_initial_import' => $initialImportRequired && $project->client_id !== null,
        ];
    }

    private function Servers_ProjectNotificationResponse($project, $message)
    {
        $project->load([
            'host',
            'client',
            'notificationRecipients',
        ]);

        return [
            'status' => 1,
            'message' => $message,
            'data' => $this->Servers_ProjectConfigurationData($project),
        ];
    }

    private function Servers_NormalizeStoredRecipients($project)
    {
        foreach ($project->notificationRecipients as $recipient) {
            if ($recipient->source_type === 'project' && $recipient->source_id === null) {
                continue;
            }

            $recipient->forceFill([
                'source_type' => 'project',
                'source_id' => null,
                'source_key' => 'project:'.Str::uuid()->toString(),
            ])->saveQuietly();
            $recipient->source_type = 'project';
            $recipient->source_id = null;
        }
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
            'id' => (int) $recipient->id,
            'key' => $recipient->source_key,
            'source_type' => $recipient->source_type,
            'source_id' => $recipient->source_id ? (int) $recipient->source_id : null,
            'channel' => $recipient->channel,
            'value' => $recipient->value,
            'name' => $recipient->recipient_name ?: '',
            'source_label' => 'Propio del proyecto',
            'selected' => true,
            'available' => false,
        ];
    }

    private function Servers_ValidateProjectNotification($channel, $value)
    {
        $channel = strtolower(trim((string) $channel));
        $value = trim((string) $value);

        if ($channel === 'email' && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('El correo del destinatario no es válido');
        }

        if ($channel === 'phone') {
            $digits = preg_replace('/\D+/', '', $value);
            if ($digits === '' || strlen($digits) < 7 || strlen($digits) > 15) {
                throw new \InvalidArgumentException('El teléfono del destinatario no es válido');
            }
        }

        return [
            'channel' => $channel,
            'value' => $value,
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
