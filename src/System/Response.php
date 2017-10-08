<?php
namespace TinyApp\System;

class Response
{
    const SECURE = 'secure';
    const HTML_ESCAPE = 'html';
    const HTML_ATTRIBUTE_ESCAPE = 'html-attr';
    const CSS_ESCAPE = 'css';
    const JS_ESCAPE = 'js';
    const NO_ESCAPE = 'raw';

    private $template;
    private $variables;
    private $headers;
    private $rules;

    public function __construct(string $template = null, array $variables, array $headers, array $rules)
    {
        $this->template = $template;
        $this->variables = $variables;
        $this->headers = $headers;
        $this->rules = $rules;
    }

    public function getTemplate() : string
    {
        return $this->template;
    }

    public function getVariables() : array
    {
        $counter = 0;
        $variables = $this->variables;
        $this->escapeArray($counter, $variables, '');
        return $variables;
    }

    public function getHeaders() : array
    {
        return $this->headers;
    }

    private function escapeArray(int $counter, array &$array, string $keyString)
    {
        $counter++;
        if (1000 < $counter) {
            throw new \Exception('Too deep array or danger of infinite recurrence, reached counter ' . var_export($counter, true));
        }

        foreach ($array as $key => &$value) {
            $currentKeyString = empty($keyString) ? $key : $keyString . '.' . $key;

            if (is_string($value) || is_numeric($value)) {
                $this->selectAndApplyRuleForValue($value, $currentKeyString);
            }

            if (is_array($value)) {
                $this->escapeArray($counter, $value, $currentKeyString);
            }
        }
    }

    private function selectAndApplyRuleForValue(&$value, $currentKeyString)
    {
        $selectedRule = self::SECURE;
        foreach ($this->rules as $ruleKeyString => $rule) {
            if (strpos($currentKeyString, $ruleKeyString) === 0) {
                $selectedRule = $rule;
                unset($rule);unset($ruleKeyString);
                break;
            }
        }

        switch($selectedRule) {
            case self::SECURE:
                $this->secureValue($value);
                break;
            case self::HTML_ESCAPE:
                $this->htmlEscapeValue($value);
                break;
            case self::HTML_ATTRIBUTE_ESCAPE:
                $this->htmlAttributeEscapeValue($value);
                break;
            case self::CSS_ESCAPE:
                $this->cssEscapeValue($value);
                break;
            case self::JS_ESCAPE:
                $this->jsEscapeValue($value);
                break;
            case self::NO_ESCAPE:
                break;
            default:
                $this->secureValue($value);
        }
    }

    private function secureValue(&$value)
    {
        $value = preg_replace('/[^a-zA-Z0-9]/', '', $value);
    }

    //@TODO update html escape from twig
    private function htmlEscapeValue(&$value)
    {
        $value = htmlspecialchars($value, ENT_QUOTES);
    }

    //@TODO add attr escape from twig
    private function htmlAttributeEscapeValue(&$value)
    {
        $value = $value . '_html-attr';
    }

    //@TODO add css escape from twig
    private function cssEscapeValue(&$value)
    {
        $value = $value . '_css';
    }

    //@TODO add js escape from twig
    private function jsEscapeValue(&$value)
    {
        $value = $value . '_js';
    }
}
