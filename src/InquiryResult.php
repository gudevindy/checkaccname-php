<?php
declare(strict_types=1);

namespace GuDevIndy\CheckAccName;

/**
 * ผลลัพธ์จาก POST /api/inquiry
 *
 * ตัวอย่าง:
 *   $r = $client->inquiry('004', '1234567890');
 *   echo $r->beneficiaryName;       // "นาย สมชาย ใจดี"
 *   echo $r->title, ' ', $r->firstName, ' ', $r->lastName;
 *   if ($r->blacklisted) { ... }
 */
final class InquiryResult
{
    public function __construct(
        public readonly string  $beneficiaryNo,         // "1234567890"
        public readonly ?string $beneficiaryNoMasking,  // "xxx-x-x7890-x"
        public readonly string  $beneficiaryName,       // raw จาก API
        public readonly ?string $title,                 // คำนำหน้า ("นาย", "บจก.", ...)
        public readonly ?string $firstName,             // ชื่อ (null เมื่อ isCompany)
        public readonly ?string $lastName,              // นามสกุล (null เมื่อ isCompany)
        public readonly bool    $isCompany,
        public readonly ?string $companyName,           // ชื่อนิติบุคคล (เมื่อ isCompany)
        public readonly string  $bankCode,
        public readonly ?string $bankAbv,
        public readonly bool    $blacklisted,           // ถูกรายงานเกิน threshold
        public readonly int     $reportCount,           // จำนวน approved reports
        public readonly int     $lookupsLast24h = 0,    // จำนวน user ค้นหาบัญชีนี้ใน 24 ชม.
        public readonly bool    $suspicious     = false,// lookupsLast24h ≥ threshold
        public readonly bool    $cached         = false,
    ) {}

    public static function fromArray(array $a): self
    {
        return new self(
            beneficiaryNo:        (string)($a['beneficiaryNo'] ?? ''),
            beneficiaryNoMasking: isset($a['beneficiaryNoMasking']) ? (string)$a['beneficiaryNoMasking'] : null,
            beneficiaryName:      (string)($a['beneficiaryName'] ?? ''),
            title:                isset($a['title']) ? (string)$a['title'] : null,
            firstName:            isset($a['firstName']) ? (string)$a['firstName'] : null,
            lastName:             isset($a['lastName'])  ? (string)$a['lastName']  : null,
            isCompany:            (bool)($a['isCompany'] ?? false),
            companyName:          isset($a['companyName']) ? (string)$a['companyName'] : null,
            bankCode:             (string)($a['bankCode'] ?? ''),
            bankAbv:              isset($a['bankAbv']) ? (string)$a['bankAbv'] : null,
            blacklisted:          (bool)($a['blacklisted'] ?? false),
            reportCount:          (int)($a['reportCount'] ?? 0),
            lookupsLast24h:       (int)($a['lookupsLast24h'] ?? 0),
            suspicious:           (bool)($a['suspicious'] ?? false),
            cached:               (bool)($a['cached'] ?? false),
        );
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'beneficiaryNo'        => $this->beneficiaryNo,
            'beneficiaryNoMasking' => $this->beneficiaryNoMasking,
            'beneficiaryName'      => $this->beneficiaryName,
            'title'                => $this->title,
            'firstName'            => $this->firstName,
            'lastName'             => $this->lastName,
            'isCompany'            => $this->isCompany,
            'companyName'          => $this->companyName,
            'bankCode'             => $this->bankCode,
            'bankAbv'              => $this->bankAbv,
            'blacklisted'          => $this->blacklisted,
            'reportCount'          => $this->reportCount,
            'lookupsLast24h'       => $this->lookupsLast24h,
            'suspicious'           => $this->suspicious,
            'cached'               => $this->cached,
        ];
    }
}
