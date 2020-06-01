<?php

namespace Expressions;

class Evaluator
{
    /**
     * @param Node $expression
     * @return mixed
     * @throws ExpressionException
     */
    public static function evaluate(Node $expression)
    {
        $operator = $expression->getOperator();
        $operands = $expression->getOperands();

        if ($operator === null) {
            return $expression->getValue();
        }

        switch ($operator) {
            case "T_CONJUNCTION":   return self::evaluateConjunction($operands[0], $operands[1]);
            case "T_DISJUNCTION":   return self::evaluateDisjunction($operands[0], $operands[1]);
            case "T_EQUALITY":      return self::evaluateEquality($operands[0], $operands[1]);
            case "T_NOT_EQUALITY":  return self::evaluateNotEquality($operands[0], $operands[1]);
            case "T_EQUALS":        return self::evaluateEquals($operands[0], $operands[1]);
            case "T_NOT_EQUALS":    return self::evaluateNotEquals($operands[0], $operands[1]);
            case "T_IMPLICATION":   return self::evaluateImplication($operands[0], $operands[1]);
            case "T_XOR":           return self::evaluateXor($operands[0], $operands[1]);
            case "T_GREATEREQUAL":  return self::evaluateGreaterEqual($operands[0], $operands[1]);
            case "T_LESSEQUAL":     return self::evaluateLessEqual($operands[0], $operands[1]);
            case "T_GREATER":       return self::evaluateGreater($operands[0], $operands[1]);
            case "T_LESS":          return self::evaluateLess($operands[0], $operands[1]);
            case "T_NOT":           return self::evaluateNot($operands[0]);
            case "T_MINUS":         return self::evaluateMinus($operands[0]);
        }

        throw new ExpressionException(
            "expressions-unexpected-token",
            [Expressions::highlightSegment(Expressions::$expression_string, $expression->getOffset(), strlen($expression->getValue()))]
        );
    }

    /**
     * @param Node $left
     * @param Node $right
     * @return bool
     * @throws ExpressionException
     */
    private static function evaluateConjunction(Node $left, Node $right)
    {
        return self::evaluate($left) && self::evaluate($right);
    }

    /**
     * @param Node $left
     * @param Node $right
     * @return bool
     * @throws ExpressionException
     */
    private static function evaluateDisjunction(Node $left, Node $right)
    {
        return self::evaluate($left) || self::evaluate($right);
    }

    /**
     * @param Node $left
     * @param Node $right
     * @return bool
     * @throws ExpressionException
     */
    private static function evaluateEquality(Node $left, Node $right)
    {
        return self::evaluate($left) === self::evaluate($right);
    }

    /**
     * @param Node $left
     * @param Node $right
     * @return bool
     * @throws ExpressionException
     */
    private static function evaluateNotEquality(Node $left, Node $right)
    {
        return self::evaluate($left) !== self::evaluate($right);
    }

    /**
     * @param Node $left
     * @param Node $right
     * @return bool
     * @throws ExpressionException
     */
    private static function evaluateEquals(Node $left, Node $right)
    {
        return self::evaluate($left) == self::evaluate($right);
    }

    /**
     * @param Node $left
     * @param Node $right
     * @return bool
     * @throws ExpressionException
     */
    private static function evaluateNotEquals(Node $left, Node $right)
    {
        return self::evaluate($left) != self::evaluate($right);
    }

    /**
     * @param Node $left
     * @param Node $right
     * @return bool
     * @throws ExpressionException
     */
    private static function evaluateImplication(Node $left, Node $right)
    {
        return !self::evaluate($left) || self::evaluate($right);
    }

    /**
     * @param Node $left
     * @param Node $right
     * @return bool
     * @throws ExpressionException
     */
    private static function evaluateXor(Node $left, Node $right)
    {
        return self::evaluate($left) xor self::evaluate($right);
    }

    /**
     * @param Node $left
     * @param Node $right
     * @return bool
     * @throws ExpressionException
     */
    private static function evaluateGreaterEqual(Node $left, Node $right)
    {
        self::matchType("double", $left);
        self::matchType("double", $right);

        return self::evaluate($left) >= self::evaluate($right);
    }

    /**
     * @param Node $left
     * @param Node $right
     * @return bool
     * @throws ExpressionException
     */
    private static function evaluateLessEqual(Node $left, Node $right)
    {
        self::matchType("double", $left);
        self::matchType("double", $right);

        return self::evaluate($left) <= self::evaluate($right);
    }

    /**
     * @param Node $left
     * @param Node $right
     * @return bool
     * @throws ExpressionException
     */
    private static function evaluateGreater(Node $left, Node $right)
    {
        self::matchType("double", $left);
        self::matchType("double", $right);

        return self::evaluate($left) > self::evaluate($right);
    }

    /**
     * @param Node $left
     * @param Node $right
     * @return bool
     * @throws ExpressionException
     */
    private static function evaluateLess(Node $left, Node $right)
    {
        self::matchType("double", $left);
        self::matchType("double", $right);

        return self::evaluate($left) < self::evaluate($right);
    }

    /**
     * @param Node $expression
     * @return bool
     * @throws ExpressionException
     */
    private static function evaluateNot(Node $expression)
    {
        self::matchType("boolean", $expression);

        return !self::evaluate($expression);
    }

    /**
     * @param Node $expression
     * @return int
     * @throws ExpressionException
     */
    private static function evaluateMinus(Node $expression)
    {
        self::matchType("double", $expression);

        return -self::evaluate($expression);
    }

    /**
     * @param $expected_type
     * @param Node $expression
     * @throws ExpressionException
     */
    private static function matchType($expected_type, Node $expression) {
        $actual_type = gettype(self::evaluate($expression));

        if ($actual_type !== $expected_type) {
            if ($expression->getOperator() !== null) {
                $is_binary = isset($expression->getOperands()[1]);
                $left_offset = $is_binary ? $expression->getOperands()[0]->getOffset() : $expression->getOffset();

                $idx_right_operand = $is_binary ? 1 : 0;
                $right_operand = $expression->getOperands()[$idx_right_operand];
                $right_offset = $right_operand->getOffset() + strlen($right_operand->getValue());

                $highlighted_segment = Expressions::highlightSegment(
                    Expressions::$expression_string,
                    $left_offset,
                    $right_offset - $left_offset
                );
            } else {
                $highlighted_segment = Expressions::highlightSegment(
                    Expressions::$expression_string,
                    $expression->getOffset(),
                    strlen($expression->getValue())
                );
            }

            throw new ExpressionException(
                "expressions-invalid-type",
                [$highlighted_segment, $expected_type, $actual_type]
            );
        }
    }
}
