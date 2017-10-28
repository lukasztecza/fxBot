<?php
namespace TinyApp\Model\System;

class Response
{
    const DEFAULT_RULE = 'sanitize';

    const SANITIZE = 'sanitize';
    const HTML_ESCAPE = 'html';
    const URL_ESCAPE = 'url';
    const NO_ESCAPE = 'raw';

    private $template;
    private $variables;
    private $rules;
    private $headers;
    private $cookies;

    public function __construct(string $file = null, array $variables = [], array $rules = [], array $headers = [], array $cookies = [])
    {
        $this->file = $file;
        $this->variables = $variables;
        $this->rules = $rules;
        $this->headers = $headers;
        $this->cookies = $cookies;
    }

    public function getFile() : string
    {
        // Filter out .. or ../ and allow only alphanumeric characters and . and / for file names
        return preg_replace(['/(\.\.\/)/', '/\.\./'], '', preg_replace('/[^a-zA-Z0-9\.\/]/', '', $this->file));
        //@TODO ensure that . is before extension only
    }

    public function getVariables() : array
    {
        // Return escaped variables by default but do not change class member
        $variables = $this->variables;
        $this->escapeArray(0, $variables, '');
        return $variables;
    }

    public function getHeaders() : array
    {
        return $this->headers;
    }

    public function getCookies() : array
    {
        $cookies = $this->cookies;
        foreach ($cookies as &$cookie) {
            if (empty($cookie['name']) || empty($cookie['value'])) {
                throw new \Exception('Cookie name and value is required ' . var_export($cookie, true));
            }

            $cookie['expire'] = $cookie['expire'] ?? 0;
            $cookie['path'] = $cookie['path'] ?? '/';
            $cookie['domain'] = $cookie['domain'] ?? '';
            $cookie['secure']  = $cookie['secure'] ?? false;
            $cookie['httponly'] = $cookie['httponly'] ?? true;
        }

        return $cookies;
    }

    private function escapeArray(int $counter, array &$array, string $keyString)
    {
        $this->handleCounter($counter);

        // If value is not array apply rule else call self
        foreach ($array as $key => &$value) {
            $this->handleCounter($counter);

            $originalKey = $key;
            $this->sanitizeValue($key);
            if ($key !== (string)$originalKey) {
                unset($array[$originalKey]);
                if (!empty($key)) {
                    $array[$key] = $value;
                    trigger_error('Sanitized improper key ' . $originalKey . ' from ' . var_export($array, true) , E_USER_NOTICE);
                    continue;
                }
                trigger_error('Dropped improper key ' . $originalKey . ' from ' . var_export($array, true), E_USER_NOTICE);
                continue;
            }

            $currentKeyString = empty($keyString) ? $key : $keyString . '.' . $key;

            if (is_string($value) || is_numeric($value)) {
                $this->selectAndApplyRuleForValue($value, $currentKeyString);
            }

            if (is_array($value)) {
                $this->escapeArray($counter, $value, $currentKeyString);
            }
        }
    }

    private function handleCounter(&$counter) : void
    {
        $counter++;
        if (1000 < $counter) {
            throw new \Exception('Too deep array or danger of infinite recurrence, reached counter ' . var_export($counter, true));
        }
    }

    private function selectAndApplyRuleForValue(&$value, $currentKeyString)
    {
        // Search for passed rule or use the default one
        $selectedRule = self::DEFAULT_RULE;
        foreach ($this->rules as $ruleKeyString => $rule) {
            if (strpos($currentKeyString, $ruleKeyString) === 0) {
                $selectedRule = $rule;
                unset($rule);unset($ruleKeyString);
                break;
            }
        }

        // Allow only known rules
        switch($selectedRule) {
            case self::SANITIZE:
                $this->sanitizeValue($value);
                break;
            case self::HTML_ESCAPE:
                $this->htmlEscapeValue($value);
                break;
            case self::URL_ESCAPE:
                $this->urlEscapeValue($value);
                break;
            case self::NO_ESCAPE:
                break;
            default:
                throw new \Exception('Not supported escape/sanitize rule ' . $selectedRule);
        }
    }

    private function sanitizeValue(&$value)
    {
        $value = preg_replace('/[^a-zA-Z0-9]/', '', $value);
    }

    private function htmlEscapeValue(&$value)
    {
        $value = htmlspecialchars($value, ENT_QUOTES);
        //@TODO add whitelist of characters and change them back
    }

    private function urlEscapeValue(&$value)
    {
        $value = rawurlencode($value);
    }
}
