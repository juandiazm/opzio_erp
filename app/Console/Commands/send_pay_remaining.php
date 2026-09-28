<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use App\traits\licenses_trait;
use App\traits\open_ia_trait;
use App\traits\mail_trait;
use App\traits\twilio_sms_trait;
use App\traits\whatsapp_notifications_trait;
use App\traits\incomes_trait;
use App\Models\sms_log;

class send_pay_remaining extends Command
{
    use 
    licenses_trait
    ,open_ia_trait
    ,mail_trait
    ,twilio_sms_trait
    ,whatsapp_notifications_trait
    ,incomes_trait
    ;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:send_pay_remaining';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send pay remaining to all users who have not paid yet.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    private function randomSendAt()
    {
        return Carbon::today(config('app.timezone'))->setTime(8, 0)->addMinutes(random_int(0, 420));
    }

    private function reminderChannelLabel(?string $channel): string
    {
        return match (strtolower(trim((string) $channel))) {
            'email' => 'Email',
            'sms' => 'SMS',
            'whatsapp' => 'WhatsApp',
            default => 'Varios canales',
        };
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Get all overdue incomes (both static and recurring)
        $incomesResponse = $this->Income_GetAllOverdueIncomes();
        $incomes = collect($incomesResponse['data'] ?? []);
        $portfolioIncomes = collect($incomesResponse['portfolio'] ?? $incomes);
        $sendableIncomeIds = $incomes->pluck('unique_id')->flip();
        $portfolioTotalsByClient = $portfolioIncomes
            ->filter(fn ($income) => $income->client && $income->client->active == 1)
            ->groupBy(fn ($income) => $income->client->id)
            ->map(fn ($clientIncomes) => $clientIncomes->sum(fn ($income) => (float) $income->total));
        info('command:send_pay_remaining - Total overdue incomes found: ' . count($portfolioIncomes));
        
        $ia_messages = [];
        $report_message = [];
        $report_clients = [];
        
        // Group all incomes by client
        $groupedByClient = [];
        
        // Process the complete overdue portfolio; only incomes in data are eligible for today's reminder.
        foreach($portfolioIncomes as $income) {
            $client = $income->client;
            
            // Skip if client is not active
            if(!$client || $client->active != 1) {
                continue;
            }
            
            $client_id = $client->id;
            
            if(!isset($groupedByClient[$client_id])) {
                $groupedByClient[$client_id] = [
                    'client' => [
                        'id' => $client->id,
                        'name' => $client->name,
                        'identification' => $client->identification,
                    ],
                    'portfolio_total' => (float) $portfolioTotalsByClient->get($client_id, 0),
                    'portfolio_incomes' => [],
                    'portfolio_income_ids' => [],
                    'incomes' => [],
                    'income_ids' => [], // Track unique income IDs
                    'emails' => [],
                    'phones' => [],
                    'whatsapp_recipients' => [],
                    'incomes_by_channel' => [
                        'email' => [],
                        'sms' => [],
                        'whatsapp' => [],
                    ],
                    'notification_income_ids_by_channel' => [
                        'email' => [],
                        'sms' => [],
                        'whatsapp' => [],
                    ],
                    'service_names' => [],
                ];
            }
            
            $reminderChannel = strtolower(trim((string) ($income->reminder_channel ?? '')));
            $incomeData = [
                'unique_id' => $income->unique_id,
                'client_name' => $income->client_name,
                'client_identification' => $income->client_identification,
                'timely_payment' => $income->timely_payment,
                'cutoff_date' => $income->cutoff_date,
                'total' => $income->total,
                'payment_link' => $income->payment_link,
                'state' => $income->state,
                'days_overdue' => $income->days_overdue,
                'reminder_channel' => $reminderChannel ?: null,
                'siigo_invoice_url' => $income->siigo_invoice_url,
                'has_electronic_invoice' => !empty($income->siigo_invoice_url),
            ];

            if(!in_array($income->unique_id, $groupedByClient[$client_id]['portfolio_income_ids'], true)){
                $groupedByClient[$client_id]['portfolio_incomes'][] = $incomeData;
                $groupedByClient[$client_id]['portfolio_income_ids'][] = $income->unique_id;
            }

            if (!$sendableIncomeIds->has($income->unique_id)) {
                continue;
            }

            // Check if this income is already added (avoid duplicates)
            if(!in_array($income->unique_id, $groupedByClient[$client_id]['income_ids'], true)){
                // Add income to client group
                $groupedByClient[$client_id]['incomes'][] = $incomeData;
                
                // Track this income ID
                $groupedByClient[$client_id]['income_ids'][] = $income->unique_id;
                info('command:send_pay_remaining - Added income: ' . $income->unique_id . ' (days overdue: ' . $income->days_overdue . ') for client: ' . $client->name);
            }else{
                info('command:send_pay_remaining - Duplicate income skipped: ' . $income->unique_id . ' for client: ' . $client->name);
                continue;
            }

            $channelsForIncome = $reminderChannel !== ''
                ? [$reminderChannel]
                : ['email', 'sms', 'whatsapp'];
            foreach ($channelsForIncome as $channel) {
                if (!isset($groupedByClient[$client_id]['incomes_by_channel'][$channel])) {
                    continue;
                }
                $alreadyGrouped = collect($groupedByClient[$client_id]['incomes_by_channel'][$channel])
                    ->contains('unique_id', $income->unique_id);
                if (!$alreadyGrouped) {
                    $groupedByClient[$client_id]['incomes_by_channel'][$channel][] = $incomeData;
                }
            }
            
            // Get notification emails and phones from licenses associated with this income
            $license_ids = $income->income_licenses->pluck('license_id')->toArray();
            $notificationsResponse = $this->License_GetLicenseNotificationsByLicensesIds($license_ids);
            $notificationData = collect($notificationsResponse['data'] ?? []);
            $hasContactPayload = $notificationData->contains(function ($item) {
                return is_array($item) && array_key_exists('tags', $item);
            });
            $hasContactSchema = Schema::hasTable('notification_tags')
                && Schema::hasColumn('license_notifications', 'client_id');
            if ($hasContactSchema && ($hasContactPayload || $notificationData->isEmpty())) {
                $tagSlug = config('notifications.collection_tag', 'cobranza');
                $licenseTagged = $notificationData->filter(function ($item) use ($tagSlug) {
                    if (!is_array($item) || !($item['active'] ?? true)) {
                        return false;
                    }
                    return collect($item['tags'] ?? [])->contains(function ($tag) use ($tagSlug) {
                        return data_get($tag, 'slug') === $tagSlug;
                    });
                })->values();
                $notificationsResponse = $licenseTagged->isNotEmpty()
                    ? array_merge($notificationsResponse, ['data' => $licenseTagged->all()])
                    : $this->License_GetTaggedLicenseNotificationsByLicensesIds($license_ids, $client_id, $tagSlug);
            }
            
            if($notificationsResponse['status'] == 1){
                foreach($notificationsResponse['data'] as $item){
                    $email = trim((string) ($item['email'] ?? ''));
                    $phone = trim((string) ($item['phone'] ?? ''));
                    try {
                        $channels = $this->NotificationContact_NormalizeChannels($item['channels'] ?? [], $email, $phone);
                    } catch (\Throwable $exception) {
                        $channels = [];
                        if($email !== '') $channels[] = 'email';
                        if($phone !== '') $channels[] = 'sms';
                    }
                    if(($reminderChannel === '' || $reminderChannel === 'email') && in_array('email', $channels, true) && filter_var($email, FILTER_VALIDATE_EMAIL)){
                        $groupedByClient[$client_id]['emails'][] = $email;
                    }
                    if(($reminderChannel === '' || $reminderChannel === 'sms') && in_array('sms', $channels, true) && $phone !== ''){
                        $groupedByClient[$client_id]['phones'][] = $phone;
                    }
                    if(($reminderChannel === '' || $reminderChannel === 'whatsapp') && in_array('whatsapp', $channels, true) && $phone !== ''){
                        $groupedByClient[$client_id]['whatsapp_recipients'][] = [
                            'phone' => $phone,
                            'name' => trim((string) ($item['name'] ?? '')),
                        ];
                    }
                }
            }
            
            // Collect service names for IA message
            foreach($income->income_licenses as $incomeLicense){
                if($incomeLicense->license && isset($incomeLicense->license->service)){
                    $serviceName = $incomeLicense->license->service->name ?? 'nuestros servicios';
                    if(!in_array($serviceName, $groupedByClient[$client_id]['service_names'])){
                        $groupedByClient[$client_id]['service_names'][] = $serviceName;
                    }
                }
            }
        }
        
        // Send one email per client with all their incomes
        foreach($groupedByClient as $client_id => $clientData) {
            info('command:send_pay_remaining - Processing client: ' . $clientData['client']['name'] . ' with ' . count($clientData['incomes']) . ' income(s)');
            $sendAt = $this->randomSendAt();
            $notificationChannels = [];
            $channelSummary = [];
            foreach ($clientData['incomes'] as $income) {
                $channel = $income['reminder_channel'] ?: 'multiple';
                if (!isset($channelSummary[$channel])) {
                    $channelSummary[$channel] = [
                        'channel' => $channel,
                        'channel_label' => $this->reminderChannelLabel($channel),
                        'orders' => 0,
                        'total' => 0,
                    ];
                }
                $channelSummary[$channel]['orders']++;
                $channelSummary[$channel]['total'] += (float) $income['total'];
            }
            
            try{
                // Generate IA message using the first service name
                $firstServiceName = !empty($clientData['service_names']) ? $clientData['service_names'][0] : 'nuestros servicios';
                
                if(!array_key_exists($firstServiceName, $ia_messages)){
                    $ResponseIA = $this->OpenIA_MakeQuestion(
                        'Eres una empresa de software, debes escribir un mensaje publicitario hacia uno de tus clientes usando tuteo (tú). Debes incentivar al cliente a consumir los servicios de '.$firstServiceName.' Y explicarle por qué este servicio agrega valor a sus negocios. No más de 150 caracteres. Usa un lenguaje profesional y cercano tuteando, no agregues hashtags, sin llamados a la acción.'
                    );
                    if($ResponseIA['status']==1){
                        $ia_message = $ResponseIA['data'][0];
                    }else{
                        $ia_message = 'Optimiza tu productividad y eficiencia con nuestra solución de software: automatización inteligente para simplificar tus procesos comerciales.';
                    }
                    $ia_messages[$firstServiceName] = $ia_message;
                }else{
                    $ia_message = $ia_messages[$firstServiceName];
                }
                
                // Prepare emails
                $uniqueEmails = array_unique($clientData['emails']);
                $Mails = [];
                foreach($uniqueEmails as $email){
                    $Mails[] = [
                        'address' => $email,
                        'name' => $email,
                    ];
                }
                $emailIncomes = $clientData['incomes_by_channel']['email'];
                
                $deferEmailOnSunday = $this->Mail_ShouldDeferExternalOnSunday(
                    ['_defer_external_on_sunday' => true],
                    $Mails
                );
                if(count($Mails) > 0 && $emailIncomes && !$deferEmailOnSunday){
                    // Prepare attachments - all PDFs for this client
                    $attachments = [];
                    foreach($emailIncomes as $income){
                        $pdfPath = storage_path('app/public/incomes/pdfs/' . $income['unique_id'] . '.pdf');
                        if(file_exists($pdfPath)){
                            $order_id = substr($income['unique_id'], -10);
                            $attachments[] = [
                                'path' => $pdfPath,
                                'name' => ($income['state']==2?'Orden de compra':'Cotización') . '_' . $order_id.'.pdf'
                            ];
                        }
                    }
                    
                    // Check if all incomes have electronic invoices
                    $hasElectronicInvoice = collect($emailIncomes)->every(function($income) {
                        return $income['has_electronic_invoice'];
                    });
                    
                    // Prepare subject based on invoice type
                    $invoiceCount = count($emailIncomes);
                    if($hasElectronicInvoice){
                        $subject = $invoiceCount > 1 
                            ? 'Tienes ' . $invoiceCount . ' facturas electrónicas pendientes' 
                            : 'Recuerda realizar tu pago - Factura #' . substr($emailIncomes[0]['unique_id'], -10);
                    } else {
                        $subject = $invoiceCount > 1 
                            ? 'Tienes ' . $invoiceCount . ' órdenes de compra pendientes' 
                            : 'Recuerda realizar tu pago #' . substr($emailIncomes[0]['unique_id'], -10);
                    }
                    
                    $MailData = [
                        'subject' => $subject,
                    ];
                    
                    // Select appropriate view based on invoice type
                    $View = $hasElectronicInvoice ? 'mail.pay_remaining_grouped_invoice' : 'mail.pay_remaining_grouped';
                    $ViewData = collect([
                        'client' => $clientData['client'],
                        'incomes' => $emailIncomes,
                        'ia_message' => $ia_message,
                        '_defer_external_on_sunday' => true,
                    ]);
                    
                    $mailLog = $this->MailLog_CreatePending(
                        $subject,
                        $View,
                        config('mail.from.address') ?? '',
                        config('mail.from.name') ?? '',
                        $Mails,
                        $ViewData,
                        $attachments,
                        $sendAt
                    );
                    if ($mailLog) {
                        $notificationChannels[] = 'email';
                        $clientData['notification_income_ids_by_channel']['email'] = array_column($emailIncomes, 'unique_id');
                        info('command:send_pay_remaining - Email queued in mail_logs: ' . $mailLog->unique_id . ' for ' . $sendAt->format('Y-m-d H:i:s'));
                    }
                }
            }catch(\Exception $e) {
                info('command:send_pay_remaining email grouped: '.$e->getMessage());
            }
            
            // Send SMS for each income (keeping individual SMS as they have character limits)
            $smsQueued = false;
            try{
                $uniquePhones = array_unique($clientData['phones']);
                $smsIncomes = $clientData['incomes_by_channel']['sms'];
                
                // Check if all incomes have electronic invoices
                $hasElectronicInvoice = collect($smsIncomes)->every(function($income) {
                    return $income['has_electronic_invoice'];
                });
                
                foreach($uniquePhones as $phone){
                    // Send one SMS with summary if multiple incomes, or detailed if just one
                    if(count($smsIncomes) > 1){
                        $totalAmount = array_sum(array_column($smsIncomes, 'total'));
                        $documentType = $hasElectronicInvoice ? 'facturas electrónicas' : 'órdenes de compra';
                        $MessageValue = 'Hola '.$clientData['client']['name'].', tienes '.count($smsIncomes).' '.$documentType.' pendientes por un total de COP $'.number_format($totalAmount, 0,',','.').'. Revisa tu correo para más detalles y enlaces de pago.';
                    }else{
                        $income = $smsIncomes[0] ?? null;
                        if (!$income) {
                            continue;
                        }
                        $order_id = substr($income['unique_id'], -10);
                        $documentType = $hasElectronicInvoice ? 'Factura' : 'Orden de compra';
                        $MessageValue = 'Hola '.$clientData['client']['name'].', generamos la '.$documentType.' #'.$order_id.' por un valor de COP $'.number_format($income['total'], 0,',','.').'. Paga antes del '.$income['cutoff_date'].' en '.$income['payment_link'];
                    }
                    sms_log::create([
                        'unique_id' => strtoupper(Str::uuid()->toString()),
                        'client_id' => $clientData['client']['id'],
                        'recipient_name' => $clientData['client']['name'],
                        'to' => $this->TwilioSMS_NormalizePhone($phone),
                        'body' => $MessageValue,
                        'attempts' => 0,
                        'status' => 0,
                        'send_at' => $sendAt,
                    ]);
                    $smsQueued = true;
                }
            }catch(\Exception $e) {
                info('command:send_pay_remaining sms grouped: '.$e->getMessage());
            }
            if ($smsQueued) {
                $notificationChannels[] = 'sms';
                $clientData['notification_income_ids_by_channel']['sms'] = array_column($smsIncomes, 'unique_id');
            }

            $whatsappQueued = false;
            try{
                $whatsappIncomes = $clientData['incomes_by_channel']['whatsapp'];
                $totalAmount = array_sum(array_column($whatsappIncomes, 'total'));
                $whatsappRecipients = collect($clientData['whatsapp_recipients'])
                    ->map(function ($recipient) {
                        return [
                            'phone' => trim((string) ($recipient['phone'] ?? '')),
                            'name' => trim((string) ($recipient['name'] ?? '')),
                        ];
                    })
                    ->filter(fn ($recipient) => $recipient['phone'] !== '')
                    ->unique(fn ($recipient) => preg_replace('/\D+/', '', $this->TwilioWhatsApp_NormalizePhone($recipient['phone'])))
                    ->values();
                foreach($whatsappRecipients as $recipient){
                    $recipientName = $recipient['name'] ?: $clientData['client']['name'];
                    $contentVariables = $this->Income_PaymentReminderTemplateVariables(
                        $whatsappIncomes[0] ?? [],
                        $recipientName,
                        $totalAmount,
                        $clientData['client']['name'] ?? ($whatsappIncomes[0]['client_name'] ?? null)
                    );
                    $response = $this->Notification_QueueWhatsappTemplate(
                        $recipient['phone'],
                        $clientData['client']['id'],
                        $recipientName,
                        config('notifications.overdue_payment_template_sid', 'HX9990ce79b043c2a8a8fc31aa3b220a46'),
                        $contentVariables,
                        $sendAt
                    );
                    if (($response['status'] ?? 0) !== 1) {
                        info('command:send_pay_remaining whatsapp: '.$response['message']);
                    } else {
                        $whatsappQueued = true;
                    }
                }
            }catch(\Exception $e) {
                info('command:send_pay_remaining whatsapp grouped: '.$e->getMessage());
            }
            if ($whatsappQueued) {
                $notificationChannels[] = 'whatsapp';
                $clientData['notification_income_ids_by_channel']['whatsapp'] = array_column($whatsappIncomes, 'unique_id');
            }

            $notificationChannelLabels = array_map(
                fn ($channel) => $this->reminderChannelLabel($channel),
                $notificationChannels
            );
            $notificationScheduled = !empty($notificationChannels);
            $report_clients[] = [
                'client' => $clientData['client']['name'],
                'identification' => $clientData['client']['identification'],
                'total' => $clientData['portfolio_total'],
                'channels' => array_values($channelSummary),
                'channels_label' => implode(', ', array_column($channelSummary, 'channel_label')),
                'orders' => count($clientData['portfolio_incomes']),
                'notification_scheduled' => $notificationScheduled,
                'notification_status' => $notificationScheduled ? 'Programada' : 'No enviada',
                'notification_channels' => $notificationChannels,
                'notification_channels_label' => $notificationChannelLabels
                    ? implode(', ', $notificationChannelLabels)
                    : 'Ninguno',
                'scheduled_for' => $notificationScheduled ? $sendAt->format('Y-m-d H:i:s') : null,
            ];
            
            // Add each unique overdue income to report (no duplicates)
            foreach($clientData['portfolio_incomes'] as $income){
                $incomeNotificationChannels = [];
                foreach ($clientData['notification_income_ids_by_channel'] as $channel => $incomeIds) {
                    if (in_array($income['unique_id'], $incomeIds, true)) {
                        $incomeNotificationChannels[] = $channel;
                    }
                }
                $incomeIsSendable = in_array($income['unique_id'], $clientData['income_ids'], true);
                $incomeNotificationChannelLabels = array_map(
                    fn ($channel) => $this->reminderChannelLabel($channel),
                    $incomeNotificationChannels
                );
                $incomeNotificationScheduled = !empty($incomeNotificationChannels);
                $report_message[] = [
                    'client' => $clientData['client']['name'],
                    'identification' => $clientData['client']['identification'],
                    'total' => $income['total'],
                    'order_id' => substr($income['unique_id'], -10),
                    'days_overdue' => $income['days_overdue'],
                    'channel' => $incomeIsSendable
                        ? ($income['reminder_channel'] ?: 'multiple')
                        : 'none',
                    'channel_label' => $incomeIsSendable
                        ? ($income['reminder_channel']
                            ? $this->reminderChannelLabel($income['reminder_channel'])
                            : 'Varios canales')
                        : 'Sin programación',
                    'scheduled_for' => $incomeNotificationScheduled ? $sendAt->format('Y-m-d H:i:s') : null,
                    'siigo_invoice_url' => $income['siigo_invoice_url'] ?? null,
                    'notification_scheduled' => $incomeNotificationScheduled,
                    'notification_channels' => $incomeNotificationChannels,
                    'notification_channels_label' => $incomeNotificationChannelLabels
                        ? implode(', ', $incomeNotificationChannelLabels)
                        : 'Ninguno',
                ];
            }
        }
        
        //send email report to admin
        if(count($report_clients) > 0){
            $Mails = [
                [
                    'address' => 'juandiazm@opzio.co',
                    'name' => 'Juan Diaz',
                ]
            ];
            $MailData = 
            [
                'subject' => 'Reporte de envíos de recordatorio de pago',
            ];
            $View = 'mail.pay_remaining_report';
            $ViewData = collect(
            [
                'report_message' => $report_message,
                'report_clients' => $report_clients,
            ]
            );
            $MailResponse = $this->SendMail($MailData, $Mails, $View, $ViewData, null);
            if($MailResponse['status'] == 1){
                info('command:send_pay_remaining report: Email sent successfully');
            }else{
                info('command:send_pay_remaining report: Error sending email: '.$MailResponse['message']);
            }
        }
        return 0;
    }
}
