<?php

declare(strict_types=1);

namespace Phast\Router;

use Closure;
use Phast\Router\Cache\FileCache;
use Phast\Router\DataGenerator\MarkBased as MarkBasedDataGenerator;
use Phast\Router\Dispatcher\MarkBased as MarkBasedDispatcher;
use Phast\Router\Dispatcher\Result\Matched;
use Phast\Router\Dispatcher\Result\MethodNotAllowed;
use Phast\Router\Dispatcher\Result\NotFound;
use Phast\Router\Exception\BadRouteException;
use Phast\Router\Exception\UriGenerationException;
use Phast\Router\GenerateUri\FromProcessedConfiguration;
use Phast\Router\GenerateUri\GeneratedUri;
use Phast\Router\RouteParser\Std as StdRouteParser;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use Stringable;

final class Router implements ConfigureRoutes, Dispatcher, GenerateUri
{
    public const ROUTE_NAME = '_name';
    public const ROUTE_REGEX = '_route';
    
    private string $currentGroupPrefix = '';
    private array $namedRoutes = [];
    private array $staticRoutes = [];
    private array $methodToRegexToRoutesMap = [];
    private array $processedConfiguration = [];
    private bool $configurationBuilt = false;
    
    private ?string $cacheKey;
    private ?Cache $cacheDriver;

    public function __construct(?string $cacheKey = null, ?Cache $cacheDriver = null)
    {
        $this->cacheKey = $cacheKey;
        $this->cacheDriver = $cacheDriver ?? ($cacheKey !== null ? new FileCache() : null);
    }

    /**
     * @param string|string[] $httpMethod
     * @param array<string, mixed> $extraParameters
     */
    public function addRoute(string|array $httpMethod, string $route, mixed $handler, array $extraParameters = []): void
    {
        $route = $this->currentGroupPrefix . $route;
        $routeParser = new StdRouteParser();
        $parsedRoutes = $routeParser->parse($route);

        $extraParameters = [self::ROUTE_REGEX => $route] + $extraParameters;

        foreach ((array) $httpMethod as $method) {
            foreach ($parsedRoutes as $parsedRoute) {
                $this->addParsedRoute($method, $parsedRoute, $handler, $extraParameters);
            }
        }

        if (array_key_exists(self::ROUTE_NAME, $extraParameters)) {
            $this->registerNamedRoute($extraParameters[self::ROUTE_NAME], $parsedRoutes);
        }
    }

    private function addParsedRoute(string $httpMethod, array $routeData, mixed $handler, array $extraParameters): void
    {
        if ($this->isStaticRoute($routeData)) {
            $this->addStaticRoute($httpMethod, $routeData, $handler, $extraParameters);
        } else {
            $this->addVariableRoute($httpMethod, $routeData, $handler, $extraParameters);
        }
    }

    private function isStaticRoute(array $routeData): bool
    {
        return count($routeData) === 1 && is_string($routeData[0]);
    }

    private function addStaticRoute(string $httpMethod, array $routeData, mixed $handler, array $extraParameters): void
    {
        $routeStr = $routeData[0];

        if (isset($this->staticRoutes[$httpMethod][$routeStr])) {
            throw BadRouteException::alreadyRegistered($routeStr, $httpMethod);
        }

        if (isset($this->methodToRegexToRoutesMap[$httpMethod])) {
            foreach ($this->methodToRegexToRoutesMap[$httpMethod] as $route) {
                if ($route->matches($routeStr)) {
                    throw BadRouteException::shadowedByVariableRoute($routeStr, $route->regex, $httpMethod);
                }
            }
        }

        $this->staticRoutes[$httpMethod][$routeStr] = [$handler, $extraParameters];
    }

    private function addVariableRoute(string $httpMethod, array $routeData, mixed $handler, array $extraParameters): void
    {
        $route = new Route($httpMethod, $routeData, $handler, $extraParameters);
        $regex = $route->regex;

        if (isset($this->methodToRegexToRoutesMap[$httpMethod][$regex])) {
            throw BadRouteException::alreadyRegistered($regex, $httpMethod);
        }

        $this->methodToRegexToRoutesMap[$httpMethod][$regex] = $route;
    }

    private function registerNamedRoute(mixed $name, array $parsedRoutes): void
    {
        if (!is_string($name) || $name === '') {
            throw BadRouteException::invalidRouteName($name);
        }

        if (array_key_exists($name, $this->namedRoutes)) {
            throw BadRouteException::namedRouteAlreadyDefined($name);
        }

        $this->namedRoutes[$name] = array_reverse($parsedRoutes);
    }

    public function addGroup(string $prefix, callable $callback): void
    {
        $previousGroupPrefix = $this->currentGroupPrefix;
        $this->currentGroupPrefix = $previousGroupPrefix . $prefix;
        $callback($this);
        $this->currentGroupPrefix = $previousGroupPrefix;
    }

    /**
     * @param array<string, mixed> $extraParameters
     */
    public function any(string $route, mixed $handler, array $extraParameters = []): void
    {
        $this->addRoute('*', $route, $handler, $extraParameters);
    }

    /**
     * @param array<string, mixed> $extraParameters
     */
    public function get(string $route, mixed $handler, array $extraParameters = []): void
    {
        $this->addRoute('GET', $route, $handler, $extraParameters);
    }

    /**
     * @param array<string, mixed> $extraParameters
     */
    public function post(string $route, mixed $handler, array $extraParameters = []): void
    {
        $this->addRoute('POST', $route, $handler, $extraParameters);
    }

    /**
     * @param array<string, mixed> $extraParameters
     */
    public function put(string $route, mixed $handler, array $extraParameters = []): void
    {
        $this->addRoute('PUT', $route, $handler, $extraParameters);
    }

    /**
     * @param array<string, mixed> $extraParameters
     */
    public function delete(string $route, mixed $handler, array $extraParameters = []): void
    {
        $this->addRoute('DELETE', $route, $handler, $extraParameters);
    }

    /**
     * @param array<string, mixed> $extraParameters
     */
    public function patch(string $route, mixed $handler, array $extraParameters = []): void
    {
        $this->addRoute('PATCH', $route, $handler, $extraParameters);
    }

    /**
     * @param array<string, mixed> $extraParameters
     */
    public function head(string $route, mixed $handler, array $extraParameters = []): void
    {
        $this->addRoute('HEAD', $route, $handler, $extraParameters);
    }

    /**
     * @param array<string, mixed> $extraParameters
     */
    public function options(string $route, mixed $handler, array $extraParameters = []): void
    {
        $this->addRoute('OPTIONS', $route, $handler, $extraParameters);
    }

    /**
     * @return array{array, array, array}
     */
    public function processedRoutes(): array
    {
        $this->buildConfiguration();
        return $this->processedConfiguration;
    }

    private function buildConfiguration(): void
    {
        if ($this->configurationBuilt) {
            return;
        }

        $loader = function (): array {
            $dataGenerator = new MarkBasedDataGenerator();
            
            // Process static routes
            foreach ($this->staticRoutes as $method => $routes) {
                foreach ($routes as $route => [$handler, $extraParameters]) {
                    $dataGenerator->addRoute($method, [$route], $handler, $extraParameters);
                }
            }
            
            // Process variable routes
            foreach ($this->methodToRegexToRoutesMap as $method => $routes) {
                foreach ($routes as $route) {
                    $dataGenerator->addRoute($method, $route->routeData, $route->handler, $route->extraParameters);
                }
            }
            
            $data = $dataGenerator->getData();
            $data[] = $this->namedRoutes;
            
            return $data;
        };

        if ($this->cacheDriver === null || $this->cacheKey === null) {
            $this->processedConfiguration = $loader();
        } else {
            $this->processedConfiguration = $this->cacheDriver->get($this->cacheKey, $loader);
        }

        $this->configurationBuilt = true;
    }

    public function dispatch(string $httpMethod, string $uri): Matched|NotFound|MethodNotAllowed
    {
        $this->buildConfiguration();
        
        [$staticRouteMap, $variableRouteData] = $this->processedConfiguration;

        if (isset($staticRouteMap[$httpMethod][$uri])) {
            $result = new Matched();
            $result->handler = $staticRouteMap[$httpMethod][$uri][0];
            $result->extraParameters = $staticRouteMap[$httpMethod][$uri][1];
            return $result;
        }

        if (isset($variableRouteData[$httpMethod])) {
            $result = $this->dispatchVariableRoute($variableRouteData[$httpMethod], $uri);
            if ($result !== null) {
                return $result;
            }
        }

        if ($httpMethod === 'HEAD') {
            if (isset($staticRouteMap['GET'][$uri])) {
                $result = new Matched();
                $result->handler = $staticRouteMap['GET'][$uri][0];
                $result->extraParameters = $staticRouteMap['GET'][$uri][1];
                return $result;
            }

            if (isset($variableRouteData['GET'])) {
                $result = $this->dispatchVariableRoute($variableRouteData['GET'], $uri);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        if (isset($staticRouteMap['*'][$uri])) {
            $result = new Matched();
            $result->handler = $staticRouteMap['*'][$uri][0];
            $result->extraParameters = $staticRouteMap['*'][$uri][1];
            return $result;
        }

        if (isset($variableRouteData['*'])) {
            $result = $this->dispatchVariableRoute($variableRouteData['*'], $uri);
            if ($result !== null) {
                return $result;
            }
        }

        $allowedMethods = [];

        foreach ($staticRouteMap as $method => $uriMap) {
            if ($method === $httpMethod || !isset($uriMap[$uri])) {
                continue;
            }
            $allowedMethods[] = $method;
        }

        foreach ($variableRouteData as $method => $routeData) {
            if ($method === $httpMethod) {
                continue;
            }
            $result = $this->dispatchVariableRoute($routeData, $uri);
            if ($result === null) {
                continue;
            }
            $allowedMethods[] = $method;
        }

        if ($allowedMethods !== []) {
            $result = new MethodNotAllowed();
            $result->allowedMethods = $allowedMethods;
            return $result;
        }

        return new NotFound();
    }

    private function dispatchVariableRoute(array $routeData, string $uri): ?Matched
    {
        foreach ($routeData as $data) {
            if (preg_match($data['regex'], $uri, $matches) !== 1) {
                continue;
            }

            [$handler, $varNames, $extraParameters] = $data['routeMap'][$matches['MARK']];

            $vars = [];
            $i = 0;
            foreach ($varNames as $varName) {
                $vars[$varName] = $matches[++$i];
            }

            $result = new Matched();
            $result->handler = $handler;
            $result->variables = $vars;
            $result->extraParameters = $extraParameters;

            return $result;
        }

        return null;
    }

    /**
     * @param array<string, string> $substitutions
     */
    public function forRoute(string $name, array $substitutions = []): GeneratedUri
    {
        $this->buildConfiguration();
        $namedRoutes = $this->processedConfiguration[2] ?? [];

        if (!array_key_exists($name, $namedRoutes)) {
            throw UriGenerationException::routeIsUndefined($name);
        }

        $missingParameters = [];

        foreach ($namedRoutes[$name] as $parsedRoute) {
            $missingParameters = $this->missingParameters($parsedRoute, $substitutions);

            if (count($missingParameters) === 0) {
                return $this->generatePath($name, $parsedRoute, $substitutions);
            }
        }

        throw UriGenerationException::insufficientParameters(
            $name,
            $missingParameters,
            array_keys($substitutions),
        );
    }

    private function missingParameters(array $parts, array $substitutions): array
    {
        $missingParameters = [];

        foreach ($parts as $part) {
            if (is_string($part) || array_key_exists($part[0], $substitutions)) {
                continue;
            }
            $missingParameters[] = $part[0];
        }

        return $missingParameters;
    }

    private function generatePath(string $route, array $parsedRoute, array $substitutions): GeneratedUri
    {
        $path = '';

        foreach ($parsedRoute as $part) {
            if (is_string($part)) {
                $path .= $part;
                continue;
            }

            [$parameterName, $regex] = $part;

            if (preg_match('~^' . $regex . '$~u', $substitutions[$parameterName]) !== 1) {
                throw UriGenerationException::parameterDoesNotMatchThePattern($route, $parameterName, $regex);
            }

            $path .= $substitutions[$parameterName];
            unset($substitutions[$parameterName]);
        }

        return new GeneratedUri($path, $substitutions);
    }

    /**
     * Create a new Router instance with caching enabled
     */
    public static function createWithCache(string $cacheKey, ?Cache $cacheDriver = null): self
    {
        return new self($cacheKey, $cacheDriver ?? new FileCache());
    }

    /**
     * Create a new Router instance without caching
     */
    public static function create(): self
    {
        return new self();
    }
}

// Keep all the supporting classes and interfaces as they were defined
// (Route, Matched, MethodNotAllowed, NotFound, GeneratedUri, exceptions, etc.)
// They should remain unchanged from your original code

//<?php

// declare(strict_types=1);

// namespace Phast\Router;

// use Phast\Router\Cache\FileCache;
// use Phast\Router\Dispatcher\Result\Matched;
// use Phast\Router\Dispatcher\Result\MethodNotAllowed;
// use Phast\Router\Dispatcher\Result\NotFound;
// use Phast\Router\Exception\BadRouteException;
// use Phast\Router\Exception\UriGenerationException;
// use Phast\Router\GenerateUri\GeneratedUri;
// use Psr\Http\Message\UriInterface;
// use Stringable;

// Interfaces
interface Cache
{
    /**
     * @param callable(): array $loader
     */
    public function get(string $key, callable $loader): array;
}

interface ConfigureRoutes
{
    public const ROUTE_NAME = '_name';
    public const ROUTE_REGEX = '_route';

    /**
     * @param string|string[] $httpMethod
     * @param array<string, mixed> $extraParameters
     */
    public function addRoute(string|array $httpMethod, string $route, mixed $handler, array $extraParameters = []): void;
    
    public function addGroup(string $prefix, callable $callback): void;
    
    /**
     * @param array<string, mixed> $extraParameters
     */
    public function any(string $route, mixed $handler, array $extraParameters = []): void;
    
    /**
     * @param array<string, mixed> $extraParameters
     */
    public function get(string $route, mixed $handler, array $extraParameters = []): void;
    
    /**
     * @param array<string, mixed> $extraParameters
     */
    public function post(string $route, mixed $handler, array $extraParameters = []): void;
    
    /**
     * @param array<string, mixed> $extraParameters
     */
    public function put(string $route, mixed $handler, array $extraParameters = []): void;
    
    /**
     * @param array<string, mixed> $extraParameters
     */
    public function delete(string $route, mixed $handler, array $extraParameters = []): void;
    
    /**
     * @param array<string, mixed> $extraParameters
     */
    public function patch(string $route, mixed $handler, array $extraParameters = []): void;
    
    /**
     * @param array<string, mixed> $extraParameters
     */
    public function head(string $route, mixed $handler, array $extraParameters = []): void;
    
    /**
     * @param array<string, mixed> $extraParameters
     */
    public function options(string $route, mixed $handler, array $extraParameters = []): void;
    
    /**
     * @return array{array, array, array}
     */
    public function processedRoutes(): array;
}

interface DataGenerator
{
    /**
     * @param array $routeData
     * @param array<string, mixed> $extraParameters
     */
    public function addRoute(string $httpMethod, array $routeData, mixed $handler, array $extraParameters = []): void;
    
    /**
     * @return array{array, array}
     */
    public function getData(): array;
}

interface Dispatcher
{
    public function dispatch(string $httpMethod, string $uri): Matched|NotFound|MethodNotAllowed;
}

interface Exception extends \Throwable {}

interface RouteParser
{
    /**
     * @return array<array>
     */
    public function parse(string $route): array;
}

interface GenerateUri
{
    /**
     * @param array<string, string> $substitutions
     */
    public function forRoute(string $name, array $substitutions = []): GeneratedUri;
}

// Exceptions
namespace Phast\Router\Exception;

class BadRouteException extends \LogicException implements \Phast\Router\Exception
{
    public static function alreadyRegistered(string $route, string $method): self
    {
        return new self("Cannot register two routes matching \"$route\" for method \"$method\"");
    }

    public static function namedRouteAlreadyDefined(string $name): self
    {
        return new self("Cannot register two routes under the name \"$name\"");
    }

    public static function invalidRouteName(mixed $name): self
    {
        return new self('Route name must be a non-empty string, "' . var_export($name, true) . '" given');
    }

    public static function shadowedByVariableRoute(string $route, string $shadowedRegex, string $method): self
    {
        return new self(
            "Static route \"$route\" is shadowed by previously defined variable route \"$shadowedRegex\" for method \"$method\""
        );
    }

    public static function placeholderAlreadyDefined(string $name): self
    {
        return new self("Cannot use the same placeholder \"$name\" twice");
    }

    public static function variableWithCaptureGroup(string $regexPart, string $name): self
    {
        return new self("Regex \"$regexPart\" for parameter \"$name\" contains a capturing group");
    }
}

class UriGenerationException extends \LogicException implements \Phast\Router\Exception
{
    public static function routeIsUndefined(string $name): self
    {
        return new self('There is no route with name "' . $name . '" defined');
    }

    public static function parameterDoesNotMatchThePattern(string $route, string $parameter, string $expectedPattern): self
    {
        return new self(
            "Route \"$route\" expects the parameter [$parameter] to match the regex `$expectedPattern`"
        );
    }

    /**
     * @param array<string> $missingParameters
     * @param array<string> $givenParameters
     */
    public static function insufficientParameters(string $route, array $missingParameters, array $givenParameters): self
    {
        return new self(
            sprintf(
                'Route "%s" expects at least parameter values for [%s], but received %s',
                $route,
                implode(',', $missingParameters),
                count($givenParameters) === 0 ? 'none' : '[' . implode(',', $givenParameters) . ']'
            )
        );
    }
}

// Cache Implementation
namespace Phast\Router\Cache;

use Phast\Router\Cache;
use RuntimeException;

final class FileCache implements Cache
{
    private const DIRECTORY_PERMISSIONS = 0775;
    private const FILE_PERMISSIONS = 0664;

    private static \Closure $emptyErrorHandler;

    public function __construct()
    {
        self::$emptyErrorHandler ??= static function (): void {};
    }

    public function get(string $key, callable $loader): array
    {
        $result = self::readFileContents($key);

        if ($result !== null) {
            return $result;
        }

        $data = $loader();
        self::writeToFile($key, '<?php return ' . var_export($data, true) . ';');

        return $data;
    }

    private static function readFileContents(string $path): ?array
    {
        set_error_handler(self::$emptyErrorHandler);
        $value = include $path;
        restore_error_handler();

        if (!is_array($value)) {
            return null;
        }

        return $value;
    }

    private static function writeToFile(string $path, string $content): void
    {
        $directory = dirname($path);

        if (!self::createDirectoryIfNeeded($directory) || !is_writable($directory)) {
            throw new RuntimeException('The cache directory is not writable "' . $directory . '"');
        }

        set_error_handler(self::$emptyErrorHandler);

        $tmpFile = $path . '.tmp';

        if (file_put_contents($tmpFile, $content, LOCK_EX) === false) {
            restore_error_handler();
            return;
        }

        chmod($tmpFile, self::FILE_PERMISSIONS);

        if (!rename($tmpFile, $path)) {
            unlink($tmpFile);
        }

        restore_error_handler();
    }

    private static function createDirectoryIfNeeded(string $directory): bool
    {
        if (is_dir($directory)) {
            return true;
        }

        set_error_handler(self::$emptyErrorHandler);
        $created = mkdir($directory, self::DIRECTORY_PERMISSIONS, true);
        restore_error_handler();

        return $created !== false || is_dir($directory);
    }
}

// Route Parser
namespace Phast\Router\RouteParser;

use Phast\Router\BadRouteException;
use Phast\Router\RouteParser;

final class Std implements RouteParser
{
    public const VARIABLE_REGEX = <<<'REGEX'
\{
    \s* ([a-zA-Z_][a-zA-Z0-9_-]*) \s*
    (?:
        : \s* ([^{}]*(?:\{(?-1)\}[^{}]*)*)
    )?
\}
REGEX;

    public const DEFAULT_DISPATCH_REGEX = '[^/]+';
    private const CAPTURING_GROUPS_REGEX = '~
                (?:
                    \(\?\(
                  | \[ [^\]\\\\]* (?: \\\\ . [^\]\\\\]* )* \]
                  | \\\\ .
                ) (*SKIP)(*FAIL) |
                \(
                (?!
                    \? (?! <(?![!=]) | P< | \' )
                  | \*
                )
            ~x';

    public function parse(string $route): array
    {
        $routeWithoutClosingOptionals = rtrim($route, ']');
        $numOptionals = strlen($route) - strlen($routeWithoutClosingOptionals);

        $segments = preg_split('~' . self::VARIABLE_REGEX . '(*SKIP)(*F) | \[~x', $routeWithoutClosingOptionals);
        
        if (!is_array($segments)) {
            throw new BadRouteException('Failed to parse route');
        }

        if ($numOptionals !== count($segments) - 1) {
            if (preg_match('~' . self::VARIABLE_REGEX . '(*SKIP)(*F) | \]~x', $routeWithoutClosingOptionals) === 1) {
                throw new BadRouteException('Optional segments can only occur at the end of a route');
            }
            throw new BadRouteException("Number of opening '[' and closing ']' does not match");
        }

        $currentRoute = '';
        $parsedRoutes = [];

        foreach ($segments as $n => $segment) {
            if ($segment === '' && $n !== 0) {
                throw new BadRouteException('Empty optional part');
            }

            $currentRoute .= $segment;
            $parsedRoutes[] = $this->parsePlaceholders($currentRoute);
        }

        return $parsedRoutes;
    }

    private function parsePlaceholders(string $route): array
    {
        if (preg_match_all('~' . self::VARIABLE_REGEX . '~x', $route, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER) === 0) {
            return [$route];
        }

        $offset = 0;
        $routeData = [];
        $parsedVariableNames = [];

        foreach ($matches as $set) {
            if ($set[0][1] > $offset) {
                $routeData[] = substr($route, $offset, $set[0][1] - $offset);
            }

            if (in_array($set[1][0], $parsedVariableNames, true)) {
                throw BadRouteException::placeholderAlreadyDefined($set[1][0]);
            }

            if (isset($set[2])) {
                $this->guardAgainstCapturingGroupUsage(trim($set[2][0]), $set[1][0]);
            }

            $parsedVariableNames[] = $set[1][0];
            $routeData[] = [
                $set[1][0],
                isset($set[2]) ? trim($set[2][0]) : self::DEFAULT_DISPATCH_REGEX,
            ];
            $offset = $set[0][1] + strlen($set[0][0]);
        }

        if ($offset !== strlen($route)) {
            $routeData[] = substr($route, $offset);
        }

        return $routeData;
    }

    private function guardAgainstCapturingGroupUsage(string $regex, string $variableName): void
    {
        if (!str_contains($regex, '(')) {
            return;
        }

        if (preg_match(self::CAPTURING_GROUPS_REGEX, $regex) !== 1) {
            return;
        }

        throw BadRouteException::variableWithCaptureGroup($regex, $variableName);
    }
}

// Data Generator
namespace Phast\Router\DataGenerator;

use Phast\Router\BadRouteException;
use Phast\Router\DataGenerator;
use Phast\Router\Route;

abstract class RegexBasedAbstract implements DataGenerator
{
    protected array $staticRoutes = [];
    protected array $methodToRegexToRoutesMap = [];

    abstract protected function getApproxChunkSize(): int;
    
    /**
     * @param array<string, Route> $regexToRoutesMap
     */
    abstract protected function processChunk(array $regexToRoutesMap): array;

    public function addRoute(string $httpMethod, array $routeData, mixed $handler, array $extraParameters = []): void
    {
        if ($this->isStaticRoute($routeData)) {
            $this->addStaticRoute($httpMethod, $routeData, $handler, $extraParameters);
        } else {
            $this->addVariableRoute($httpMethod, $routeData, $handler, $extraParameters);
        }
    }

    public function getData(): array
    {
        if ($this->methodToRegexToRoutesMap === []) {
            return [$this->staticRoutes, []];
        }

        return [$this->staticRoutes, $this->generateVariableRouteData()];
    }

    private function generateVariableRouteData(): array
    {
        $data = [];
        foreach ($this->methodToRegexToRoutesMap as $method => $regexToRoutesMap) {
            $chunkSize = $this->computeChunkSize(count($regexToRoutesMap));
            $chunks = array_chunk($regexToRoutesMap, $chunkSize, true);
            $data[$method] = array_map([$this, 'processChunk'], $chunks);
        }

        return $data;
    }

    private function computeChunkSize(int $count): int
    {
        $numParts = max(1, round($count / $this->getApproxChunkSize()));
        $size = (int) ceil($count / $numParts);
        
        return $size > 0 ? $size : 1;
    }

    private function isStaticRoute(array $routeData): bool
    {
        return count($routeData) === 1 && is_string($routeData[0]);
    }

    private function addStaticRoute(string $httpMethod, array $routeData, mixed $handler, array $extraParameters): void
    {
        $routeStr = $routeData[0];

        if (isset($this->staticRoutes[$httpMethod][$routeStr])) {
            throw BadRouteException::alreadyRegistered($routeStr, $httpMethod);
        }

        if (isset($this->methodToRegexToRoutesMap[$httpMethod])) {
            foreach ($this->methodToRegexToRoutesMap[$httpMethod] as $route) {
                if ($route->matches($routeStr)) {
                    throw BadRouteException::shadowedByVariableRoute($routeStr, $route->regex, $httpMethod);
                }
            }
        }

        $this->staticRoutes[$httpMethod][$routeStr] = [$handler, $extraParameters];
    }

    private function addVariableRoute(string $httpMethod, array $routeData, mixed $handler, array $extraParameters): void
    {
        $route = new Route($httpMethod, $routeData, $handler, $extraParameters);
        $regex = $route->regex;

        if (isset($this->methodToRegexToRoutesMap[$httpMethod][$regex])) {
            throw BadRouteException::alreadyRegistered($regex, $httpMethod);
        }

        $this->methodToRegexToRoutesMap[$httpMethod][$regex] = $route;
    }
}

final class MarkBased extends RegexBasedAbstract
{
    protected function getApproxChunkSize(): int
    {
        return 30;
    }

    protected function processChunk(array $regexToRoutesMap): array
    {
        $routeMap = [];
        $regexes = [];
        $markName = 'a';

        foreach ($regexToRoutesMap as $regex => $route) {
            $regexes[] = $regex . '(*MARK:' . $markName . ')';
            $routeMap[$markName] = [$route->handler, $route->variables, $route->extraParameters];
            ++$markName;
        }

        $regex = '~^(?|' . implode('|', $regexes) . ')$~';

        return ['regex' => $regex, 'routeMap' => $routeMap];
    }
}

// Dispatcher Results
namespace Phast\Router\Dispatcher\Result;

use ArrayAccess;
use OutOfBoundsException;
use RuntimeException;

final class Matched implements ArrayAccess
{
    public mixed $handler;
    public array $variables = [];
    public array $extraParameters = [];

    public function offsetExists(mixed $offset): bool
    {
        return $offset >= 0 && $offset <= 2;
    }

    public function offsetGet(mixed $offset): mixed
    {
        return match ($offset) {
            0 => \Phast\Router\Dispatcher::FOUND,
            1 => $this->handler,
            2 => $this->variables,
            default => throw new OutOfBoundsException()
        };
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new RuntimeException('Result cannot be changed');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new RuntimeException('Result cannot be changed');
    }
}

final class MethodNotAllowed implements ArrayAccess
{
    public array $allowedMethods;

    public function offsetExists(mixed $offset): bool
    {
        return $offset === 0 || $offset === 1;
    }

    public function offsetGet(mixed $offset): mixed
    {
        return match ($offset) {
            0 => \Phast\Router\Dispatcher::METHOD_NOT_ALLOWED,
            1 => $this->allowedMethods,
            default => throw new OutOfBoundsException(),
        };
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new RuntimeException('Result cannot be changed');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new RuntimeException('Result cannot be changed');
    }
}

final class NotFound implements ArrayAccess
{
    public function offsetExists(mixed $offset): bool
    {
        return $offset === 0;
    }

    public function offsetGet(mixed $offset): mixed
    {
        return match ($offset) {
            0 => \Phast\Router\Dispatcher::NOT_FOUND,
            default => throw new OutOfBoundsException(),
        };
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new RuntimeException('Result cannot be changed');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new RuntimeException('Result cannot be changed');
    }
}

// Dispatcher
namespace Phast\Router\Dispatcher;

use Phast\Router\Dispatcher;
use Phast\Router\Dispatcher\Result\Matched;
use Phast\Router\Dispatcher\Result\MethodNotAllowed;
use Phast\Router\Dispatcher\Result\NotFound;

abstract class RegexBasedAbstract implements Dispatcher
{
    public const NOT_FOUND = 0;
    public const FOUND = 1;
    public const METHOD_NOT_ALLOWED = 2;

    protected array $staticRouteMap = [];
    protected array $variableRouteData = [];

    public function __construct(array $data)
    {
        [$this->staticRouteMap, $this->variableRouteData] = $data;
    }

    abstract protected function dispatchVariableRoute(array $routeData, string $uri): ?Matched;

    public function dispatch(string $httpMethod, string $uri): Matched|NotFound|MethodNotAllowed
    {
        if (isset($this->staticRouteMap[$httpMethod][$uri])) {
            $result = new Matched();
            $result->handler = $this->staticRouteMap[$httpMethod][$uri][0];
            $result->extraParameters = $this->staticRouteMap[$httpMethod][$uri][1];
            return $result;
        }

        if (isset($this->variableRouteData[$httpMethod])) {
            $result = $this->dispatchVariableRoute($this->variableRouteData[$httpMethod], $uri);
            if ($result !== null) {
                return $result;
            }
        }

        if ($httpMethod === 'HEAD') {
            if (isset($this->staticRouteMap['GET'][$uri])) {
                $result = new Matched();
                $result->handler = $this->staticRouteMap['GET'][$uri][0];
                $result->extraParameters = $this->staticRouteMap['GET'][$uri][1];
                return $result;
            }

            if (isset($this->variableRouteData['GET'])) {
                $result = $this->dispatchVariableRoute($this->variableRouteData['GET'], $uri);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        if (isset($this->staticRouteMap['*'][$uri])) {
            $result = new Matched();
            $result->handler = $this->staticRouteMap['*'][$uri][0];
            $result->extraParameters = $this->staticRouteMap['*'][$uri][1];
            return $result;
        }

        if (isset($this->variableRouteData['*'])) {
            $result = $this->dispatchVariableRoute($this->variableRouteData['*'], $uri);
            if ($result !== null) {
                return $result;
            }
        }

        $allowedMethods = [];

        foreach ($this->staticRouteMap as $method => $uriMap) {
            if ($method === $httpMethod || !isset($uriMap[$uri])) {
                continue;
            }
            $allowedMethods[] = $method;
        }

        foreach ($this->variableRouteData as $method => $routeData) {
            if ($method === $httpMethod) {
                continue;
            }
            $result = $this->dispatchVariableRoute($routeData, $uri);
            if ($result === null) {
                continue;
            }
            $allowedMethods[] = $method;
        }

        if ($allowedMethods !== []) {
            $result = new MethodNotAllowed();
            $result->allowedMethods = $allowedMethods;
            return $result;
        }

        return new NotFound();
    }
}

final class MarkBased extends RegexBasedAbstract
{
    protected function dispatchVariableRoute(array $routeData, string $uri): ?Matched
    {
        foreach ($routeData as $data) {
            if (preg_match($data['regex'], $uri, $matches) !== 1) {
                continue;
            }

            [$handler, $varNames, $extraParameters] = $data['routeMap'][$matches['MARK']];

            $vars = [];
            $i = 0;
            foreach ($varNames as $varName) {
                $vars[$varName] = $matches[++$i];
            }

            $result = new Matched();
            $result->handler = $handler;
            $result->variables = $vars;
            $result->extraParameters = $extraParameters;

            return $result;
        }

        return null;
    }
}

// URI Generation
namespace Phast\Router\GenerateUri;

use Phast\Router\Exception\UriGenerationException;
use Phast\Router\GenerateUri;
use Psr\Http\Message\UriInterface;
use Stringable;

final class FromProcessedConfiguration implements GenerateUri
{
    public function __construct(private readonly array $processedConfiguration) {}

    public function forRoute(string $name, array $substitutions = []): GeneratedUri
    {
        if (!array_key_exists($name, $this->processedConfiguration)) {
            throw UriGenerationException::routeIsUndefined($name);
        }

        $missingParameters = [];

        foreach ($this->processedConfiguration[$name] as $parsedRoute) {
            $missingParameters = $this->missingParameters($parsedRoute, $substitutions);

            if (count($missingParameters) === 0) {
                return $this->generatePath($name, $parsedRoute, $substitutions);
            }
        }

        throw UriGenerationException::insufficientParameters(
            $name,
            $missingParameters,
            array_keys($substitutions),
        );
    }

    private function missingParameters(array $parts, array $substitutions): array
    {
        $missingParameters = [];

        foreach ($parts as $part) {
            if (is_string($part) || array_key_exists($part[0], $substitutions)) {
                continue;
            }
            $missingParameters[] = $part[0];
        }

        return $missingParameters;
    }

    private function generatePath(string $route, array $parsedRoute, array $substitutions): GeneratedUri
    {
        $path = '';

        foreach ($parsedRoute as $part) {
            if (is_string($part)) {
                $path .= $part;
                continue;
            }

            [$parameterName, $regex] = $part;

            if (preg_match('~^' . $regex . '$~u', $substitutions[$parameterName]) !== 1) {
                throw UriGenerationException::parameterDoesNotMatchThePattern($route, $parameterName, $regex);
            }

            $path .= $substitutions[$parameterName];
            unset($substitutions[$parameterName]);
        }

        return new GeneratedUri($path, $substitutions);
    }
}

final class GeneratedUri implements Stringable
{
    public function __construct(
        public readonly string $path,
        public readonly array $unmatchedSubstitutions,
    ) {}

    public function asUri(UriInterface $baseUri): UriInterface
    {
        return $baseUri
            ->withPath($this->path)
            ->withQuery(http_build_query($this->unmatchedSubstitutions));
    }

    public function __toString(): string
    {
        return $this->path;
    }
}

// Route Collector
namespace Phast\Router;

use Phast\Router\DataGenerator\MarkBased;
use Phast\Router\RouteParser\Std;

final class RouteCollector implements ConfigureRoutes
{
    protected string $currentGroupPrefix = '';
    private array $namedRoutes = [];

    public function __construct(
        protected readonly RouteParser $routeParser,
        protected readonly DataGenerator $dataGenerator,
    ) {}

    public function addRoute(string|array $httpMethod, string $route, mixed $handler, array $extraParameters = []): void
    {
        $route = $this->currentGroupPrefix . $route;
        $parsedRoutes = $this->routeParser->parse($route);

        $extraParameters = [self::ROUTE_REGEX => $route] + $extraParameters;

        foreach ((array) $httpMethod as $method) {
            foreach ($parsedRoutes as $parsedRoute) {
                $this->dataGenerator->addRoute($method, $parsedRoute, $handler, $extraParameters);
            }
        }

        if (array_key_exists(self::ROUTE_NAME, $extraParameters)) {
            $this->registerNamedRoute($extraParameters[self::ROUTE_NAME], $parsedRoutes);
        }
    }

    private function registerNamedRoute(mixed $name, array $parsedRoutes): void
    {
        if (!is_string($name) || $name === '') {
            throw BadRouteException::invalidRouteName($name);
        }

        if (array_key_exists($name, $this->namedRoutes)) {
            throw BadRouteException::namedRouteAlreadyDefined($name);
        }

        $this->namedRoutes[$name] = array_reverse($parsedRoutes);
    }

    public function addGroup(string $prefix, callable $callback): void
    {
        $previousGroupPrefix = $this->currentGroupPrefix;
        $this->currentGroupPrefix = $previousGroupPrefix . $prefix;
        $callback($this);
        $this->currentGroupPrefix = $previousGroupPrefix;
    }

    public function any(string $route, mixed $handler, array $extraParameters = []): void
    {
        $this->addRoute('*', $route, $handler, $extraParameters);
    }

    public function get(string $route, mixed $handler, array $extraParameters = []): void
    {
        $this->addRoute('GET', $route, $handler, $extraParameters);
    }

    public function post(string $route, mixed $handler, array $extraParameters = []): void
    {
        $this->addRoute('POST', $route, $handler, $extraParameters);
    }

    public function put(string $route, mixed $handler, array $extraParameters = []): void
    {
        $this->addRoute('PUT', $route, $handler, $extraParameters);
    }

    public function delete(string $route, mixed $handler, array $extraParameters = []): void
    {
        $this->addRoute('DELETE', $route, $handler, $extraParameters);
    }

    public function patch(string $route, mixed $handler, array $extraParameters = []): void
    {
        $this->addRoute('PATCH', $route, $handler, $extraParameters);
    }

    public function head(string $route, mixed $handler, array $extraParameters = []): void
    {
        $this->addRoute('HEAD', $route, $handler, $extraParameters);
    }

    public function options(string $route, mixed $handler, array $extraParameters = []): void
    {
        $this->addRoute('OPTIONS', $route, $handler, $extraParameters);
    }

    public function processedRoutes(): array
    {
        $data = $this->dataGenerator->getData();
        $data[] = $this->namedRoutes;

        return $data;
    }
}

// Route Entity
namespace Phast\Router;

final class Route
{
    public readonly string $regex;
    public readonly array $variables;

    public function __construct(
        public readonly string $httpMethod,
        array $routeData,
        public readonly mixed $handler,
        public readonly array $extraParameters,
    ) {
        [$this->regex, $this->variables] = self::extractRegex($routeData);
    }

    private static function extractRegex(array $routeData): array
    {
        $regex = '';
        $variables = [];

        foreach ($routeData as $part) {
            if (is_string($part)) {
                $regex .= preg_quote($part, '~');
                continue;
            }

            [$varName, $regexPart] = $part;
            $variables[$varName] = $varName;
            $regex .= '(' . $regexPart . ')';
        }

        return [$regex, $variables];
    }

    public function matches(string $str): bool
    {
        $regex = '~^' . $this->regex . '$~';
        return (bool) preg_match($regex, $str);
    }
}

// Main Facade
namespace Phast\Router;

use Closure;
use Phast\Router\Cache\FileCache;
use Phast\Router\Dispatcher\MarkBased as MarkBasedDispatcher;
use Phast\Router\GenerateUri\FromProcessedConfiguration;

final class PhastRouter
{
    private ?array $processedConfiguration = null;

    private function __construct(
        private readonly Closure $routeDefinitionCallback,
        private readonly string $routeParser,
        private readonly string $dataGenerator,
        private readonly string $dispatcher,
        private readonly string $routesConfiguration,
        private readonly string $uriGenerator,
        private readonly Cache|string|null $cacheDriver,
        private readonly ?string $cacheKey,
    ) {}

    /**
     * @param Closure(ConfigureRoutes):void $routeDefinitionCallback
     * @param non-empty-string $cacheKey
     */
    public static function create(Closure $routeDefinitionCallback, string $cacheKey): self
    {
        return new self(
            $routeDefinitionCallback,
            RouteParser\Std::class,
            DataGenerator\MarkBased::class,
            Dispatcher\MarkBased::class,
            RouteCollector::class,
            GenerateUri\FromProcessedConfiguration::class,
            FileCache::class,
            $cacheKey,
        );
    }

    public function disableCache(): self
    {
        return new self(
            $this->routeDefinitionCallback,
            $this->routeParser,
            $this->dataGenerator,
            $this->dispatcher,
            $this->routesConfiguration,
            $this->uriGenerator,
            null,
            null,
        );
    }

    private function buildConfiguration(): array
    {
        if ($this->processedConfiguration !== null) {
            return $this->processedConfiguration;
        }

        $loader = function (): array {
            $configuredRoutes = new $this->routesConfiguration(
                new $this->routeParser(),
                new $this->dataGenerator(),
            );

            ($this->routeDefinitionCallback)($configuredRoutes);

            return $configuredRoutes->processedRoutes();
        };

        if ($this->cacheDriver === null) {
            return $this->processedConfiguration = $loader();
        }

        $cache = is_string($this->cacheDriver)
            ? new $this->cacheDriver()
            : $this->cacheDriver;

        return $this->processedConfiguration = $cache->get($this->cacheKey, $loader);
    }

    public function dispatcher(): Dispatcher
    {
        return new $this->dispatcher($this->buildConfiguration());
    }

    public function uriGenerator(): GenerateUri
    {
        return new $this->uriGenerator($this->buildConfiguration()[2]);
    }
}

// // Create a router with caching
// $router = Router::createWithCache('/path/to/cache/file.php');

// // Or create without caching
// $router = Router::create();

// // Define routes
// $router->get('/users', 'UserController::index', ['_name' => 'users.index']);
// $router->get('/users/{id:\d+}', 'UserController::show', ['_name' => 'users.show']);
// $router->post('/users', 'UserController::store');

// // Group routes
// $router->addGroup('/api', function (Router $router) {
//     $router->get('/posts', 'PostController::index');
//     $router->post('/posts', 'PostController::store');
// });

// // Dispatch a request
// $result = $router->dispatch('GET', '/users/123');

// if ($result instanceof Matched) {
//     echo "Handler: " . $result->handler; // UserController::show
//     echo "Parameters: " . print_r($result->variables, true); // ['id' => '123']
// }

// // Generate URLs
// $url = $router->forRoute('users.show', ['id' => 456]);
// echo (string) $url; // "/users/456"