<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\traits\mail_trait;

/**
 * Web Integration Controller
 *
 * Receives API requests from opzio_web and delegates to ERP services.
 * Protected by web_api_token middleware.
 */
class web_integration_controller extends Controller
{
    use mail_trait;

    /**
     * Send a contact form email received from opzio.co
     * POST /api/web-integration/contact-mail
     * Headers: X-Api-Token: <token>
     * Body: { name, email, message }
     */
    public function sendContactMail(Request $request)
    {
        $validated = $request->validate([
            'name'    => ['required', 'string', 'min:2', 'max:100'],
            'email'   => ['required', 'email', 'max:150'],
            'message' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $result = $this->SendMail(
            ['subject' => 'Contacto desde opzio.co — ' . $validated['name']],
            [['address' => 'info@opzio.co', 'name' => 'Opzio']],
            'emails.web-contact',
            $validated,
            null,
            null,
            null,
            null
        );

        if ($result['status'] === 1) {
            return response()->json([
                'status'  => 1,
                'message' => 'Correo en cola para envío',
            ]);
        }

        info('web_integration_controller@sendContactMail error: ' . $result['message']);
        return response()->json([
            'status'  => 0,
            'message' => 'Error al procesar el correo',
        ], 500);
    }
}
