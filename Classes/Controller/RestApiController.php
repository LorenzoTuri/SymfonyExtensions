<?php

namespace Lturi\SymfonyExtensions\Classes\Controller;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Lturi\SymfonyExtensions\Exceptions\ValidationErrorsException;
use Lturi\SymfonyExtensions\Services\Response\ApiResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

/**
 * @package App\Classes\Controller
 */
abstract class RestApiController extends ApiController
{
    protected function getRequestAction(int $id, ApiResponse $apiResponse): ApiResponse
    {
        $data = $this->entityManager->getRepository($this->getEntityClass())->find($id);
        if ($data) {
            $viewModelClass = $this->getEntityViewModelClass();
            return $apiResponse->setResponse(new $viewModelClass($data));
        } else {
            throw new NotFoundHttpException("Not found");
        }
    }

    protected function getAllRequestAction(Request $request, ApiResponse $apiResponse): ApiResponse
    {
        $requestData = $this->loadRequestData($request);
        $page = isset($requestData["page"]) ? (int)$requestData["page"] : 0;
        $limit = isset($requestData["limit"]) ?
            abs((int) $requestData["limit"]) :
            $this->getParameter("rest.page_limit");

        $criteria = [];
        $orderings = [];

        /** @var ServiceEntityRepository $entityRepository */
        $entityRepository = $this->entityManager->getRepository($this->getEntityClass());

        if ($limit !== 0) {
            $data = $entityRepository->findBy($criteria, $orderings, $limit, $page*$limit);
        } else {
            $data = $entityRepository->findBy($criteria, $orderings);
        }

        $total = $entityRepository->count($criteria);
        $totalPages = $limit > 0 ? ceil($total/$limit) : 1;

        $viewModelClass = $this->getEntityViewModelClass();
        $return = new \ArrayObject();
        foreach ($data as $datum) {
            $return->append(new $viewModelClass($datum));
        }
        $additional = [
            "page" => $page,
            "pageLimit" => $limit,
            "total" => $total,
            "totalPages" => $totalPages
        ];
        return $apiResponse->setResponse($return, $additional);
    }

    /**
     * @param Request     $request
     * @param ApiResponse $apiResponse
     *
     * @return ApiResponse
     * @throws ExceptionInterface
     */
    protected function postRequestAction(Request $request, ApiResponse $apiResponse): ApiResponse
    {
        $requestData = $this->loadRequestData($request);
        if (isset($requestData["id"])) {
            unset($requestData["id"]);
        }
        return $this->processDataUpdate($requestData, $apiResponse);
    }

    /**
     * @param int         $id
     * @param Request     $request
     * @param ApiResponse $apiResponse
     *
     * @return ApiResponse
     * @throws ExceptionInterface
     */
    protected function putRequestAction(int $id, Request $request, ApiResponse $apiResponse): ApiResponse
    {
        $data = $this->entityManager->getRepository($this->getEntityClass())->find($id);
        if ($data) {
            $requestData = $this->loadRequestData($request);
            if (isset($requestData["id"])) unset($requestData["id"]);
            return $this->processDataUpdate($requestData, $apiResponse, $data);
        } else {
            throw new NotFoundHttpException("Not found");
        }
    }

    protected function deleteRequestAction(int $id, ApiResponse $apiResponse): ApiResponse
    {
        $data = $this->entityManager->getRepository($this->getEntityClass())->find($id);
        if ($data) {
            $this->entityManager->remove($data);
            $this->entityManager->flush();
            return $apiResponse->setResponse([
                "id" => $id,
                "message" => "Deleted"
            ]);
        } else {
            throw new NotFoundHttpException("Not found");
        }
    }

    abstract public function getEntityClass(): string;
    abstract public function getEntityViewModelClass(): string;
    abstract public function getAllRequest(Request $request, ApiResponse $apiResponse): ApiResponse;
    abstract public function getRequest(Request $request, int $id, ApiResponse $apiResponse): ApiResponse;
    abstract public function postRequest(Request $request, ApiResponse $apiResponse): ApiResponse;
    abstract public function putRequest(Request $request, int $id, ApiResponse $apiResponse): ApiResponse;
    abstract public function deleteRequest(Request $request, int $id, ApiResponse $apiResponse): ApiResponse;

    /**
     * @param array<mixed> $requestData
     * @param ApiResponse $apiResponse
     * @param mixed|null  $injectInto
     *
     * @return ApiResponse
     * @throws ExceptionInterface
     */
    private function processDataUpdate(array $requestData, ApiResponse $apiResponse, $injectInto = null): ApiResponse
    {
        $data = $this->serializerService->denormalize(
            $requestData,
            $this->getEntityClass(),
            'json',
            !!$injectInto ? [AbstractNormalizer::OBJECT_TO_POPULATE => $injectInto] : []
        );
        $validationErrors = $this->validator->validate($data);
        if (count($validationErrors) == 0) {
            $this->entityManager->persist($data);
            $this->entityManager->flush();
            $viewModelClass = $this->getEntityViewModelClass();
            // Since data may have changed, let's reload it
            $data = $this->entityManager->getRepository($this->getEntityClass())->find($data->getId());
            return $apiResponse->setResponse(new $viewModelClass($data));
        } else {
            throw new ValidationErrorsException($validationErrors);
        }
    }
}
