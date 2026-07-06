<?php 
namespace App\traits;
use Mail;
use Illuminate\Support\Facades\App;
use App\Mail\CustomMail;


trait mail_trait
{
	use mail_log_trait;

	public function SendMail(
		$MailData,
		$Mails,
		$View,
		$ViewData,
		$files,
		$unique_id = null,
		$mailer = null,
		$from = null
	) {
		$Response = [
			'status' => 0,
			'message' => ''
		];

		try {
			if (isset($from) === false) {
				$from = [
					'address' => config('app.MAIL_FROM_ADDRESS') ?? '',
					'name' => config('app.MAIL_FROM_NAME') ?? ''
				];
			}
			if (App::environment() === 'local') {
				$Mails = [
					[
						'address' => 'soporte@opzio.com.co',
						'name' => 'Opzio Test'
					]
				];
			}
			$mail = $mailer ? Mail::mailer($mailer) : Mail::mailer('smtp');
			///////////////////////////
			///////////////////////////
			// Define the mailable object
			$mailJob = $mail->to(array_column($Mails, 'address'))->queue(new CustomMail($MailData, $View, $ViewData, $files, $from));
			//check if the mail was sent
			if ($mailJob !== null) {
				$Response['message'] = 'Correo en cola para envío';
				$Response['status'] = 1;
			} else {
				$Response['message'] = 'Error al enviar el correo';
			}

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
			$ViewData,
			$Response['status'],
			$files,
			$Response['message']
		);

		return $Response;
	}

	public function SendMail_attach_array($MailData, $Mails, $View, $ViewData, $file_array, $unique_id = null)
	{
		$Response = [
			'status' => 0,
			'message' => ''
		];
		try {
			if (App::environment() === 'local') {
				$Mails = [
					[
						'address' => 'soporte@opzio.com.co',
						'name' => 'Opzio Test'
					]
				];
			}
			
			$from = [
				'address' => config('app.MAIL_FROM_ADDRESS') ?? '',
				'name' => config('app.MAIL_FROM_NAME') ?? ''
			];
			
			$mail = Mail::mailer('smtp');
			///////////////////////////
			///////////////////////////
			// Define the mailable object
			$mailJob = $mail->to(array_column($Mails, 'address'))->queue(new CustomMail($MailData, $View, $ViewData, $file_array, $from));
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
			$ViewData,
			$Response['status'],
			$file_array,
			$Response['message']
		);
		return $Response;
	}
	
}
