<?php

namespace Lturi\SymfonyExtensions\Rest\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class RestrictUser {
    /**
     * @var string
     * @Required
     */
    public $dbFieldName;
    /**
     * @var string
     * @Required
     */
    public $userGetter;

    /**
     * @return string
     */
    public function getDbFieldName() {
        return $this->dbFieldName;
    }
    /**
     * @return string
     */
    public function getUserGetter() {
        return $this->userGetter;
    }
}