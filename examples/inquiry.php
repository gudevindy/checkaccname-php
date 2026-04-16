<?php
// examples/02-inquiry.php — ตรวจสอบชื่อเจ้าของบัญชี
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use GuDevIndy\CheckAccName\Client;
use GuDevIndy\CheckAccName\Exception\AuthException;
use GuDevIndy\CheckAccName\Exception\InquiryException;
use GuDevIndy\CheckAccName\Exception\RateLimitException;
use GuDevIndy\CheckAccName\Exception\NetworkException;

$token = getenv('CHECKACCNAME_TOKEN') ?: '';
if ($token === '') {
    fwrite(STDERR, "Set CHECKACCNAME_TOKEN environment variable first\n");
    exit(1);
}

$client = new Client($token);

try {
    // ตัวอย่าง: KBank (004) เลขบัญชี 1234567890
    $r = $client->inquiry(bankCode: '004', accountNo: '1234567890');

    echo "ชื่อเจ้าของบัญชี: {$r->beneficiaryName}\n";
    if (!$r->isCompany) {
        echo "  คำนำหน้า: {$r->title}\n";
        echo "  ชื่อ:      {$r->firstName}\n";
        echo "  นามสกุล:   {$r->lastName}\n";
    } else {
        echo "  นิติบุคคล: {$r->companyName}\n";
    }
    echo "เลขบัญชี (mask): {$r->beneficiaryNoMasking}\n";

    if ($r->blacklisted) {
        echo "\n⚠️  WARNING: บัญชีนี้ถูกรายงานว่าน่าสงสัย ({$r->reportCount} ครั้ง)\n";
    }
    if ($r->suspicious && !$r->blacklisted) {
        echo "\n⚠️  ต้องสงสัย: ถูกค้นหาโดย {$r->lookupsLast24h} คนใน 24 ชม.\n";
    }

} catch (AuthException $e) {
    fwrite(STDERR, "Token ไม่ถูกต้องหรือถูก revoke: {$e->getMessage()}\n");
    exit(1);
} catch (RateLimitException $e) {
    fwrite(STDERR, "เกินโควต้ารายวัน: {$e->getMessage()}\n");
    exit(1);
} catch (InquiryException $e) {
    fwrite(STDERR, "ตรวจสอบไม่ผ่าน [{$e->errorCode}]: {$e->getMessage()}\n");
    exit(1);
} catch (NetworkException $e) {
    fwrite(STDERR, "เชื่อมต่อไม่ได้: {$e->getMessage()}\n");
    exit(1);
}
