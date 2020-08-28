<?php

namespace Lturi\SymfonyExtensions\Controller\Api;

use Lturi\SymfonyExtensions\Classes\Controller\ApiController;
use Lturi\SymfonyExtensions\Services\Response\ApiResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Locales;
use Safe\Exceptions\DirException;

class TranslationController extends ApiController
{

    /**
     * @param Request $request
     * @param ApiResponse $apiResponse
     * @return ApiResponse
     * @throws DirException
     */
    public function getAllRequest(Request $request, ApiResponse $apiResponse): ApiResponse
    {
        $translationsPath = $this->params->get("translator.default_path");
        $translationsFiles = \Safe\scandir($translationsPath);

        $messages = [];
        $locales = array_keys(Locales::getNames());
        foreach ($locales as $locale) {
            $fileExists = in_array("messages.".$locale.".json", $translationsFiles);
            if ($fileExists) {
                $localMessages = $this->translator->getCatalogue($locale)->all("messages");
                $messages[$locale] = $localMessages;
            }
        }
        return $apiResponse->setResponse($messages);
    }
}
