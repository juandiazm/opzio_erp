<?php

namespace App\traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\client;
use App\Models\license;
use App\Models\service;
use App\Models\income;
use App\Models\income_license;

/**
 * Nini Integration Trait
 * 
 * Handles incoming recharge sync requests from nini_admin_app.
 * Resilient workflow:
 * 1. Verify/create client by NIT
 * 2. Verify/create Nini P.O.S. license for client
 * 3. Create income as paid (state=3)
 * 4. Mark payment data
 * 5. Generate Siigo electronic invoice
 * 
 * Uses existing traits: incomes_trait, clients_trait (which includes siigo_new_trait)
 */
trait nini_integration_trait
{
    use incomes_trait;

    /* ==================== Main Entry Point ==================== */

    /**
     * Process a wallet recharge sync from nini_admin_app.
     * Creates client if needed, license if needed, income as paid, and Siigo invoice.
     * 
     * @param array $data Recharge payload from nini_admin_app
     * @return array Response with income and invoice data
     */
    public function NiniIntegration_SyncRecharge(array $data): array
    {
        $response = ['status' => 0, 'message' => '', 'data' => null];

        try {
            // Validate required fields
            $validation = $this->NiniIntegration_ValidatePayload($data);
            if ($validation['status'] !== 1) {
                return $validation;
            }

            // Check for duplicate processing (idempotency by nini_transaction_id)
            $existingIncome = $this->NiniIntegration_FindExistingIncome($data['nini_transaction_reference']);
            if ($existingIncome) {
                $response['status'] = 1;
                $response['message'] = 'Transacción ya fue procesada anteriormente';
                $response['data'] = [
                    'income_id' => $existingIncome->id,
                    'income_unique_id' => $existingIncome->unique_id,
                    'client_id' => $existingIncome->client_id,
                    'siigo_invoice_id' => $existingIncome->siigo_invoice_id,
                    'siigo_invoice_url' => $existingIncome->siigo_invoice_url,
                    'already_processed' => true,
                ];
                return $response;
            }

            DB::beginTransaction();

            try {
                // Step 1: Ensure client exists
                $clientResult = $this->NiniIntegration_EnsureClient($data);
                if ($clientResult['status'] !== 1) {
                    DB::rollBack();
                    return $clientResult;
                }
                $client = $clientResult['data'];

                // Step 2: Ensure Nini license exists for client
                $licenseResult = $this->NiniIntegration_EnsureLicense($client, $data);
                if ($licenseResult['status'] !== 1) {
                    DB::rollBack();
                    return $licenseResult;
                }
                $niniLicense = $licenseResult['data'];

                // Step 3: Create income as paid
                $incomeResult = $this->NiniIntegration_CreatePaidIncome($client, $niniLicense, $data);
                if ($incomeResult['status'] !== 1) {
                    DB::rollBack();
                    return $incomeResult;
                }
                $incomeData = $incomeResult['data'];

                DB::commit();

                // Step 4: Generate Siigo electronic invoice (outside transaction — non-critical)
                $siigoResult = $this->NiniIntegration_GenerateSiigoInvoice($incomeData['income']);

                $response['status'] = 1;
                $response['message'] = 'Recarga sincronizada exitosamente';
                $response['data'] = [
                    'income_id' => $incomeData['income']->id,
                    'income_unique_id' => $incomeData['income']->unique_id,
                    'client_id' => $client->id,
                    'siigo_invoice_id' => $incomeData['income']->siigo_invoice_id,
                    'siigo_invoice_url' => $incomeData['income']->siigo_invoice_url,
                    'already_processed' => false,
                ];

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            info('NiniIntegration_SyncRecharge error: ' . $e->getMessage());
            info('NiniIntegration_SyncRecharge trace: ' . $e->getTraceAsString());
            $response['message'] = 'Error al sincronizar recarga: ' . $e->getMessage();
        }

        return $response;
    }

    /* ==================== Validation ==================== */

    /**
     * Validate the incoming payload
     */
    private function NiniIntegration_ValidatePayload(array $data): array
    {
        $required = [
            'company_nit',
            'company_name',
            'total_amount',
            'payment_date',
            'nini_transaction_reference',
        ];

        foreach ($required as $field) {
            if (empty($data[$field]) && $data[$field] !== 0 && $data[$field] !== '0') {
                return [
                    'status' => 0,
                    'message' => "Campo requerido faltante: {$field}",
                    'data' => null,
                ];
            }
        }

        if (floatval($data['total_amount']) <= 0) {
            return [
                'status' => 0,
                'message' => 'El monto total debe ser mayor a 0',
                'data' => null,
            ];
        }

        return ['status' => 1, 'message' => 'OK', 'data' => null];
    }

    /* ==================== Idempotency Check ==================== */

    /**
     * Check if this transaction was already processed
     */
    private function NiniIntegration_FindExistingIncome(string $reference): ?income
    {
        // Search by description containing the nini reference
        return income::where('payment_reference', 'LIKE', '%' . $reference . '%')
            ->where('description', 'LIKE', '%Nini%')
            ->first();
    }

    /* ==================== Client Management ==================== */

    /**
     * Find or create client by NIT (company identification)
     */
    private function NiniIntegration_EnsureClient(array $data): array
    {
        $response = ['status' => 0, 'message' => '', 'data' => null];

        try {
            $nit = $this->NiniIntegration_CleanNit($data['company_nit']);

            // Try to find existing client by identification (NIT)
            $client = client::where('identification', $nit)
                ->orWhere('identification', $data['company_nit']) // Try with original format too
                ->first();

            if ($client) {
                info('NiniIntegration: Found existing client ID ' . $client->id . ' for NIT ' . $nit);
                
                // Ensure client has Siigo ID, create if missing
                if (empty($client->siigo_id)) {
                    $this->NiniIntegration_EnsureClientInSiigo($client);
                }

                $response['status'] = 1;
                $response['message'] = 'Cliente existente encontrado';
                $response['data'] = $client;
                return $response;
            }

            // Client doesn't exist — create it
            info('NiniIntegration: Creating new client for NIT ' . $nit);

            $companyName = $data['company_name'] ?? 'Sin nombre';
            $legalName = $data['company_legal_name'] ?? $companyName;
            $email = $data['company_email'] ?? null;
            $phone = $data['company_phone'] ?? null;
            $address = $data['company_address'] ?? 'Sin dirección';

            // Parse name and lastname from company name
            $nameParts = $this->NiniIntegration_ParseCompanyName($legalName);

            // Determine identification type (NIT if has '-', else cédula)
            $identificationType = strpos($data['company_nit'], '-') !== false ? 0 : 1;

            // Create client record
            $client = new client();
            $client->unique_id = strtoupper(Str::uuid()->toString());
            $client->name = $nameParts['name'];
            $client->lastname = $nameParts['lastname'];
            $client->email = $email ?? 'nini-' . $nit . '@nocliente.com';
            $client->identification_type = $identificationType;
            $client->identification = $nit;
            $client->country_id = 1; // Colombia
            $client->address = $address;
            $client->phone = $phone ?? '';
            $client->sector_id = 1; // Default sector
            $client->value_per_hour = 0;
            $client->description = 'Cliente creado automáticamente desde Nini P.O.S. - NIT: ' . $data['company_nit'];
            $client->verified = 1;
            $client->verified_date = Carbon::now();
            $client->active = 1;
            $client->electronic_invoice = 1;

            // Create client in Siigo
            $siigoResponse = $this->SiigoNew_AddClient(
                $nit,
                trim($nameParts['name']) ?: 'N/A',
                trim($nameParts['lastname']) ?: 'N/A',
                $client->email,
                $client->phone ?: '0000000',
                $client->address ?: 'Sin dirección',
                true // IVAMandatory
            );

            if ($siigoResponse['status'] == 1 && !empty($siigoResponse['data']['id'])) {
                $client->siigo_id = $siigoResponse['data']['id'];
            } else {
                info('NiniIntegration: Warning - could not create client in Siigo: ' . json_encode($siigoResponse));
                // Continue anyway — Siigo invoice might fail later but client can still be created
            }

            $client->save();

            info('NiniIntegration: Created client ID ' . $client->id . ' for NIT ' . $nit);

            $response['status'] = 1;
            $response['message'] = 'Cliente creado exitosamente';
            $response['data'] = $client;

        } catch (\Exception $e) {
            info('NiniIntegration_EnsureClient error: ' . $e->getMessage());
            $response['message'] = 'Error al verificar/crear cliente: ' . $e->getMessage();
        }

        return $response;
    }

    /**
     * Ensure client exists in Siigo (for existing clients missing siigo_id)
     */
    private function NiniIntegration_EnsureClientInSiigo(client $client): void
    {
        try {
            $siigoResponse = $this->SiigoNew_AddClient(
                $client->identification,
                trim($client->name) ?: 'N/A',
                trim($client->lastname) ?: 'N/A',
                $client->email ?? 'sin-email@temp.com',
                $client->phone ?? '0000000',
                $client->address ?? 'Sin dirección',
                true
            );

            if ($siigoResponse['status'] == 1 && !empty($siigoResponse['data']['id'])) {
                $client->siigo_id = $siigoResponse['data']['id'];
                $client->save();
                info('NiniIntegration: Updated client ' . $client->id . ' with Siigo ID ' . $client->siigo_id);
            } else {
                info('NiniIntegration: Could not create Siigo client for ID ' . $client->id . ': ' . json_encode($siigoResponse));
            }
        } catch (\Exception $e) {
            info('NiniIntegration_EnsureClientInSiigo error: ' . $e->getMessage());
        }
    }

    /* ==================== License Management ==================== */

    /**
     * Find or create a Nini P.O.S. license for the client
     */
    private function NiniIntegration_EnsureLicense(client $client, array $data): array
    {
        $response = ['status' => 0, 'message' => '', 'data' => null];

        try {
            $niniServiceId = config('services.nini_integration.service_id', 4);

            // Find existing Nini license for this client
            $license = license::where('client_id', $client->id)
                ->where('service_id', $niniServiceId)
                ->whereNull('deleted_at')
                ->first();

            if ($license) {
                info('NiniIntegration: Found existing license ID ' . $license->id . ' for client ' . $client->id);
                $response['status'] = 1;
                $response['message'] = 'Licencia existente encontrada';
                $response['data'] = $license;
                return $response;
            }

            // Create new Nini license
            info('NiniIntegration: Creating new Nini license for client ' . $client->id);

            $serviceName = config('services.nini_integration.service_name', 'Software de Facturación P.O.S');
            $companyName = $data['company_name'] ?? $client->name;

            $license = new license();
            $license->unique_id = strtoupper(Str::uuid()->toString());
            $license->active = 1;
            $license->client_id = $client->id;
            $license->name = 'Nini P.O.S. - ' . $companyName;
            $license->service_id = $niniServiceId;
            $license->employee_id = config('services.nini_integration.employee_id', null);
            $license->value = 0; // Nini manages its own pricing
            $license->comission = 0;
            $license->type = 1; // Recurrente
            $license->recurrence_months = 1;
            $license->billing_day = Carbon::now()->day;
            $license->days_to_expire = 30;
            $license->last_billing_date = Carbon::now();
            $license->next_billing_date = Carbon::now()->addMonth();
            $license->last_payed_date = Carbon::now();
            $license->remaining_days = 30;
            $license->user_key = 'nini_' . Str::random(20);
            $license->password_key = 'nini_' . Str::random(20);
            $license->description = 'Licencia Nini P.O.S. creada automáticamente por integración - NIT: ' . ($data['company_nit'] ?? '');
            $license->save();

            info('NiniIntegration: Created license ID ' . $license->id . ' for client ' . $client->id);

            $response['status'] = 1;
            $response['message'] = 'Licencia creada exitosamente';
            $response['data'] = $license;

        } catch (\Exception $e) {
            info('NiniIntegration_EnsureLicense error: ' . $e->getMessage());
            $response['message'] = 'Error al verificar/crear licencia: ' . $e->getMessage();
        }

        return $response;
    }

    /* ==================== Income Creation ==================== */

    /**
     * Create a paid income for the Nini wallet recharge
     */
    private function NiniIntegration_CreatePaidIncome(client $client, license $license, array $data): array
    {
        $response = ['status' => 0, 'message' => '', 'data' => null];

        try {
            $totalAmount = floatval($data['total_amount']);
            $rechargeAmount = floatval($data['recharge_amount'] ?? $totalAmount);
            $paymentDate = $data['payment_date'] ?? Carbon::now()->format('Y-m-d');
            $paymentReference = $data['nini_transaction_reference'] ?? '';
            $description = $data['description'] ?? 'Recarga billetera Nini P.O.S.';

            $niniServiceId = config('services.nini_integration.service_id', 4);
            $niniServiceName = config('services.nini_integration.service_name', 'Software de Facturación P.O.S');
            $employeeId = config('services.nini_integration.employee_id', null);
            $employeeName = config('services.nini_integration.employee_name', null);

            // Build license items for the income
            $licenseItems = [];

            // Main recharge item
            $licenseItems[] = [
                'license_id' => $license->id,
                'license_name' => 'Recarga Billetera Nini P.O.S.',
                'service_id' => $niniServiceId,
                'service_name' => $niniServiceName,
                'recurrence_months' => null,
                'value' => $rechargeAmount,
                'comission' => 0,
                'employee_id' => $employeeId,
                'employee_name' => $employeeName,
                'tax_id' => null,
                'tax_name' => null,
                'tax_value' => 0,
                'description' => $description,
                'total' => $rechargeAmount,
                'hours' => 0,
            ];

            // Create the income directly (state=3 = Pagada)
            $now = Carbon::now();
            $incomeTotal = collect($licenseItems)->sum('total');

            $income = new income();
            $income->unique_id = strtoupper(Str::uuid()->toString());
            $income->state = 3; // Pagada
            $income->client_id = $client->id;
            $income->client_identification = $client->identification;
            $income->client_name = $client->name . ($client->lastname ? ' ' . $client->lastname : '');
            $income->timely_payment = Carbon::parse($paymentDate);
            $income->cutoff_date = Carbon::parse($paymentDate);
            $income->description = $description;
            $income->total = $incomeTotal;
            $income->payment_state = 1; // Pagado
            $income->payment_date = Carbon::parse($paymentDate);
            $income->payment_reference = 'NINI-' . $paymentReference;
            $income->created_at = $now;
            $income->save();

            // Create income license items
            foreach ($licenseItems as $item) {
                $incomeLicense = new income_license();
                $incomeLicense->income_id = $income->id;
                $incomeLicense->license_id = $item['license_id'];
                $incomeLicense->license_name = $item['license_name'];
                $incomeLicense->timely_payment = Carbon::parse($paymentDate);
                $incomeLicense->service_id = $item['service_id'];
                $incomeLicense->service_name = $item['service_name'];
                $incomeLicense->recurrence_months = $item['recurrence_months'];
                $incomeLicense->value = $item['value'];
                $incomeLicense->comission = $item['comission'];
                $incomeLicense->employee_id = $item['employee_id'];
                $incomeLicense->employee_name = $item['employee_name'];
                $incomeLicense->tax_id = $item['tax_id'];
                $incomeLicense->tax_name = $item['tax_name'];
                $incomeLicense->tax_value = $item['tax_value'];
                $incomeLicense->description = $item['description'];
                $incomeLicense->total = $item['total'];
                $incomeLicense->hours = $item['hours'];
                $incomeLicense->save();
            }

            info('NiniIntegration: Created income ID ' . $income->id . ' for client ' . $client->id . ' amount ' . $incomeTotal);

            $response['status'] = 1;
            $response['message'] = 'Ingreso creado exitosamente';
            $response['data'] = [
                'income' => $income,
                'license_items' => $licenseItems,
            ];

        } catch (\Exception $e) {
            info('NiniIntegration_CreatePaidIncome error: ' . $e->getMessage());
            $response['message'] = 'Error al crear ingreso: ' . $e->getMessage();
        }

        return $response;
    }

    /* ==================== Siigo Electronic Invoice ==================== */

    /**
     * Generate Siigo electronic invoice for the income.
     * Non-critical — if it fails, the income still exists and can be invoiced later.
     */
    private function NiniIntegration_GenerateSiigoInvoice(income $income): array
    {
        $response = ['status' => 0, 'message' => '', 'data' => null];

        try {
            // Skip if already has Siigo invoice
            if (!empty($income->siigo_invoice_id)) {
                $response['status'] = 1;
                $response['message'] = 'Ya tiene factura Siigo';
                return $response;
            }

            // Use the existing Income_CreateSiigoInvoice method.
            // Pass markAsPaid=true so the Siigo invoice is recorded as paid
            // immediately (no due_date), since the recharge was already paid.
            $siigoResult = $this->Income_CreateSiigoInvoice($income, true);

            if ($siigoResult['status'] == 1) {
                // Reload income to get updated Siigo fields
                $income->refresh();
                
                $response['status'] = 1;
                $response['message'] = 'Factura electrónica generada exitosamente';
                $response['data'] = $siigoResult['data'] ?? null;

                info('NiniIntegration: Siigo invoice created for income ' . $income->id . ' - Invoice: ' . $income->siigo_invoice_id);
            } else {
                info('NiniIntegration: Siigo invoice failed for income ' . $income->id . ': ' . ($siigoResult['message'] ?? 'unknown'));
                $response['message'] = 'Error al generar factura Siigo: ' . ($siigoResult['message'] ?? 'Error desconocido');
            }

        } catch (\Exception $e) {
            info('NiniIntegration_GenerateSiigoInvoice error: ' . $e->getMessage());
            $response['message'] = 'Error Siigo: ' . $e->getMessage();
        }

        return $response;
    }

    /* ==================== Helpers ==================== */

    /**
     * Clean NIT to a standard format (remove verification digit)
     */
    private function NiniIntegration_CleanNit(string $nit): string
    {
        // Remove spaces
        $nit = trim($nit);
        
        // If has dash (NIT format like 900123456-1), keep full format for search
        // but also store the base number
        return $nit;
    }

    /**
     * Parse company name into name/lastname parts for client creation
     */
    private function NiniIntegration_ParseCompanyName(string $fullName): array
    {
        $parts = explode(' ', trim($fullName));

        if (count($parts) <= 1) {
            return ['name' => $fullName, 'lastname' => ''];
        }

        // First word as name, rest as lastname
        $name = array_shift($parts);
        $lastname = implode(' ', $parts);

        return ['name' => $name, 'lastname' => $lastname];
    }

    /* ==================== Health Check ==================== */

    /**
     * Simple health check endpoint for nini to verify connectivity
     */
    public function NiniIntegration_HealthCheck(): array
    {
        return [
            'status' => 1,
            'message' => 'Opzio ERP - Nini Integration Active',
            'data' => [
                'app' => 'opzio_erp',
                'integration' => 'nini',
                'timestamp' => Carbon::now()->toIso8601String(),
                'version' => '1.0.0',
            ],
        ];
    }
}
