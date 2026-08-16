<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ValidateRequestInput
{
    private const MAX_DEPTH = 6;
    private const MAX_QUERY_FIELDS = 50;
    private const MAX_BODY_FIELDS = 300;
    private const MAX_QUERY_STRING_LENGTH = 4096;
    private const MAX_QUERY_VALUE_LENGTH = 255;
    private const MAX_BODY_VALUE_LENGTH = 65535;
    private const MAX_SNAPSHOT_IMAGE_VALUE_LENGTH = 7000000;

    /**
     * @param  Closure(Request): Response  $next
     *
     * @throws ValidationException
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (strlen($request->server('QUERY_STRING', '')) > self::MAX_QUERY_STRING_LENGTH) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $this->reject('Chuỗi truy vấn quá dài.');
        }

        // Luong: Gan ket qua xu ly vao bien $largeBodyFields.
        $largeBodyFields = $request->is('thu-kinh/luu-ket-qua')
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            ? ['image' => self::MAX_SNAPSHOT_IMAGE_VALUE_LENGTH]
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            : [];

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->validatePayload($request->query->all(), self::MAX_QUERY_FIELDS, self::MAX_QUERY_VALUE_LENGTH);
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->validatePayload($request->request->all(), self::MAX_BODY_FIELDS, self::MAX_BODY_VALUE_LENGTH, $largeBodyFields);

        // Luong: Tra ve ket qua cuoi cung cua ham.
        return $next($request);
    }

    /**
     * @throws ValidationException
     */
    private function validatePayload(array $payload, int $maxFields, int $maxValueLength, array $fieldValueLengths = []): void
    {
        if (count(Arr::dot($payload)) > $maxFields) {
            $this->reject('Dữ liệu gửi lên có quá nhiều trường.');
        }

        $this->validateNode($payload, $maxValueLength, $fieldValueLengths);
    }

    /**
     * @throws ValidationException
     */
    private function validateNode(mixed $value, int $maxValueLength, array $fieldValueLengths = [], int $depth = 0, ?string $key = null): void
    {
        if ($depth > self::MAX_DEPTH) {
            $this->reject('Dữ liệu gửi lên quá sâu.');
        }

        if (is_array($value)) {
            foreach ($value as $childKey => $child) {
                $childKey = (string) $childKey;
                $this->validateKey($childKey);
                $this->validateNode($child, $maxValueLength, $fieldValueLengths, $depth + 1, $childKey);
            }

            return;
        }

        if (is_scalar($value) || $value === null) {
            $this->validateScalar((string) $value, $fieldValueLengths[$key] ?? $maxValueLength);
        }
    }

    /**
     * @throws ValidationException
     */
    private function validateKey(string $key): void
    {
        if ($key === '' || strlen($key) > 80 || preg_match('/[^A-Za-z0-9_.:-]/', $key) === 1) {
            $this->reject('Tên trường dữ liệu không hợp lệ.');
        }
    }

    /**
     * @throws ValidationException
     */
    private function validateScalar(string $value, int $maxValueLength): void
    {
        if (strlen($value) > $maxValueLength) {
            $this->reject('Dữ liệu gửi lên quá dài.');
        }

        if (preg_match('//u', $value) !== 1) {
            $this->reject('Dữ liệu gửi lên không đúng mã hóa UTF-8.');
        }

        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value) === 1) {
            $this->reject('Dữ liệu gửi lên chứa ký tự không hợp lệ.');
        }
    }

    /**
     * @throws ValidationException
     */
    private function reject(string $message): never
    {
        throw ValidationException::withMessages([
            'request' => $message,
        ]);
    }
}
