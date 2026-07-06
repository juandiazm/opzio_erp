<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomMail extends Mailable
{
    use Queueable, SerializesModels;

    public $MailData;
    public $ViewData;
    public $View;
    public $files;
    public $fromDetails;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($MailData, $View, $ViewData, $files = null, $from = null)
    {
        $this->MailData = $MailData;
        $this->View = $View;
        $this->ViewData = $ViewData;
        $this->files = $files;
        $this->fromDetails = $from;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // Set the "from" address if provided
        if ($this->fromDetails) {
            $this->from($this->fromDetails['address'], $this->fromDetails['name']);
        }

        // Build the email with the specified view and data
        $email = $this->view($this->View)
            ->with(['Data' => $this->ViewData])
            ->subject($this->MailData['subject'])
            ->bcc('soporte@opzio.com.co');

        // Attach the files if provided
        if (isset($this->files) && $this->files != null) {
            $isAssociative = array_keys($this->files) !== range(0, count($this->files) - 1);
        
            if ($isAssociative) {
                $email->attach($this->files['path'], ['as' => $this->files['name']]);
            } else {
                foreach ($this->files as $file) {
                    $email->attach($file['path'], ['as' => $file['name']]);
                }
            }
            
        }

        return $email;
    }
}
