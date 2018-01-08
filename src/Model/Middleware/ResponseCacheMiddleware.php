<?php
namespace TinyApp\Model\Middleware;

use TinyApp\Model\Middleware\MiddlewareAbstract;
use TinyApp\Model\System\Request;
use TinyApp\Model\System\Response;

class ResponseCacheMiddleware extends MiddlewareAbstract
{
    private $cacheList;

    private const CACHE_PATH = APP_ROOT_DIR . '/tmp/cache';

    public function __construct(MiddlewareInterface $next, array $cacheList)
    {
        parent::__construct($next);
        $this->cacheList = $cacheList;
    }

    public function process(Request $request) : Response
    {
        // Check if route is in cache list but consider only GET requests
        $included = false;
        if ($request->getMethod() === 'GET') {
            foreach ($this->cacheList as $ruleKey => $rule) {
                if (!isset($rule['route']) || !isset($rule['time'])) {
                    throw new \Exception('Cache rule with key ' . $ruleKey . ' must contain route and time parameters ' . var_export($rule, true));
                }

                if ($rule['route'] === $request->getRoute()) {
                    $included = true;
                    break;
                }
            }
        }

        // Return cached response if included in cache list and it exists
        $cacheFile = self::CACHE_PATH . '/' . md5($request->getRoute() . $request->getPath() . json_encode($request->getQuery())) . '.php';
        if ($included && file_exists($cacheFile) && (time() - $rule['time'] < filemtime($cacheFile))) {
            return unserialize(file_get_contents($cacheFile));
        }

        // Get response and cache it if included in cache list
        $response = $this->getNext()->process($request);
        if ($included) {
            if (!file_exists(self::CACHE_PATH)) {
                mkdir(self::CACHE_PATH, 0775, true);
            }
            file_put_contents($cacheFile, serialize($response));
        }

        return $response;
    }
}
