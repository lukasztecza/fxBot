<?php
namespace TinyApp\Model\System;

class Response
{
    private const DEFAULT_RULE = 'sanitize';

    private const SANITIZE = 'sanitize';
    private const HTML_ESCAPE = 'html';
    private const URL_ESCAPE = 'url';
    private const FILE_ESCAPE = 'file';
    private const NO_ESCAPE = 'raw';

    private const ALLOWED_HTML_TAGS = ['h3', 'p', 'i', 'b', 'table', 'tr', 'th', 'td', 'ul', 'ol', 'li'];

    private $file;
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
        $filename = $this->file;
        $this->fileEscapeValue($filename);
        return $filename;

        //@TODO change Exceptions to be HttpExceptions RuntimeExceptions etc

        //@TODO add TCPDF composer lib

        //@TODO add tinymce or some kind of editor handling and whitelist of html escape tags

        //@TODO add image processing lib

        //@TODO add multifiles and chunk upload jquery

        //@TODO maybe later add streaming
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
        // Add content security policy headers to allow only trusted assets source
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

    private function escapeArray(int $counter, array &$array, string $keyString) : void
    {
        foreach ($array as $key => &$value) {
            $this->handleCounter($counter);

            // Ensure safe array keys
            $originalKey = $key;
            $this->sanitizeValue($key);
            if ($key !== (string)$originalKey) {
                unset($array[$originalKey]);
                if (!empty($key)) {
                    $array[$key] = $value;
                    trigger_error('Sanitized improper key ' . $originalKey . ' into ' . $key . ' from ' . var_export($array, true) , E_USER_NOTICE);
                    continue;
                }
                trigger_error('Dropped improper key ' . $originalKey . ' from ' . var_export($array, true), E_USER_NOTICE);
                continue;
            }

            // If value is not array apply rule else call self
            $currentKeyString = empty($keyString) ? $key : $keyString . '.' . $key;
            if (is_string($value) || is_numeric($value)) {
                $this->selectAndApplyRuleForValue($value, $currentKeyString);
            }
            if (is_array($value)) {
                $this->escapeArray($counter, $value, $currentKeyString);
            }
        }
    }

    private function handleCounter(int &$counter) : void
    {
        $counter++;
        if (1000 < $counter) {
            throw new \Exception('Too big or deep array or danger of infinite recurrence, reached counter ' . var_export($counter, true));
        }
    }

    private function selectAndApplyRuleForValue(string &$value, string $currentKeyString) : void
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
            case self::FILE_ESCAPE:
                $this->fileEscapeValue($value);
                break;
            case self::NO_ESCAPE:
                break;
            default:
                throw new \Exception('Not supported escape/sanitize rule ' . $selectedRule);
        }
    }

    private function sanitizeValue(string &$value) : void
    {
        $value = preg_replace('/[^a-zA-Z0-9]/', '', $value);
    }

    private function htmlEscapeValue(string &$value) : void
    {
        $value = htmlspecialchars($value, ENT_QUOTES);

        // Unescape allowed html tags
        $patterns = $replacements = [];
        foreach (self::ALLOWED_HTML_TAGS as $tag) {
            $patterns[] = '/&lt;' . $tag . '&gt;/';
            $patterns[] = '/&lt;\/' . $tag . '&gt;/';
            $replacements[] = '<' . $tag . '>';
            $replacements[] = '</' . $tag . '>';
        }
        $value = preg_replace($patterns, $replacements, $value);
    }

    private function urlEscapeValue(string &$value) : void
    {
        $value = rawurlencode($value);
    }

    private function fileEscapeValue(string &$value) : void
    {
        preg_match('/(\/{0,1}[a-zA-Z0-9]{1,}){1,}(\.{1})([a-z]{3,4}){1}/', $value, $matches);
        if (isset($matches[0])) {
            $value = $matches[0];
        } else {
            $value = '';
        }
    }
}
