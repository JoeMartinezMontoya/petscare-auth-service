<?php
namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiException extends HttpException
{
    private string $title;
    private string $detail;
    private string $customMessage;

    public function __construct(string $title, string $detail, string $message, int $status = 400)
    {
        $this->title         = $title;
        $this->detail        = $detail;
        $this->customMessage = $message;

        parent::__construct($status, $message);
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDetail(): string
    {
        return $this->detail;
    }

    public function getCustomMessage(): string
    {
        return $this->customMessage;
    }
}
