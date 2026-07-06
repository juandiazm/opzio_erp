<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Session;

use App\traits\ia_assistant_trait;
use App\traits\pdf_trait;
use App\traits\mail_trait;
use App\Models\ia_conversation;
use App\Models\ia_turn;
use App\Models\client;
use App\Models\user;

class ia_assistant_controller extends Controller
{
    use ia_assistant_trait;
    use pdf_trait;
    use mail_trait;

    // ─── Return client list for the dropdown ─────────────────────────────────
    public function get_clients_list()
    {
        try {
            $clients = client::where('active', 1)
                ->orderBy('name')
                ->get(['id', 'name', 'lastname', 'photo', 'email']);

            return response()->json([
                'status' => 1,
                'data'   => $clients,
            ]);
        } catch (\Exception $e) {
            info('ia_assistant_controller::get_clients_list ' . $e->getMessage());
            return response()->json(['status' => 0, 'message' => $e->getMessage()], 400);
        }
    }

    // ─── Generate first-turn report ───────────────────────────────────────────
    public function generate_report(Request $request)
    {
        try {
            $request->validate([
                'client_id'     => 'required|exists:clients,id',
                'report_period' => 'required|string|max:100',
                'file'          => 'required|file|max:20480',
            ]);

            $allowedExtensions = ['xlsx', 'xls', 'csv', 'pdf'];
            $fileExt = strtolower($request->file('file')->getClientOriginalExtension());
            if (!in_array($fileExt, $allowedExtensions)) {
                return response()->json(['status' => 0, 'message' => 'El archivo debe ser Excel (.xlsx, .xls), CSV o PDF.'], 422);
            }

            $clientId = $request->client_id;
            $period   = $request->report_period;
            $user     = Session::get('user');
            $userId   = $user['id'];

            /** @var \App\Models\client $clientModel */
            $clientModel = client::findOrFail($clientId);
            $clientName  = trim($clientModel->name . ' ' . ($clientModel->lastname ?? ''));

            // Store the uploaded file temporarily
            $uploadedFile = $request->file('file');
            $fileName     = $uploadedFile->getClientOriginalName();
            $tempPath     = $uploadedFile->store('ia_uploads_temp', 'local');
            $fullPath      = storage_path('app/' . $tempPath);

            // Create conversation record immediately with 'processing' status
            $conversation = ia_conversation::create([
                'user_id'                  => $userId,
                'client_id'                => $clientId,
                'title'                    => 'Reporte Marketing - ' . $clientName . ' - ' . $period,
                'openai_last_response_id'  => null,
                'report_period'            => $period,
                'status'                   => 'processing',
                'metadata'                 => ['openai_file_id' => null],
            ]);

            try {
                // Upload to OpenAI
                $uploadResult = $this->IA_UploadFile($fullPath, $fileName);

                // Remove temp file after upload
                Storage::disk('local')->delete($tempPath);

                if ($uploadResult['status'] !== 1) {
                    $conversation->update(['status' => 'failed']);
                    return response()->json([
                        'status'  => 0,
                        'message' => 'Error al subir el archivo a OpenAI: ' . ($uploadResult['message'] ?? ''),
                    ], 400);
                }

                $openaiFileId = $uploadResult['file_id'];
                $conversation->update(['metadata' => ['openai_file_id' => $openaiFileId]]);

                // Generate report
                $reportResult = $this->IA_GenerateMarketingReport($openaiFileId, $clientName, $period);

                if ($reportResult['status'] !== 1) {
                    $conversation->update(['status' => 'failed']);
                    return response()->json([
                        'status'  => 0,
                        'message' => 'Error al generar el reporte: ' . ($reportResult['message'] ?? ''),
                    ], 400);
                }

                // Update conversation to completed
                $conversation->update([
                    'openai_last_response_id' => $reportResult['response_id'],
                    'status'                  => 'completed',
                ]);

                ia_turn::create([
                    'conversation_id'    => $conversation->id,
                    'openai_response_id' => $reportResult['response_id'],
                    'parent_response_id' => null,
                    'user_input'         => 'Archivo: ' . $fileName . ' | Período: ' . $period,
                    'report_json'        => $reportResult['report_json'],
                    'turn_number'        => 1,
                    'input_tokens'       => $reportResult['input_tokens'],
                    'output_tokens'      => $reportResult['output_tokens'],
                ]);

                return response()->json([
                    'status'          => 1,
                    'conversation_id' => $conversation->id,
                    'report_json'     => $reportResult['report_json'],
                    'turn_number'     => 1,
                ]);
            } catch (\Exception $innerEx) {
                $conversation->update(['status' => 'failed']);
                throw $innerEx;
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status' => 0, 'message' => $e->errors()], 422);
        } catch (\Exception $e) {
            info('ia_assistant_controller::generate_report ' . $e->getMessage());
            return response()->json(['status' => 0, 'message' => $e->getMessage()], 400);
        }
    }

    // ─── Regenerate with user feedback ───────────────────────────────────────
    public function regenerate_report(Request $request)
    {
        try {
            $request->validate([
                'conversation_id' => 'required|exists:ia_conversations,id',
                'feedback'        => 'required|string|max:2000',
            ]);

            $conversationId = $request->conversation_id;
            $feedback       = $request->feedback;
            $userId         = Session::get('user')['id'];

            $conversation = ia_conversation::where('id', $conversationId)
                ->where('user_id', $userId)
                ->firstOrFail();

            $previousResponseId = $conversation->openai_last_response_id;
            $lastTurnNumber     = $conversation->turns()->max('turn_number') ?? 0;

            // Set processing status before calling OpenAI
            $conversation->update(['status' => 'processing']);

            try {
                $result = $this->IA_RegenerateReport($previousResponseId, $feedback);

                if ($result['status'] !== 1) {
                    $conversation->update(['status' => 'completed']);
                    return response()->json([
                        'status'  => 0,
                        'message' => 'Error al regenerar el reporte: ' . ($result['message'] ?? ''),
                    ], 400);
                }

                $newTurnNumber = $lastTurnNumber + 1;

                ia_turn::create([
                    'conversation_id'    => $conversation->id,
                    'openai_response_id' => $result['response_id'],
                    'parent_response_id' => $previousResponseId,
                    'user_input'         => $feedback,
                    'report_json'        => $result['report_json'],
                    'turn_number'        => $newTurnNumber,
                    'input_tokens'       => $result['input_tokens'],
                    'output_tokens'      => $result['output_tokens'],
                ]);

                $conversation->update([
                    'openai_last_response_id' => $result['response_id'],
                    'status'                  => 'completed',
                ]);

                return response()->json([
                    'status'          => 1,
                    'conversation_id' => $conversation->id,
                    'report_json'     => $result['report_json'],
                    'turn_number'     => $newTurnNumber,
                ]);
            } catch (\Exception $innerEx) {
                $conversation->update(['status' => 'completed']);
                throw $innerEx;
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status' => 0, 'message' => $e->errors()], 422);
        } catch (\Exception $e) {
            info('ia_assistant_controller::regenerate_report ' . $e->getMessage());
            return response()->json(['status' => 0, 'message' => $e->getMessage()], 400);
        }
    }

    // ─── Download PDF for a conversation ─────────────────────────────────────
    public function download_pdf(Request $request, $conversation_id)
    {
        try {
            $userId = Session::get('user')['id'];

            $conversation = ia_conversation::with(['client', 'latest_turn'])
                ->where('id', $conversation_id)
                ->where('user_id', $userId)
                ->firstOrFail();

            $latestTurn = $conversation->latest_turn;

            if (!$latestTurn) {
                return response()->json(['status' => 0, 'message' => 'No se encontró un reporte generado.'], 404);
            }

            $client     = $conversation->client;
            $reportJson = $latestTurn->report_json;

            $Data = [
                'report'      => $reportJson,
                'client'      => $client,
                'period'      => $conversation->report_period,
                'public_path' => public_path('/'),
                'generated_at' => now()->format('d/m/Y H:i'),
            ];

            $pdf      = $this->PDF_GenerarPDF('pdf.ia_marketing_report', $Data);
            $filename = 'Reporte-Marketing-' . str_replace(' ', '-', $reportJson['period'] ?? $conversation->report_period) . '.pdf';

            return response($pdf->output(), 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            info('ia_assistant_controller::download_pdf ' . $e->getMessage());
            return response()->json(['status' => 0, 'message' => $e->getMessage()], 400);
        }
    }
    // ─── Inline PDF preview for a conversation ──────────────────────────────────
    public function preview_pdf($conversation_id)
    {
        try {
            $userId = Session::get('user')['id'];

            $conversation = ia_conversation::with(['client', 'latest_turn'])
                ->where('id', $conversation_id)
                ->where('user_id', $userId)
                ->firstOrFail();

            $latestTurn = $conversation->latest_turn;

            if (!$latestTurn) {
                return response()->json(['status' => 0, 'message' => 'No se encontró un reporte generado.'], 404);
            }

            $client     = $conversation->client;
            $reportJson = $latestTurn->report_json;

            $Data = [
                'report'       => $reportJson,
                'client'       => $client,
                'period'       => $conversation->report_period,
                'public_path'  => public_path('/'),
                'generated_at' => now()->format('d/m/Y H:i'),
            ];

            $pdf      = $this->PDF_GenerarPDF('pdf.ia_marketing_report', $Data);
            $filename = 'Reporte-Marketing-' . str_replace(' ', '-', $reportJson['period'] ?? $conversation->report_period) . '.pdf';

            return response($pdf->output(), 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            info('ia_assistant_controller::preview_pdf ' . $e->getMessage());
            return response()->json(['status' => 0, 'message' => $e->getMessage()], 400);
        }
    }
    // ─── Get conversation history ─────────────────────────────────────────────
    public function get_conversation_history($conversation_id)
    {
        try {
            $userId = Session::get('user')['id'];

            $conversation = ia_conversation::with(['turns', 'client'])
                ->where('id', $conversation_id)
                ->where('user_id', $userId)
                ->firstOrFail();

            return response()->json([
                'status'       => 1,
                'conversation' => [
                    'id'            => $conversation->id,
                    'title'         => $conversation->title,
                    'report_period' => $conversation->report_period,
                    'client'        => [
                        'name'  => trim($conversation->client->name . ' ' . ($conversation->client->lastname ?? '')),
                        'photo' => $conversation->client->photo,
                        'email' => $conversation->client->email,
                    ],
                    'turns' => $conversation->turns->map(fn($t) => [
                        'id'          => $t->id,
                        'turn_number' => $t->turn_number,
                        'user_input'  => $t->user_input,
                        'created_at'  => $t->created_at->format('d/m/Y H:i'),
                    ]),
                ],
            ]);
        } catch (\Exception $e) {
            info('ia_assistant_controller::get_conversation_history ' . $e->getMessage());
            return response()->json(['status' => 0, 'message' => $e->getMessage()], 400);
        }
    }

    // ─── List all conversations for current user ──────────────────────────────
    public function get_conversations_list(Request $request)
    {
        try {
            $userId = Session::get('user')['id'];
            $q      = trim($request->query('q', ''));

            $query = ia_conversation::with(['client', 'latest_turn'])
                ->where('user_id', $userId);

            if ($q !== '') {
                $like = '%' . $q . '%';
                $query->where(function ($sub) use ($like) {
                    $sub->where('title', 'like', $like)
                        ->orWhere('report_period', 'like', $like)
                        ->orWhereHas('client', fn($c) => $c->whereRaw("CONCAT(name, ' ', COALESCE(lastname, '')) like ?", [$like]));
                });
            }

            $conversations = $query->orderByDesc('updated_at')->get();

            return response()->json([
                'status' => 1,
                'data'   => $conversations->map(fn($c) => [
                    'id'            => $c->id,
                    'title'         => $c->title,
                    'report_period' => $c->report_period,
                    'updated_at'    => $c->updated_at->format('d/m/Y H:i'),
                    'client_name'   => trim($c->client->name . ' ' . ($c->client->lastname ?? '')),
                    'client_photo'  => $c->client->photo,
                    'client_email'  => $c->client->email,
                    'turn_count'    => $c->turns()->count(),
                    'status'        => $c->status ?? 'completed',
                ]),
            ]);
        } catch (\Exception $e) {
            info('ia_assistant_controller::get_conversations_list ' . $e->getMessage());
            return response()->json(['status' => 0, 'message' => $e->getMessage()], 400);
        }
    }

    // ─── Send report via email ────────────────────────────────────────────────
    public function send_report_email(Request $request)
    {
        try {
            $request->validate([
                'conversation_id' => 'required|exists:ia_conversations,id',
                'email'           => 'required|email|max:255',
            ]);

            $userSession = Session::get('user');
            $userId      = $userSession['id'];
            $userEmail   = $userSession['email'] ?? null;

            $conversation = ia_conversation::with(['client', 'latest_turn'])
                ->where('id', $request->conversation_id)
                ->where('user_id', $userId)
                ->firstOrFail();

            if ($conversation->status !== 'completed') {
                return response()->json(['status' => 0, 'message' => 'El reporte aún no está listo.'], 422);
            }

            $latestTurn = $conversation->latest_turn;
            if (!$latestTurn) {
                return response()->json(['status' => 0, 'message' => 'No se encontró un reporte generado.'], 404);
            }

            $client     = $conversation->client;
            $reportJson = $latestTurn->report_json;
            $clientName = trim($client->name . ' ' . ($client->lastname ?? ''));

            // Generate PDF to temp file
            $Data = [
                'report'       => $reportJson,
                'client'       => $client,
                'period'       => $conversation->report_period,
                'public_path'  => public_path('/'),
                'generated_at' => now()->format('d/m/Y H:i'),
            ];

            $pdf      = $this->PDF_GenerarPDF('pdf.ia_marketing_report', $Data);
            $filename = 'Reporte-Marketing-' . str_replace(' ', '-', $reportJson['period'] ?? $conversation->report_period) . '.pdf';
            $tempPdf  = storage_path('app/ia_temp_pdf_' . $conversation->id . '_' . time() . '.pdf');
            file_put_contents($tempPdf, $pdf->output());

            // Build recipients list
            $Mails = [
                ['address' => $request->email, 'name' => $clientName],
            ];
            if ($userEmail && strtolower($userEmail) !== strtolower($request->email)) {
                $Mails[] = ['address' => $userEmail, 'name' => $userSession['name'] ?? 'Usuario'];
            }

            // Email data
            $MailData = [
                'subject' => 'Reporte de Marketing - ' . $clientName . ' - ' . $conversation->report_period,
            ];

            $ViewData = collect([
                'client_name' => $clientName,
                'period'      => $conversation->report_period,
                'report_title' => $conversation->title,
                'generated_at' => now()->format('d/m/Y H:i'),
            ]);

            $files = [
                'path' => $tempPdf,
                'name' => $filename,
            ];

            $mailResponse = $this->SendMail($MailData, $Mails, 'mail.ia_marketing_report', $ViewData, $files);

            // Clean up temp PDF
            if (file_exists($tempPdf)) {
                unlink($tempPdf);
            }

            if ($mailResponse['status'] === 1) {
                return response()->json(['status' => 1, 'message' => 'Correo enviado correctamente.']);
            } else {
                return response()->json(['status' => 0, 'message' => 'Error al enviar: ' . $mailResponse['message']], 400);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status' => 0, 'message' => $e->errors()], 422);
        } catch (\Exception $e) {
            info('ia_assistant_controller::send_report_email ' . $e->getMessage());
            return response()->json(['status' => 0, 'message' => $e->getMessage()], 400);
        }
    }
}
