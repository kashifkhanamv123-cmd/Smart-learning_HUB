<?php
namespace PHPMailer\PHPMailer;

require_once __DIR__ . '/Exception.php';

class PHPMailer {
    public $Host;
    public $SMTPAuth = false;
    public $Username;
    public $Password;
    public $SMTPSecure = '';
    public $Port = 25;
    
    private $to = [];
    private $fromEmail = '';
    private $fromName = '';
    public $Subject = '';
    public $Body = '';
    public $AltBody = '';
    public $ContentType = 'text/plain';
    public $CharSet = 'utf-8';
    
    const ENCRYPTION_STARTTLS = 'tls';
    const ENCRYPTION_SMTPS = 'ssl';

    public function __construct($exceptions = null) {}

    public function isSMTP() {
        // Always uses SMTP in this custom wrapper
    }

    public function setFrom($email, $name = '') {
        $this->fromEmail = $email;
        $this->fromName = $name;
    }

    public function addAddress($email, $name = '') {
        $this->to[] = $email;
    }

    public function isHTML($isHtml = true) {
        $this->ContentType = $isHtml ? 'text/html' : 'text/plain';
    }

    public function send() {
        if (empty($this->to)) {
            throw new Exception("No recipient address provided");
        }
        
        $host = $this->Host;
        $port = $this->Port;
        
        // Open TCP connection
        $socket = @fsockopen($host, $port, $errno, $errstr, 15);
        if (!$socket) {
            throw new Exception("Could not connect to SMTP host: $errstr ($errno)");
        }
        
        $this->readResponse($socket, '220');
        
        $this->writeCommand($socket, "EHLO " . (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost'));
        $this->readResponse($socket, '250');
        
        // Upgrade connection to TLS
        if ($this->SMTPSecure === 'tls') {
            $this->writeCommand($socket, "STARTTLS");
            $this->readResponse($socket, '220');
            
            // Enable TLS crypto
            $crypto_method = STREAM_CRYPTO_METHOD_TLS_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $crypto_method = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
            }
            if (!@stream_socket_enable_crypto($socket, true, $crypto_method)) {
                fclose($socket);
                throw new Exception("STARTTLS negotiation failed");
            }
            
            // Send EHLO again after TLS negotiation
            $this->writeCommand($socket, "EHLO " . (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost'));
            $this->readResponse($socket, '250');
        }
        
        // Perform SMTP Authentication
        if ($this->SMTPAuth) {
            $this->writeCommand($socket, "AUTH LOGIN");
            $this->readResponse($socket, '334');
            
            $this->writeCommand($socket, base64_encode($this->Username));
            $this->readResponse($socket, '334');
            
            $this->writeCommand($socket, base64_encode($this->Password));
            $this->readResponse($socket, '235');
        }
        
        // Mail From
        $this->writeCommand($socket, "MAIL FROM:<" . $this->fromEmail . ">");
        $this->readResponse($socket, '250');
        
        // RCPT TO
        foreach ($this->to as $recipient) {
            $this->writeCommand($socket, "RCPT TO:<" . $recipient . ">");
            $this->readResponse($socket, '250');
        }
        
        // DATA
        $this->writeCommand($socket, "DATA");
        $this->readResponse($socket, '354');
        
        // Build Headers and MIME Body
        $headers = [];
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: " . $this->ContentType . "; charset=" . $this->CharSet;
        
        $headers[] = "From: " . ($this->fromName ? '"' . $this->fromName . '" ' : '') . "<" . $this->fromEmail . ">";
        $headers[] = "To: " . implode(', ', $this->to);
        $headers[] = "Subject: =?UTF-8?B?" . base64_encode($this->Subject) . "?=";
        $headers[] = "Date: " . date('r');
        $headers[] = "Message-ID: <" . uniqid('', true) . "@" . (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost') . ">";
        
        // Double check SMTP DATA ending format (double CRLF + body + CRLF + dot + CRLF)
        $message = implode("\r\n", $headers) . "\r\n\r\n" . $this->Body . "\r\n.\r\n";
        
        // Send data
        fwrite($socket, $message);
        $this->readResponse($socket, '250');
        
        // Quit session
        $this->writeCommand($socket, "QUIT");
        fclose($socket);
        
        return true;
    }
    
    private function writeCommand($socket, $cmd) {
        fwrite($socket, $cmd . "\r\n");
    }
    
    private function readResponse($socket, $expectedCode) {
        $response = '';
        while ($line = fgets($socket, 512)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        $code = substr($response, 0, 3);
        if ($code !== $expectedCode) {
            throw new Exception("SMTP Error: Expected $expectedCode, got: $response");
        }
        return $response;
    }
}
?>
