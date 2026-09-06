<?php 
namespace App\traits;
use Mail;
use Carbon\Carbon;
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
		$logStatus = 0;
		$deferred = false;

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
			$deferred = $this->Mail_ShouldDeferExternalOnSunday($ViewData, $Mails);
			if ($deferred) {
				$Response['message'] = 'Correo externo diferido por ser domingo';
				$Response['status'] = 1;
			} else {
				$mail = Mail::mailer($mailer ?: config('mail.default', 'smtp'));
				///////////////////////////
				///////////////////////////
				// Define the mailable object
				$mail->to(array_column($Mails, 'address'))->queue(new CustomMail($MailData, $View, $ViewData, $files, $from, $replyTo));
				$Response['message'] = 'Correo en cola para envío';
				$Response['status'] = 1;
			}
			$logStatus = $deferred ? 0 : $Response['status'];

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
			$logStatus,
			$files,
			$deferred ? '' : $Response['message']
		);

		return $Response;
	}

	public function SendMail_attach_array($MailData, $Mails, $View, $ViewData, $file_array, $unique_id = null, $from = null, $replyTo = null, $mailer = null)
	{
		$Response = [
			'status' => 0,
			'message' => ''
		];
		$logStatus = 0;
		$deferred = false;
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
			
			$deferred = $this->Mail_ShouldDeferExternalOnSunday($ViewData, $Mails);
			if ($deferred) {
				$Response['message'] = 'Correo externo diferido por ser domingo';
				$Response['status'] = 1;
			} else {
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
			}
			$logStatus = $deferred ? 0 : $Response['status'];

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
			$logStatus,
			$file_array,
			$deferred ? '' : $Response['message']
		);
		return $Response;
	}

	public function Mail_ShouldDeferExternalOnSunday($mailData, $Mails): bool
	{
		if (!Carbon::now(config('app.timezone'))->isSunday()) {
			return false;
		}

		$mailData = $this->Mail_NormalizeData($mailData);
		if (empty($mailData['_defer_external_on_sunday'])) {
			return false;
		}

		$recipients = is_array($Mails) && array_key_exists('address', $Mails)
			? [$Mails]
			: (array) $Mails;
		if (!$recipients) {
			return false;
		}

		foreach ($recipients as $recipient) {
			if (!$this->Mail_IsInternalRecipient($recipient)) {
				return true;
			}
		}

		return false;
	}

	private function Mail_NormalizeData($mailData): array
	{
		if (is_object($mailData) && method_exists($mailData, 'toArray')) {
			$mailData = $mailData->toArray();
		}

		return is_array($mailData) ? $mailData : [];
	}

	private function Mail_IsInternalRecipient($recipient): bool
	{
		$address = is_array($recipient)
			? ($recipient['address'] ?? $recipient['email'] ?? '')
			: $recipient;
		$address = strtolower(trim((string) $address));
		if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
			return false;
		}

		$atPosition = strrpos($address, '@');
		$domain = $atPosition === false ? '' : substr($address, $atPosition + 1);
		$internalDomains = ['opzio.co'];

		return in_array($domain, $internalDomains, true);
	}
	
}
