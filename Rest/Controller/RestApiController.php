<?php

namespace Lturi\SymfonyExtensions\Rest\Controller;

use ArrayObject;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\EntityManagerInterface;
use Lturi\SymfonyExtensions\Rest\Annotation\RestrictUser;
use Lturi\SymfonyExtensions\Framework\Exception\UnauthorizedUserException;
use Lturi\SymfonyExtensions\Framework\Exception\ValidationErrorsException;
use Lturi\SymfonyExtensions\Framework\Service\Response\ApiResponse;
use Lturi\SymfonyExtensions\Framework\Service\SerializerService;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class RestApiController extends ApiController
{
    /** @var Reader  */
    protected $reader;

    public function __construct (
        ContainerInterface $container,
        SerializerService $serializerService,
        ParameterBagInterface $params,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        Reader $reader
    ) {
        parent::__construct($container, $serializerService, $params, $translator, $entityManager, $validator);

        $this->reader = $reader;
    }

    /**
     * @param int         $id
     * @param ApiResponse $apiResponse
     *
     * @return ApiResponse
     * @throws UnauthorizedUserException|ReflectionException
     */
    protected function getRequestAction(int $id, ApiResponse $apiResponse): ApiResponse
    {
        $criteria = [
            "id" => $id,
        ];
        $data = $this->entityManager->getRepository($this->getEntityClass())->findOneBy($criteria);
        if ($data && $authAnnotation = $this->requiresAuthCheck()) {
            $entityUser = $data->{$authAnnotation->getUserGetter()}();
            if ($entityUser && $entityUser != $this->getUser()) {
                throw new UnauthorizedUserException("Unauthorized user for this entity");
            }
        }
        if ($data) {
            $viewModelClass = $this->getEntityViewModelClass();
            return $apiResponse->setResponse(new $viewModelClass($data));
        } else {
            throw new NotFoundHttpException("Not found");
        }
    }

    /**
     * @param Request     $request
     * @param ApiResponse $apiResponse
     *
     * @return ApiResponse
     * @throws ReflectionException
     */
    protected function getAllRequestAction(Request $request, ApiResponse $apiResponse): ApiResponse
    {
        $requestData = $this->loadRequestData($request);
        $page = isset($requestData["page"]) ? (int)$requestData["page"] : 0;
        $limit = isset($requestData["limit"]) ?
            abs((int) $requestData["limit"]) :
            $this->getParameter("rest.page_limit");

        /** @var ServiceEntityRepository $entityRepository */
        $entityRepository = $this->entityManager->getRepository($this->getEntityClass());

        $criteria = [];
        $orderings = [];
        if (isset($requestData["filter"])) {
            $criteria = $requestData["filter"];
        }
        if (isset($requestData["order"])) {
            if (is_array($requestData["order"]))
                $orderings = $requestData["order"];
            else {
                $orderings = ["id" => $requestData["order"]];
            }
        }
        if ($authAnnotation = $this->requiresAuthCheck()) {
            $criteria[$authAnnotation->getDbFieldName()] = $this->getUser();
        }

        if ($limit !== 0) {
            $data = $entityRepository->findBy($criteria, $orderings, $limit, $page*$limit);
        } else {
            $data = $entityRepository->findBy($criteria, $orderings);
        }

        $total = $entityRepository->count($criteria);
        $totalPages = $limit > 0 ? ceil($total/$limit) : 1;

        $viewModelClass = $this->getEntityViewModelClass();
        $return = new ArrayObject();
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
     * @throws ExceptionInterface|ReflectionException|UnauthorizedUserException
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
     * @throws ExceptionInterface|ReflectionException|UnauthorizedUserException
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
        // TODO: check here for auth
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
     * @throws ReflectionException|UnauthorizedUserException
     */
    private function processDataUpdate(array $requestData, ApiResponse $apiResponse, $injectInto = null): ApiResponse
    {
        $data = $this->serializerService->denormalize(
            $requestData,
            $this->getEntityClass(),
            'json',
            !!$injectInto ? [AbstractNormalizer::OBJECT_TO_POPULATE => $injectInto] : []
        );
        if ($data && $authAnnotation = $this->requiresAuthCheck()) {
            $entityUser = $data->{$authAnnotation->getUserGetter()}();
            if ($entityUser && $entityUser != $this->getUser()) {
                throw new UnauthorizedUserException("Unauthorized user for this entity");
            }
        }

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

    /**
     * @return RestrictUser|null
     * @throws ReflectionException
     */
    private function requiresAuthCheck() {
        /** logged administrators does not require authentication permissions */
        if ($this->userHasRole("ROLE_ADMINISTRATOR")) return null;

        /** @var RestrictUser $annotation */
        $annotation = $this->reader->getClassAnnotation(
            new ReflectionClass($this->getEntityClass()),
            RestrictUser::class
        );
        return $annotation;
    }

    private function userHasRole(string $role) {
        $user = $this->getUser();
        if (!$user) return false;
        foreach ($user->getRoles() as $userRole) {
            if ($userRole == $role) return true;
        }
        return false;
    }
}
