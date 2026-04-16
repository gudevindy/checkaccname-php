<?php
declare(strict_types=1);

namespace GuDevIndy\CheckAccName;

use GuDevIndy\CheckAccName\Exception\CheckAccNameException;
use GuDevIndy\CheckAccName\Exception\InquiryException;
use GuDevIndy\CheckAccName\Exception\NetworkException;

/**
 * PHP client for Check AccName — Thai bank account name inquiry API.
 *
 * Quick start:
 *   $client = new Client('YOUR-BEARER-TOKEN');
 *   $result = $client->inquiry('004', '1234567890');
 *   echo $result->beneficiaryName;
 *
 * Get a token (free tier available):
 *   1. Login at https://check.gudevindy.com via LINE
 *   2. Copy the API token from /member dashboard
 *
 * @link https://check.gudevindy.com/docs
 */
final class Client
{
    public const VERSION    = '1.0.0';
    public const USER_AGENT = 'CheckAccName-PHP/1.0 (+https://github.com/gudevindy/checkaccname-php)';

    private string $baseUrl;
    private string $token;
    private int    $timeout;
    private bool   $sslVerify;

    /**
     * @param string              $token   Bearer token from /member dashboard
     * @param array<string,mixed> $options [
     *     'baseUrl'   => 'https://check.gudevindy.com',  // override for testing
     *     'timeout'   => 15,                              // seconds
     *     'sslVerify' => true,
     * ]
     */
    public function __construct(string $token, array $options = [])
    {
        if ($token === '') {
            throw new \InvalidArgumentException('token is required');
        }
        if (!extension_loaded('curl')) {
            throw new \RuntimeException('ext-curl is required');
        }
        $this->token     = $token;
        $this->baseUrl   = rtrim((string)($options['baseUrl'] ?? 'https://check.gudevindy.com'), '/');
        $this->timeout   = (int)($options['timeout'] ?? 15);
        $this->sslVerify = (bool)($options['sslVerify'] ?? true);
    }

    /**
     * POST /api/inquiry — ตรวจสอบชื่อเจ้าของบัญชี
     *
     * @param  string $bankCode  รหัสธนาคาร 3 หลัก (เช่น "004" = KBank)
     * @param  string $accountNo เลขบัญชี (รับทั้งมีและไม่มี dash; ตัดเหลือเฉพาะตัวเลขให้)
     *
     * @throws InquiryException      บัญชีไม่พบ / รูปแบบผิด / upstream ปฏิเสธ
     * @throws CheckAccNameException ปัญหา auth / quota / network
     */
    public function inquiry(string $bankCode, string $accountNo): InquiryResult
    {
        $bankCode  = trim($bankCode);
        $accountNo = preg_replace('/\D+/', '', $accountNo) ?? '';
        if ($bankCode === '' || $accountNo === '') {
            throw new \InvalidArgumentException('bankCode and accountNo are required');
        }

        $resp = $this->post('/api/inquiry', [
            'bankCode'      => $bankCode,
            'beneficiaryNo' => $accountNo,
        ]);

        // success-shape มี beneficiaryName · error-shape มี status:false + errorCode
        if (empty($resp['beneficiaryName'])) {
            $e = new InquiryException(
                (string)($resp['errorMessage'] ?? $resp['error'] ?? 'Inquiry failed'),
                422
            );
            $e->errorCode    = $resp['errorCode']    ?? null;
            $e->responseBody = $resp;
            throw $e;
        }
        return InquiryResult::fromArray($resp);
    }

    // ─── Internal HTTP ────────────────────────────────────────────

    /**
     * @param  array<mixed>        $body
     * @return array<string,mixed>
     */
    private function post(string $path, array $body): array
    {
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->token,
                'User-Agent: ' . self::USER_AGENT,
            ],
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => $this->sslVerify,
            CURLOPT_SSL_VERIFYHOST => $this->sslVerify ? 2 : 0,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $resp = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        unset($ch);

        if ($resp === false || $err !== '') {
            throw new NetworkException('cURL error: ' . ($err ?: 'unknown'));
        }
        $decoded = json_decode((string)$resp, true);
        if (!is_array($decoded)) {
            throw new NetworkException("Invalid JSON response (HTTP {$code})");
        }
        if ($code >= 400) {
            throw CheckAccNameException::fromHttp($code, $decoded);
        }
        return $decoded;
    }
}
