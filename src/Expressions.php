<?php

namespace Expressions;

final class Expressions
{
    public static $expression_string;

    /**
     * @param \Parser $parser
     */
    public static function onParserFirstCallInit(\Parser $parser)
    {
        spl_autoload_register(function ($class) {
            $class_parts = explode("\\", $class);
            array_shift($class_parts);

            $path = __DIR__ . "/" . implode("/", $class_parts) . ".php";

            include_once($path);
        });

        $parser->setFunctionHook('expression', [self::class, 'evaluateExpression']);
    }

    /**
     * @param \Parser $parser
     * @param string $expression_string
     * @param string $consequent
     * @param string $alternate
     * @return string|array
     */
    public static function evaluateExpression(\Parser $parser, $expression_string = '', $consequent = '', $alternate = '')
    {
        self::$expression_string = $expression_string;

        try {
            $parser = new Parser(self::$expression_string);

            $expression = $parser->parse();
            $expression = Evaluator::evaluate($expression);

            return $expression ? $consequent : $alternate;
        } catch (ExpressionException $exception) {
            return self::error($exception->getMessageName(), $exception->getMessageParameters());
        }
    }

    /**
     * @param $errormsg
     * @param array $params
     * @return array
     */
    public static function error($errormsg, $params = [])
    {
        return [
            \Html::rawElement(
                'span',
                ['class' => 'error'],
                wfMessage($errormsg, $params)->parse()
            ), 'noparse' => true, 'isHTML' => false
        ];
    }

    /**
     * Highlights the given code segment at the given offset. Used for error reporting.
     *
     * @param $expression
     * @param $offset
     * @param $token_length
     * @return string
     */
    public static function highlightSegment($expression, $offset, $token_length)
    {
        $max_expression_length = 60;

        $truncated = htmlspecialchars(
            substr($expression, max(0, $offset - ($max_expression_length / 2)), $max_expression_length)
        );

        if (strlen($truncated) < strlen($expression)) {
            $truncated .= "...";
        }

        if ($offset > ($max_expression_length / 2)) {
            $truncated = "..." . $truncated;
            $offset = $offset - ($offset - ($max_expression_length / 2)) + 3; // +3 because we add three dots.
        }

        return "<pre>1| $truncated\n" . str_repeat("&nbsp;", $offset + 3) . str_repeat("^", $token_length) . "</pre>";
    }
}