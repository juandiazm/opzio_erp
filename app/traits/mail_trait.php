<?php 
namespace App\traits;
use Mail;
use Illuminate\Support\Facades\App;
use App\Mail\CustomMail;


trait mail_trait
{
	use mail_log_trait;
	use mail_senders_trait;

	public function SendMail(
		$MailData,
		$Mails,
		$View,
		$ViewData,
		$files,
		$unique_id = null,
		$mailer = null,
		$from = null,
		$replyTo = null
	) {
		$Response = [
			'status' => 0,
			'message' => ''
		];

		try {
			$from = $this->Mail_GetSenderForView($View, $from);
			$replyTo = $this->Mail_GetReplyTo();
			if (App::environment() === 'local') {
				$Mails = [
					[
						'address' => 'info@opzio.co',
						'name' => 'Opzio Test'
					]
				];
			}
			$mail = Mail::mailer($mailer ?: config('mail.default', 'smtp'));
			///////////////////////////
			///////////////////////////
			// Define the mailable object
			$mail->to(array_column($Mails, 'address'))->queue(new CustomMail($MailData, $View, $ViewData, $files, $from, $replyTo));
			$Response['message'] = 'Correo en cola para envío';
			$Response['status'] = 1;

		} catch (\Exception $e) {
			info('SendMail error: ' . $e->getMessage());
			$Response['message'] = $e->getMessage();
		}

		// Set mail log
		$this->MailLog_SetLog(
			$unique_id,
			$MailData['subject'],
			$View,
			$from['address'],
			$from['name'],
			$Mails,
			null,
			$this->Mail_AddEnvelopeMetadata($ViewData, $from, $replyTo),
			$Response['status'],
			$files,
			$Response['message']
		);

		return $Response;
	}

	public function SendMail_attach_array($MailData, $Mails, $View, $ViewData, $file_array, $unique_id = null, $from = null, $replyTo = null, $mailer = null)
	{
		$Response = [
			'status' => 0,
			'message' => ''
		];
		try {
			if (App::environment() === 'local') {
				$Mails = [
					[
						'address' => 'info@opzio.co',
						'name' => 'Opzio Test'
					]
				];
			}
			
					$from = $this->Mail_GetSenderForView($View, $from);
					$replyTo = $this->Mail_GetReplyTo();
			
				$mail = Mail::mailer($mailer ?: config('mail.default', 'smtp'));
			///////////////////////////
			///////////////////////////
			// Define the mailable object
			$mailJob = $mail->to(array_column($Mails, 'address'))->queue(new CustomMail($MailData, $View, $ViewData, $file_array, $from, $replyTo));
			//check if the mail was sent
			if ($mailJob !== null) {
				$Response['message'] = 'Correo en cola para envío';
				$Response['status'] = 1;
			} else {
				$Response['message'] = 'Error al enviar el correo';
			}

		} catch (\Exception $e) {
			info('SendMail_attach_array error: ' . $e->getMessage());
			$Response['message'] = $e->getMessage();
		}
		//Set mail log
		$this->MailLog_SetLog(
			$unique_id,
			$MailData['subject'],
			$View,
			$from['address'],
			$from['name'],
			$Mails,
			null,
			$this->Mail_AddEnvelopeMetadata($ViewData, $from, $replyTo),
			$Response['status'],
			$file_array,
			$Response['message']
		);
		return $Response;
	}
	
}
