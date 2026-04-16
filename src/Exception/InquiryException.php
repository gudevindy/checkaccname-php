<?php
declare(strict_types=1);

namespace GuDevIndy\CheckAccName\Exception;

/**
 * คำขอผิด / บัญชีไม่พบ / รูปแบบไม่ถูกต้อง / upstream ปฏิเสธ (HTTP 400, 422)
 */
class InquiryException extends CheckAccNameException
{
    /** Error code จาก KBank (เช่น "IQ01") ถ้ามี */
    public ?string $errorCode = null;
}
