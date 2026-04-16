<?php
declare(strict_types=1);

namespace GuDevIndy\CheckAccName\Exception;

/**
 * เกินโควต้ารายวัน หรือยิงเร็วเกิน (HTTP 429)
 */
class RateLimitException extends CheckAccNameException
{
}
