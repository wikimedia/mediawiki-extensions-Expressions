<?php

namespace Expressions;

use Exception;

/**
 * Class ExpressionException
 *
 * @package Expressions
 */
class ExpressionException extends Exception
{
    /**
     * @var string
     */
    private $message_name;

    /**
     * @var array
     */
    private $message_parameters;

    /**
     * ExpressionException constructor.
     *
     * @param $message_name string Message key of the exception message.
     * @param array $message_parameters
     */
    public function __construct($message_name, $message_parameters = [])
    {
        $this->message_name = $message_name;
        $this->message_parameters = $message_parameters;

        parent::__construct();
    }

    /**
     * @return string The message key of the exception message.
     */
    public function getMessageName()
    {
        return $this->message_name;
    }

    /**
     * @return array
     */
    public function getMessageParameters()
    {
        return $this->message_parameters;
    }
}