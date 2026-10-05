<?php
// Minimalen SMTP odjemalec (Gmail prek SSL), brez zunanjih knjižnic.
// Ena povezava lahko pošlje več mailov zapored.

class Smtp
{
    private $sock = null;

    public function __construct(private array $c) {}

    public function open(): void
    {
        $this->sock = @stream_socket_client($this->c['host'] . ':' . $this->c['port'], $errno, $errstr, 15);
        if (!$this->sock) {
            throw new RuntimeException("Povezava na mail strežnik ni uspela: $errstr ($errno)");
        }
        stream_set_timeout($this->sock, 20);
        $this->expect(220);
        $this->cmd('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'), 250);
        if (!empty($this->c['user'])) {
            $this->cmd('AUTH LOGIN', 334);
            $this->cmd(base64_encode($this->c['user']), 334);
            $this->cmd(base64_encode(str_replace(' ', '', $this->c['pass'])), 235);
        }
    }

    public function send(string $to, string $subject, string $html): void
    {
        if (!$this->sock) $this->open();
        $from = $this->c['from'] ?? $this->c['user'];
        $domain = explode('@', $from)[1] ?? 'localhost';

        $this->cmd("MAIL FROM:<$from>", 250);
        $this->cmd("RCPT TO:<$to>", [250, 251]);
        $this->cmd('DATA', 354);

        $headers = [
            'Date: ' . date('r'),
            'From: ' . self::enc($this->c['from_name'] ?? '') . " <$from>",
            "To: <$to>",
            'Subject: ' . self::enc($subject),
            'Message-ID: <' . bin2hex(random_bytes(12)) . "@$domain>",
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ];
        // base64 telo nikoli ne vsebuje vrstice, ki se začne s piko
        $this->cmd(implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($html)) . '.', 250);
    }

    public function close(): void
    {
        if ($this->sock) {
            @fwrite($this->sock, "QUIT\r\n");
            @fclose($this->sock);
            $this->sock = null;
        }
    }

    private function cmd(string $line, $ok): string
    {
        fwrite($this->sock, $line . "\r\n");
        return $this->expect($ok);
    }

    private function expect($ok): string
    {
        $resp = '';
        while (($l = fgets($this->sock, 515)) !== false) {
            $resp .= $l;
            if (strlen($l) < 4 || $l[3] === ' ') break;
        }
        $code = (int) substr($resp, 0, 3);
        if (!in_array($code, (array) $ok, true)) {
            $this->close();
            throw new RuntimeException('Mail strežnik: ' . trim($resp ?: 'ni odgovora'));
        }
        return $resp;
    }

    private static function enc(string $s): string
    {
        return '=?UTF-8?B?' . base64_encode($s) . '?=';
    }
}
