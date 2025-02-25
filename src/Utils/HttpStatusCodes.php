<?php
namespace App\Utils;

use Symfony\Component\HttpFoundation\Response;

class HttpStatusCodes
{
    public const SUCCESS             = Response::HTTP_OK;                    // 200
    public const CREATED             = Response::HTTP_CREATED;               // 201
    public const BAD_REQUEST         = Response::HTTP_BAD_REQUEST;           // 400
    public const UNAUTHORIZED        = Response::HTTP_UNAUTHORIZED;          // 401
    public const FORBIDDEN           = Response::HTTP_FORBIDDEN;             // 403
    public const NOT_FOUND           = Response::HTTP_NOT_FOUND;             // 404
    public const SERVER_ERROR        = Response::HTTP_INTERNAL_SERVER_ERROR; // 500
    public const SERVICE_UNAVAILABLE = Response::HTTP_SERVICE_UNAVAILABLE;   // 503
}
