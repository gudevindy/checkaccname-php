# Check AccName · PHP Client

[![Packagist Version](https://img.shields.io/badge/packagist-1.0.0-blue)](https://packagist.org/packages/gudevindy/checkaccname)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.0-777bb3)](https://www.php.net)
[![License: MIT](https://img.shields.io/badge/license-MIT-green)](LICENSE)

PHP SDK สำหรับเรียกใช้ [Check AccName API](https://check.gudevindy.com) — **ตรวจสอบชื่อเจ้าของบัญชีธนาคารจากเลขบัญชี** · รองรับทุกธนาคารในไทย

> **Use cases:** ระบบประมูล · ระบบ Affiliate · ระบบถอนเงิน · ป้องกันสมัครซ้ำ · KYC

## Features

- ✅ ตรวจสอบชื่อเจ้าของบัญชี (PromptPay + ORFT) ทุกธนาคารในไทย
- ✅ แยกคำนำหน้า / ชื่อ / นามสกุลอัตโนมัติ + รองรับนิติบุคคล
- ✅ Type-safe DTO (typed properties)
- ✅ Exception hierarchy แยกประเภท Auth / RateLimit / Inquiry / Network
- ✅ Zero deps · ใช้แค่ ext-curl + ext-json
- ✅ MIT License

## Requirements

- PHP **8.0+**
- ext-curl, ext-json
- Bearer token (ขอฟรีที่ https://check.gudevindy.com — login ด้วย LINE)

## Install

```bash
composer require gudevindy/checkaccname
```

## Quick Start

```php
<?php
require 'vendor/autoload.php';

use GuDevIndy\CheckAccName\Client;

$client = new Client('YOUR-BEARER-TOKEN');

$result = $client->inquiry(bankCode: '004', accountNo: '1234567890');

echo $result->beneficiaryName;     // "นาย สมชาย ใจดี"
echo $result->title;               // "นาย"
echo $result->firstName;           // "สมชาย"
echo $result->lastName;            // "ใจดี"
```

## Get a Token

1. ไปที่ https://check.gudevindy.com
2. กด **Login with LINE**
3. หน้า [Dashboard](https://check.gudevindy.com/member) → คัดลอก **API Token**
4. แผน Free 20 ครั้ง/วัน · Starter 50/วัน · Enterprise 2,000/วัน

## API

### `new Client(string $token, array $options = [])`

| Option | Type | Default | คำอธิบาย |
|---|---|---|---|
| `baseUrl` | string | `https://check.gudevindy.com` | override สำหรับ test/staging |
| `timeout` | int | `15` | cURL timeout (วินาที) |
| `sslVerify` | bool | `true` | verify peer cert (production = true เสมอ) |

### `inquiry(string $bankCode, string $accountNo): InquiryResult`

ตรวจสอบชื่อเจ้าของบัญชี · ค่า return เป็น `InquiryResult` (readonly DTO)

```php
$r = $client->inquiry('004', '1234567890');

$r->beneficiaryName;        // "นาย สมชาย ใจดี"
$r->beneficiaryNoMasking;   // "xxx-x-x7890-x"
$r->title;                  // "นาย"
$r->firstName;              // "สมชาย"   (null ถ้า isCompany)
$r->lastName;               // "ใจดี"   (null ถ้า isCompany)
$r->isCompany;              // false
$r->companyName;            // null     (มีค่าเมื่อ isCompany=true)
$r->bankCode;               // "004"
$r->bankAbv;                // "KBANK"
$r->blacklisted;            // bool — ถูกรายงานเกิน threshold
$r->reportCount;            // int  — จำนวน reports approved
$r->lookupsLast24h;         // int  — จำนวน user ค้นหาบัญชีนี้ใน 24 ชม.
$r->suspicious;             // bool — lookupsLast24h ≥ threshold
$r->cached;                 // bool — server ตอบจาก cache หรือไม่

// ถ้าอยากเป็น array
$r->toArray();
```

## Error Handling

```php
use GuDevIndy\CheckAccName\Client;
use GuDevIndy\CheckAccName\Exception\{
    AuthException,
    RateLimitException,
    InquiryException,
    NetworkException,
    CheckAccNameException,
};

$client = new Client(getenv('TOKEN'));

try {
    $r = $client->inquiry('004', '1234567890');
    echo $r->beneficiaryName;
} catch (AuthException $e) {
    // 401/403 — token ไม่ถูกต้อง / หมดอายุ / ถูก revoke
} catch (RateLimitException $e) {
    // 429 — เกินโควต้ารายวัน
} catch (InquiryException $e) {
    // 400/422 — บัญชีไม่พบ, รูปแบบผิด
    echo "Error code: {$e->errorCode}";
} catch (NetworkException $e) {
    // cURL fail / 5xx
} catch (CheckAccNameException $e) {
    // catch-all (parent ของทั้งหมดข้างบน)
}
```

ทุก exception มี:
- `$e->getMessage()` — ข้อความ error
- `$e->httpStatus` — HTTP status code (`int`)
- `$e->responseBody` — raw decoded JSON response (debug)

## รหัสธนาคารที่ใช้บ่อย

| Code | ตัวย่อ | ธนาคาร |
|---|---|---|
| `002` | BBL    | กรุงเทพ |
| `004` | KBANK  | กสิกรไทย |
| `006` | KTB    | กรุงไทย |
| `011` | TTB    | ทหารไทยธนชาต |
| `014` | SCB    | ไทยพาณิชย์ |
| `025` | BAY    | กรุงศรีอยุธยา |
| `030` | GSB    | ออมสิน |

ดูรายการเต็ม → https://check.gudevindy.com/banklist

## Example

```bash
export CHECKACCNAME_TOKEN="your-token"
php examples/inquiry.php
```

## Demo

ลองตัวอย่างระบบสมัครสมาชิกที่ใช้ SDK ตัวนี้:
👉 https://demo.gudevindy.com

## Security

- Token = Bearer JWT — เก็บใน `.env` หรือ secret manager เท่านั้น **อย่า commit ลง repo**
- เรียก API จาก backend (ไม่ส่ง token ไป browser)
- Validate `bankCode` + `accountNo` ก่อนเรียก API
- ใช้ HTTPS เสมอ (`sslVerify=true` default)

## Links

- 🌐 Service: https://check.gudevindy.com
- 📖 API Docs: https://check.gudevindy.com/docs
- 🧪 Demo (PHP): https://demo.gudevindy.com
- 🐛 Issues: https://github.com/gudevindy/checkaccname-php/issues

## License

MIT — see [LICENSE](LICENSE)
