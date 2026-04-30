<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserRegistered extends Mailable
{
    use Queueable, SerializesModels;

    public $UserData;

    public function __construct($UserData)
    {
        $this->UserData = $UserData;
    }

    public function build()
    {
        return $this->view('emails.User_registered');
    }
}
