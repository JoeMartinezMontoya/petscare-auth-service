<?php
namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiException extends HttpException
{
    public function __construct(string $title, string $detail, string $message, int $status = 400)
    {
        $errorData = [
            'status'  => $status,
            'title'   => $title,
            'detail'  => $detail,
            'message' => $message,
        ];

        parent::__construct($status, json_encode($errorData));
    }
}
