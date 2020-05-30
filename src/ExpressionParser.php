<?php

namespace Expressions;

class ExpressionParser
{
    /**
     * @var array
     */
    private $tokens;

    /**
     * @var int
     */
    private $current = 0;

    /**
     * ExpressionParser constructor.
     * @param string $expression_string
     * @throws ExpressionException
     */
    public function __construct($expression_string)
    {
        $this->tokens = ExpressionLexer::lex($expression_string);
    }

    /**
     * @return ExpressionNode
     * @throws ExpressionException
     */
    public function parse()
    {
        if (count($this->tokens) === 0) {
            return new ExpressionNode(null, null, null, null, null);
        }

        $ast = $this->equality();

        if ($this->current < count($this->tokens)) {
            throw $this->error(
                "expressions-unexpected-token",
                $this->tokens[$this->current][2],
                [$this->tokens[$this->current][0]]
            );
        }

        return $ast;
    }

    /**
     * @return ExpressionNode
     * @throws ExpressionException
     */
    private function equality()
    {
        $expression = $this->implication();

        while ($this->match("T_NOT_EQUALS", "T_NOT_EQUALITY", "T_EQUALS", "T_EQUALITY")) {
            $operator = $this->tokens[$this->current - 1];
            $right = $this->implication();

            $expression = new ExpressionNode(
                $operator[1],
                [$expression, $right],
                $operator[0],
                $operator[1],
                $operator[2]
            );
        }

        return $expression;
    }

    /**
     * @return ExpressionNode
     * @throws ExpressionException
     */
    private function implication()
    {
        $expression = $this->disjunction();

        while ($this->match("T_IMPLICATION")) {
            $operator = $this->tokens[$this->current - 1];
            $right = $this->disjunction();

            $expression = new ExpressionNode(
                $operator[1],
                [$expression, $right],
                $operator[0],
                $operator[1],
                $operator[2]
            );
        }

        return $expression;
    }

    /**
     * @return ExpressionNode
     * @throws ExpressionException
     */
    private function disjunction()
    {
        $expression = $this->conjunction();

        while ($this->match("T_DISJUNCTION")) {
            $operator = $this->tokens[$this->current - 1];
            $right = $this->conjunction();

            $expression = new ExpressionNode(
                $operator[1],
                [$expression, $right],
                $operator[0],
                $operator[1],
                $operator[2]
            );
        }

        return $expression;
    }

    /**
     * @return ExpressionNode
     * @throws ExpressionException
     */
    private function conjunction()
    {
        $expression = $this->logical_xor();

        while ($this->match("T_CONJUNCTION")) {
            $operator = $this->tokens[$this->current - 1];
            $right = $this->logical_xor();

            $expression = new ExpressionNode(
                $operator[1],
                [$expression, $right],
                $operator[0],
                $operator[1],
                $operator[2]
            );
        }

        return $expression;
    }

    /**
     * @return ExpressionNode
     * @throws ExpressionException
     */
    private function logical_xor()
    {
        $expression = $this->comparison();

        while ($this->match("T_XOR")) {
            $operator = $this->tokens[$this->current - 1];
            $right = $this->comparison();

            $expression = new ExpressionNode(
                $operator[1],
                [$expression, $right],
                $operator[0],
                $operator[1],
                $operator[2]
            );
        }

        return $expression;
    }

    /**
     * @return ExpressionNode
     * @throws ExpressionException
     */
    private function comparison()
    {
        $expression = $this->unary();

        while ($this->match("T_GREATER", "T_LESS", "T_GREATEREQUAL", "T_LESSEQUAL")) {
            $operator = $this->tokens[$this->current - 1];
            $right = $this->unary();

            $expression = new ExpressionNode(
                $operator[1],
                [$expression, $right],
                $operator[0],
                $operator[1],
                $operator[2]
            );
        }

        return $expression;
    }

    /**
     * @return ExpressionNode
     * @throws ExpressionException
     */
    private function unary()
    {
        if ($this->match("T_NOT", "T_MINUS")) {
            $operator = $this->tokens[$this->current - 1];
            $right = $this->unary();

            return new ExpressionNode(
                $operator[1],
                [$right],
                $operator[0],
                $operator[1],
                $operator[2]
            );
        }

        return $this->primary();
    }

    /**
     * Returns true and advances one token if the current token is any of the given token types.
     *
     * @return bool
     */
    private function match()
    {
        $tokens = func_get_args();

        foreach ($tokens as $token) {
            if ($this->check($token)) {
                $this->advance();
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the given token type is equal to the type of the current token.
     *
     * @param $token_type
     * @return bool
     */
    private function check($token_type)
    {
        if ($this->atEnd()) {
            return false;
        }

        return $this->tokens[$this->current][1] === $token_type;
    }

    /**
     * Returns true if we ran out of tokens.
     *
     * @return bool
     */
    private function atEnd()
    {
        return $this->current >= count($this->tokens);
    }

    /**
     * Consumes the current token and returns it.
     *
     * @return mixed
     */
    private function advance()
    {
        if (!$this->atEnd()) $this->current++;
        return $this->tokens[$this->current - 1];
    }

    /**
     * @return ExpressionNode
     * @throws ExpressionException
     */
    private function primary()
    {
        if ($this->match("T_FALSE")) {
            return new ExpressionNode(
                null,
                null,
                false,
                "T_FALSE",
                $this->tokens[$this->current - 1][2]
            );
        }

        if ($this->match("T_TRUE")) {
            return new ExpressionNode(
                null,
                null,
                true,
                $this->tokens[$this->current - 1][1],
                $this->tokens[$this->current - 1][2]
            );
        }

        if ($this->match("T_NUMBER")) {
            return new ExpressionNode(
                null,
                null,
                floatval($this->tokens[$this->current - 1][0]),
                $this->tokens[$this->current - 1][1],
                $this->tokens[$this->current - 1][2]
            );
        }

        if ($this->match("T_STRING")) {
            return new ExpressionNode(
                null,
                null,
                substr($this->tokens[$this->current - 1][0], 1, -1),
                $this->tokens[$this->current - 1][1],
                $this->tokens[$this->current - 1][2]
            );
        }

        if ($this->match("T_LEFTPAREN")) {
            $expression = $this->equality();
            $this->consume("T_RIGHTPAREN", "expressions-unclosed-paren");

            return $expression;
        }

        throw $this->error(
            "expressions-unexpected-token",
            $this->tokens[$this->current - 1][2],
            [$this->tokens[$this->current - 1][0]]
        );
    }

    /**
     * @param $token_type
     * @param $errormsg
     * @return mixed
     * @throws ExpressionException
     */
    private function consume($token_type, $errormsg)
    {
        if ($this->check($token_type)) {
            return $this->advance();
        }

        throw $this->error($errormsg, $this->tokens[$this->current - 1][2]);
    }

    /**
     * @param $errormsg
     * @param $offset
     * @param array $additional_arguments
     * @return ExpressionException
     */
    private function error($errormsg, $offset, $additional_arguments = [])
    {
        array_unshift($additional_arguments, Expressions::highlightSegment(Expressions::$expression_string, $offset));
        return new ExpressionException($errormsg, $additional_arguments);
    }
}