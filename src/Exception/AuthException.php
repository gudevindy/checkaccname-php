<?php
declare(strict_types=1);

namespace GuDevIndy\CheckAccName\Exception;

/**
 * Token หาย / ไม่ถูกต้อง / ถูก revoke (HTTP 401, 403)
 */
class AuthException extends CheckAccNameException
{
}
