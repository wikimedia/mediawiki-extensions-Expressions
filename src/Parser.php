<?php

namespace Expressions;

class Parser
{
    const VALUE_TOKEN_TYPES = [
        "T_STRING",
        "T_INT",
        "T_FALSE",
        "T_TRUE"
    ];

    /**
     * @var array
     */
    private $tokens;

    /**
     * @var int
     */
    private $current = 0;

    /**
     * Parser constructor.
     * @param string $expression_string
     * @throws ExpressionException
     */
    public function __construct($expression_string)
    {
        $this->tokens = Lexer::lex($expression_string);
    }

    /**
     * @return Node
     * @throws ExpressionException
     */
    public function parse()
    {
        if (count($this->tokens) === 0) {
            return new Node(null, null, null, null, null, null);
        }

        $ast = $this->equality();

        if ($this->current < count($this->tokens)) {
            $this->throwUnexpectedTokenError($this->tokens[$this->current]);
        }

        return $ast;
    }

    /**
     * @return Node
     * @throws ExpressionException
     */
    private function equality()
    {
        $offset_start = $this->tokens[$this->current]->getOffset();
        $expression = $this->implication();

        while ($this->match("T_NOT_EQUALS", "T_NOT_EQUALITY", "T_EQUALS", "T_EQUALITY")) {
            $operator = $this->previous();
            $right = $this->implication();

            $expression = new Node(
                $operator->getTokenType(),
                [$expression, $right],
                $operator->getMatch(),
                $operator->getTokenType(),
                $offset_start,
                $right->getOffsetEnd()
            );
        }

        return $expression;
    }

    /**
     * @return Node
     * @throws ExpressionException
     */
    private function implication()
    {
        $offset_start = $this->tokens[$this->current]->getOffset();
        $expression = $this->disjunction();

        while ($this->match("T_IMPLICATION")) {
            $operator = $this->previous();
            $right = $this->disjunction();

            $expression = new Node(
                $operator->getTokenType(),
                [$expression, $right],
                $operator->getMatch(),
                $operator->getTokenType(),
                $offset_start,
                $right->getOffsetEnd()
            );
        }

        return $expression;
    }

    /**
     * @return Node
     * @throws ExpressionException
     */
    private function disjunction()
    {
        $offset_start = $this->tokens[$this->current]->getOffset();
        $expression = $this->conjunction();

        while ($this->match("T_DISJUNCTION")) {
            $operator = $this->previous();
            $right = $this->conjunction();

            $expression = new Node(
                $operator->getTokenType(),
                [$expression, $right],
                $operator->getMatch(),
                $operator->getTokenType(),
                $offset_start,
                $right->getOffsetEnd()
            );
        }

        return $expression;
    }

    /**
     * @return Node
     * @throws ExpressionException
     */
    private function conjunction()
    {
        $offset_start = $this->tokens[$this->current]->getOffset();
        $expression = $this->logical_xor();

        while ($this->match("T_CONJUNCTION")) {
            $operator = $this->previous();
            $right = $this->logical_xor();

            $expression = new Node(
                $operator->getTokenType(),
                [$expression, $right],
                $operator->getMatch(),
                $operator->getTokenType(),
                $offset_start,
                $right->getOffsetEnd()
            );
        }

        return $expression;
    }

    /**
     * @return Node
     * @throws ExpressionException
     */
    private function logical_xor()
    {
        $offset_start = $this->tokens[$this->current]->getOffset();
        $expression = $this->comparison();

        while ($this->match("T_XOR")) {
            $operator = $this->previous();
            $right = $this->comparison();

            $expression = new Node(
                $operator->getTokenType(),
                [$expression, $right],
                $operator->getMatch(),
                $operator->getTokenType(),
                $offset_start,
                $right->getOffsetEnd()
            );
        }

        return $expression;
    }

    /**
     * @return Node
     * @throws ExpressionException
     */
    private function comparison()
    {
        $offset_start = $this->tokens[$this->current]->getOffset();
        $expression = $this->unary();

        while ($this->match("T_GREATER", "T_LESS", "T_GREATEREQUAL", "T_LESSEQUAL")) {
            $operator = $this->previous();
            $right = $this->unary();

            $expression = new Node(
                $operator->getTokenType(),
                [$expression, $right],
                $operator->getMatch(),
                $operator->getTokenType(),
                $offset_start,
                $right->getOffsetEnd()
            );
        }

        return $expression;
    }

    /**
     * @return Node
     * @throws ExpressionException
     */
    private function unary()
    {
        if ($this->match("T_NOT", "T_MINUS")) {
            $operator = $this->previous();
            $right = $this->unary();

            return new Node(
                $operator->getTokenType(),
                [$right],
                $operator->getMatch(),
                $operator->getTokenType(),
                $operator->getOffset(),
                $right->getOffsetEnd()
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

        return $this->tokens[$this->current]->getTokenType() === $token_type;
    }

    private function previous()
    {
        return $this->tokens[$this->current - 1];
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
        return $this->previous();
    }

    /**
     * @return Node
     * @throws ExpressionException
     */
    private function primary()
    {
        if ($this->match("T_FALSE")) {
            return new Node(
                null,
                null,
                false,
                "T_FALSE",
                $this->previous()->getOffset(),
                $this->previous()->getOffset() + 5
            );
        }

        if ($this->match("T_TRUE")) {
            return new Node(
                null,
                null,
                true,
                "T_TRUE",
                $this->previous()->getOffset(),
                $this->previous()->getOffset() + 4
            );
        }

        if ($this->match("T_NUMBER")) {
            return new Node(
                null,
                null,
                floatval($this->previous()->getMatch()),
                "T_NUMBER",
                $this->previous()->getOffset(),
                $this->previous()->getOffset() + strlen((string)$this->previous()->getMatch())
            );
        }

        if ($this->match("T_STRING")) {
            return new Node(
                null,
                null,
                substr($this->previous()->getMatch(), 1, -1),
                "T_STRING",
                $this->previous()->getOffset(),
                $this->previous()->getOffset() + strlen($this->previous()->getMatch())
            );
        }

        if ($this->match("T_LEFTPAREN")) {
            $expression = $this->equality();
            $this->consumeLeftParenthesis();

            return $expression;
        }

        $this->throwUnexpectedTokenError($this->previous());

        return null;
    }

    /**
     * @return mixed
     * @throws ExpressionException
     */
    private function consumeLeftParenthesis()
    {
        if ($this->check("T_RIGHTPAREN")) {
            return $this->advance();
        }

        // Find the offset of the last unclosed parenthesis.
        $idx_current = strlen(Expressions::$expression_string);
        $open_parenthesis = 1;

        while ($open_parenthesis > 0) {
            $character = Expressions::$expression_string[--$idx_current];

            if ($character === '(') $open_parenthesis--;
            if ($character === ')') $open_parenthesis++;
        }

        throw $this->error("expressions-unclosed-paren", $idx_current, 1);
    }

    /**
     * @param $token
     * @throws ExpressionException
     */
    private function throwUnexpectedTokenError($token)
    {
        if (in_array($token[1], self::VALUE_TOKEN_TYPES)) {
            $hint = wfMessage('expressions-unexpected-token-operator-hint')->plain();
        } else {
            $hint = wfMessage('expressions-unexpected-token-value-hint')->plain();
        }

        throw $this->error(
            "expressions-unexpected-token",
            $token->getOffset(),
            strlen($token->getMatch()),
            [$token->getMatch(), $hint]
        );
    }

    /**
     * @param $errormsg
     * @param $offset
     * @param int $token_length
     * @param array $additional_arguments
     * @return ExpressionException
     */
    private function error($errormsg, $offset, $token_length = 50, $additional_arguments = [])
    {
        array_unshift($additional_arguments, Expressions::highlightSegment(Expressions::$expression_string, $offset, $token_length));
        return new ExpressionException($errormsg, $additional_arguments);
    }
}