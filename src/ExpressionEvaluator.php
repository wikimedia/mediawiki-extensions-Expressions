<?php

namespace Expressions;

class ExpressionEvaluator
{
    /**
     * @param array $expression
     * @return mixed
     * @throws ExpressionException
     */
    public static function evaluate(ExpressionNode $expression)
    {
        $operator = $expression->getOperator();
        $operands = $expression->getOperands();

        // TODO: Type checking?

        switch ($operator) {
            case null:
                return $expression->getValue();
            case "T_CONJUNCTION":
                return self::evaluate($operands[0]) && self::evaluate($operands[1]);
            case "T_DISJUNCTION":
                return self::evaluate($operands[0]) || self::evaluate($operands[1]);
            case "T_EQUALITY":
                return self::evaluate($operands[0]) === self::evaluate($operands[1]);
            case "T_NOT_EQUALITY":
                return self::evaluate($operands[0]) !== self::evaluate($operands[1]);
            case "T_EQUALS":
                return self::evaluate($operands[0]) == self::evaluate($operands[1]);
            case "T_NOT_EQUALS":
                return self::evaluate($operands[0]) != self::evaluate($operands[1]);
            case "T_IMPLICATION":
                return !self::evaluate($operands[0]) || self::evaluate($operands[1]);
            case "T_XOR":
                return self::evaluate($operands[0]) xor self::evaluate($operands[1]);
            case "T_GREATEREQUAL":
                return self::evaluate($operands[0]) >= self::evaluate($operands[1]);
            case "T_LESSEQUAL":
                return self::evaluate($operands[0]) <= self::evaluate($operands[1]);
            case "T_GREATER":
                return self::evaluate($operands[0]) > self::evaluate($operands[1]);
            case "T_LESS":
                return self::evaluate($operands[0]) < self::evaluate($operands[1]);
            case "T_NOT":
                return !self::evaluate($operands[0]);
            case "T_MINUS":
                if (!is_double($operands[0]->getValue())) {
                    throw new ExpressionException(
                        "expressions-invalid-operand",
                        [
                            Expressions::highlightSegment(Expressions::$expression_string, $expression->getOffset()),
                            "number",
                            gettype($operands[0]->getValue())
                        ]
                    );
                }
                return -self::evaluate($operands[0]);
        }

        throw new ExpressionException(
            "expressions-invalid-operand",
            [Expressions::highlightSegment(Expressions::$expression_string, $expression->getOffset())]
        );
    }
}