<?php

namespace Expressions;

final class Node
{
    /**
     * @var string|null
     */
    private $operator;

    /**
     * @var array|null
     */
    private $operands;

    /**
     * @var string
     */
    private $value;

    /**
     * @var int
     */
    private $offset_start;

    /**
     * @var string
     */
    private $token;

    /**
     * @var int
     */
    private $length;

    /**
     * Node constructor.
     *
     * @param string|null $operator
     * @param array|null $operands
     * @param string $value
     * @param string $token
     * @param int $offset_start
     * @param int $length
     */
    public function __construct($operator, $operands, $value, $token, $offset_start, $length = null)
    {
        $this->operator = $operator;
        $this->operands = $operands;
        $this->value = $value;
        $this->token = $token;
        $this->offset_start = $offset_start;
        $this->length = $length;
    }

    /**
     * @return string|null
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @return array|null
     */
    public function getOperands()
    {
        return $this->operands;
    }

    /**
     * @return int
     */
    public function getOffsetStart()
    {
        return $this->offset_start;
    }

    /**
     * @return int|bool|string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return int|null
     */
    public function getLength()
    {
        return $this->length;
    }

    public function setLength($length)
    {
        $this->length = $length;
    }
}