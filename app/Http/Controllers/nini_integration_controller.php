<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\traits\nini_integration_trait;

/**
 * Nini Integration Controller
 * 
 * API controller for handling sync requests from nini_admin_app.
 * Protected by api_token middleware for authentication.
 */
class nini_integration_controller extends Controller
{
    use nini_integration_trait;

    /**
     * Health check endpoint
     * GET /api/nini-integration/health
     */
    public function health()
    {
        $result = $this->NiniIntegration_HealthCheck();
        return response()->json($result);
    }

    /**
     * Sync a wallet recharge from nini_admin_app
     * POST /api/nini-integration/sync-recharge
     * 
     * Expected payload:
     * {
     *   "source": "nini_admin_app",
     *   "nini_transaction_id": int,
     *   "nini_transaction_unique_id": string,
     *   "nini_transaction_reference": string,
     *   "company_nit": string,
     *   "company_name": string,
     *   "company_legal_name": string,
     *   "company_email": string|null,
     *   "company_phone": string|null,
     *   "company_address": string|null,
     *   "recharge_amount": float,
     *   "bonus_amount": float,
     *   "total_amount": float,
     *   "currency": string,
     *   "payment_date": string (Y-m-d),
     *   "payment_reference": string,
     *   "payment_method": string,
     *   "description": string,
     *   "transaction_created_at": string (ISO 8601),
     *   "bonus_percentage": float
     * }
     */
    public function syncRecharge(Request $request)
    {
        try {
            $data = $request->all();

            info('NiniIntegration: Incoming sync-recharge request', [
                'nini_transaction_id' => $data['nini_transaction_id'] ?? null,
                'company_nit' => $data['company_nit'] ?? null,
                'total_amount' => $data['total_amount'] ?? null,
                'ip' => $request->ip(),
            ]);

            $result = $this->NiniIntegration_SyncRecharge($data);

            $httpStatus = $result['status'] === 1 ? 200 : 422;
            return response()->json($result, $httpStatus);

        } catch (\Exception $e) {
            info('nini_integration_controller syncRecharge error: ' . $e->getMessage());
            return response()->json([
                'status' => 0,
                'message' => 'Error interno del servidor: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
}
