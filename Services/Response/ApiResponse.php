<?php

namespace Lturi\SymfonyExtensions\Services\Response;

use Lturi\SymfonyExtensions\Services\SerializerService;
use Symfony\Component\HttpFoundation\JsonResponse;

class ApiResponse extends JsonResponse
{
    /** @var bool */
    protected $success;
    /** @var SerializerService */
    protected $serializerService;

    public function __construct(SerializerService $serializerService)
    {
        parent::__construct();
        $this->serializerService = $serializerService;
    }

    /**
     * @param mixed $data
     * @param mixed $additional
     * @param int   $status
     * @param array $headers
     *
     * @return $this
     */
    public function setResponse(
        $data = null,
        $additional = null,
        int $status = 200,
        array $headers = []
    ) :self {
        $this->success = $status < 400;
        $this->setStatusCode($status);
        $this->headers->add($headers);

        $this->insertContent($data, $additional, $status);

        return $this;
    }

    /**
     * @return bool
     */
    public function getSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @param mixed $data
     * @param mixed $additional
     * @param int $status
     */
    private function insertContent($data, $additional, $status): void
    {
        $content = new \stdClass();
        $content->success = $this->success;
        $content->code = $status;
        if ($content->success) {
            $content->data = $data;
        } else {
            $content->error = $data;
        }
        $content->additional = $additional;
        $this->setContent($this->serializerService->serialize($content, "json"));
    }
}
