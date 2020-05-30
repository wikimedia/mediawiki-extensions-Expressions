<?php

namespace Expressions;

final class ExpressionNode
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
    private $offset;

    /**
     * @var string
     */
    private $token;

    /**
     * ExpressionNode constructor.
     *
     * @param string|null $operator
     * @param array|null $operands
     * @param string $value
     * @param string $token
     * @param int $offset
     */
    public function __construct($operator, $operands, $value, $token, $offset)
    {
        $this->operator = $operator;
        $this->operands = $operands;
        $this->value = $value;
        $this->token = $token;
        $this->offset = $offset;
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
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @return int|bool|string
     */
    public function getValue()
    {
        return $this->value;
    }
}