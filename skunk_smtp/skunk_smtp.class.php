<?php
/*
  skunk_smtp mailer class 0.1.1
  ~ iadnah 2011

  supports sending email through an SMTP server with SMTP AUTH, SSL, and/or STARTTLS

  read the comments for documentation

  example usage for using a gmail account:


	$mailer = new skunk_smtp();
	$mailer->smtp_host = 'smtp.gmail.com';
	$mailer->smtp_port = 587;
	$mailer->smtp_transport = 'tcp';
	$mailer->auth_user = "username@gmail.com";
	$mailer->auth_pass = "gmail_password";
	
	$mailer->ehlo = "skunk_mail";

	$mailer->mail_from = "username@gmail.com";
	$mailer->mail_to = "somebody@example.com";

	$mailer->subject = "You got some mail!";
	$mailer->msg = "This is the raw data section of the message.";
	$mailer->stls = true;

	if ($mailer->send()) {
		echo "Mail sent!\n";
	}

*/

class skunk_smtp {

  public $smtp_host = '';	//Host to connect to
  public $smtp_port = '';	//Port to connect on
  public $smtp_transport = '';	//Transport to use. Either tcp or ssl

  public $mail_from = '';
  public $subject = '';
  public $mail_to = '';
  public $msg = '';
  public $data_from = '';
  public $data_to = '';
  public $headers = '';


  var $socket;

  var $ehlo;

  var $last_ret;

  var $timeout = 30;

  var $uri;

  var $auth;
  var $auth_user;
  var $auth_pass;

  var $stls = false;


  public $debug = true;

  public function debug($msg) {
    if ($this->debug) {
      echo "[DEBUG] $msg";
    }
  }

  public function empty_read_buffer() {
    while ($this->poll()) {
      $line = fgets($this->socket);
      $this->debug("SERVER: ". rtrim($line). "\n");
    }
  }

  private function poll($usec = 5) {
    $r = array($this->socket);
    $w = $e = null;
    $c = stream_select($r, $w, $e, $usec);
    if ($c > 0) { return true; }
    else { return false; }
  }

  public function connect() {
      $this->uri = $this->smtp_transport. "://". $this->smtp_host. ":". $this->smtp_port;
      if (! ($this->socket = stream_socket_client($this->uri, $errno, $errstr, $this->timeout) ) ) {
	return false;
      }

      if (!$this->get_return_code(220)) {
	die("Connect failed.");
      }

      return true;
  }


  public function cmd($cmd) {
      $this->debug("CLIENT: $cmd\n");
      fwrite($this->socket, $cmd. "\r\n");
  }

  public function get_return_code($cmp = '250') {
    if ($this->poll()) {
      if (! ($this->last_ret = fgets($this->socket))) {
	return;
      }

      $this->debug("SERVER: ". rtrim($this->last_ret). "\n");

      if ((int)$this->last_ret == $cmp) {
	return true;
      }
    }
  }


  private function es() {
    if (strpos($this->last_ret, 'ESMTP') !== false) {
      return true;
    }
  }

  public function send_auth() {
    $this->cmd("AUTH LOGIN");
    if (!$this->get_return_code(334)) {
      return false;
    }

    $this->cmd(base64_encode($this->auth_user));
    if (!$this->get_return_code(334)) {
      return false;
    }

    $this->cmd(base64_encode($this->auth_pass));
    if (!$this->get_return_code(235)) {
      debug("WTF: ". $this->last_ret. "\n");
      return false;
    }

    return true;
  }

  public function postConInit() {
      $this->cmd("EHLO ". $this->ehlo);

      if (!$this->get_return_code(250)) {
	die("Bad response to ehlo");
      }
      $this->empty_read_buffer();
  }

  public function send_from_headers() {
    $this->cmd('MAIL FROM: <'.$this->mail_from.'>');
    return $this->get_return_code();
  }

  public function send_to_headers() {
    $this->cmd('RCPT TO: <'.$this->mail_to.'>');
    return $this->get_return_code();
  }

  public function send_data() {
      $this->cmd('DATA');
      if (!$this->get_return_code(354)) {
	  return false;
      }
      $this->cmd("Subject: ".$this->subject);
      $this->cmd("Date: ".date('r'));
      $this->cmd("To: ".(strlen($this->data_to) > 0 ? $this->data_to : $this->mail_to));
      $this->cmd("From: ".(strlen($this->data_from) > 0 ? $this->data_from : $this->mail_from));
      $this->cmd($this->headers. "\r\n");
      $this->cmd($this->encode_body());
      $this->cmd('.');
      return $this->get_return_code();
  }

  public function encode_body() {
      $body = $this->msg;
      $body = str_replace("\r", "", $body);
      return $body. "\n";
  }

  public function starttls() {
    if ($this->stls) {
      $this->cmd("STARTTLS");
      if (!$this->get_return_code(220)) {
	  debug("STARTTLS denied by server: ". $this->last_ret. "\n");
	  return false;
      } 

      if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
	  debug("STARTTLS error because of PHP.");
	  return false;
      }
      return true;
    }
  }

  public function send() {
    if (!$this->connect()) {
      $this->debug("Connection failed\n");
      return false;
    }
    $this->postConInit();
    if ($this->stls) {
      if (!$this->starttls()) {
        $this->debug("STARTTLS enabled but could not initialize.\n");
        return false;
      }
    } else {
      $self->debug("STARTTLS initialized.\n");
    }

    //Do authentication if a username is set
    if (strlen($this->auth_user) > 0) {
      if (!$this->send_auth()) {
        $this->debug("SMTP AUTH failed.\n");
        return false;
      }
    }
    if (!$this->send_from_headers()) {
      $this->debug("Server did not like MAIL FROM:\n");
      return false;
    }

    if (!$this->send_to_headers()) {
      $this->debug("Server did not like RCPT TO:\n");
      return false;
    }

    if (!$this->send_data()) {
      $this->debug("Could not queue mail: ". $this->last_ret. "\n");
      return false;
    }
    return true;
  }
}

?>
