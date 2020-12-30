<?php

namespace exceptions;

/**
 * Class ParameterNotFoundException
 * @author vlad <vladbara705@gmail.com>
 * @package exceptions
 */
class ParameterNotFoundException extends \DomainException
{
    /**
     * ParameterNotFoundException constructor.
     * @param null $message
     * @param int $code
     * @param \Exception|null $previous
     */
    public function __construct($message = null, $code = 0, \Exception $previous = null)
    {
        if(is_null($message)) {
            $message = 'Параметр не найден';
        }
        parent::__construct($message, $code, $previous);
    }
}