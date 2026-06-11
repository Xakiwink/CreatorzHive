<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Thrown instead of exiting when CREATORZHIVE_PHPUNIT is true (see Response::json).
 */
final class TestResponseException extends \Exception
{
    /** @var array<string, mixed> */
    public $payload;

    public int $httpStatus;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(array $payload, int $httpStatus)
    {
        parent::__construct('Test JSON response', 0, null);
        $this->payload = $payload;
        $this->httpStatus = $httpStatus;
    }
}

final class TestHtmlResponseException extends \Exception
{
    public string $html;
    public int $httpStatus;

    public function __construct(string $html, int $httpStatus)
    {
        parent::__construct('Test HTML response', 0, null);
        $this->html = $html;
        $this->httpStatus = $httpStatus;
    }
}

final class TestRedirectException extends \Exception
{
    public string $url;
    public int $httpStatus;

    public function __construct(string $url, int $httpStatus)
    {
        parent::__construct('Test redirect', 0, null);
        $this->url = $url;
        $this->httpStatus = $httpStatus;
    }
}
