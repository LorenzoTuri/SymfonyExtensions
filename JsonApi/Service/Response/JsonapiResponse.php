<?php

namespace Lturi\SymfonyExtensions\JsonApi\Service\Response;

use Lturi\SymfonyExtensions\Framework\Service\SerializerService;
use stdClass;
use Symfony\Component\HttpFoundation\JsonResponse;

class JsonapiResponse extends JsonResponse
{
    /** @var bool */
    protected $success;
    /** @var SerializerService */
    protected $serializerService;

    public function __construct(SerializerService $serializerService)
    {
        parent::__construct();
        $this->serializerService = $serializerService;
        $this->headers->set("Content-Type", "application/vnd.api+json");
    }

    /**
     * @param mixed $data
     * @param null  $meta
     * @param null  $links
     * @param int   $status
     * @param array $headers
     *
     * @return $this
     */
    public function setSuccess(
        $data = null,
        $meta = null,
        $links = null,
        int $status = 200,
        array $headers = []
    ) :self {
        $content = new stdClass();
        $content->data = $data;
        $content->meta = $meta;
        $content->links = $links;
        $this->setResponse($status, $headers, $content);
        return $this;
    }

    public function setError(
        $errors = null,
        $meta = null,
        $links = null,
        int $status = 500,
        array $headers = []
    ) :self {
        $content = new stdClass();
        $content->errors = $errors;
        $content->meta = $meta;
        $content->links = $links;
        $this->setResponse($status, $headers, $content);
        return $this;
    }

    private function setResponse($status, $headers, $content) {
        $this->setStatusCode($status);
        $this->headers->add($headers);
        $this->setContent($this->serializerService->serialize($content, "json"));
    }
}
