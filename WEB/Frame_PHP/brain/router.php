<?php

declare(strict_types=1);

namespace FastRoute {


    use Throwable;

    interface Exception extends Throwable {}
}

namespace FastRoute {


    /** @phpstan-import-type ProcessedData from ConfigureRoutes */
    interface Cache
    {
        /**
         * @param callable():ProcessedData $loader
         *
         * @return ProcessedData
         */
        public function get(string $key, callable $loader): array;
    }
}

namespace FastRoute {


    /**
     * @phpstan-type ParsedRoute array<string|array{string, string}>
     * @phpstan-type ParsedRoutes list<ParsedRoute>
     */
    interface RouteParser
    {
        /**
         * Parses a route string into multiple route data arrays.
         *
         * The expected output is defined using an example:
         *
         * For the route string "/fixedRoutePart/{varName}[/moreFixed/{varName2:\d+}]", if {varName} is interpreted as
         * a placeholder and [...] is interpreted as an optional route part, the expected result is:
         *
         * [
         *     // first route: without optional part
         *     [
         *         "/fixedRoutePart/",
         *         ["varName", "[^/]+"],
         *     ],
         *     // second route: with optional part
         *     [
         *         "/fixedRoutePart/",
         *         ["varName", "[^/]+"],
         *         "/moreFixed/",
         *         ["varName2", [0-9]+"],
         *     ],
         * ]
         *
         * Here one route string was converted into two route data arrays.
         *
         * @param string $route Route string to parse
         *
         * @return ParsedRoutes Array of route data arrays
         */
        public function parse(string $route): array;
    }
}

namespace FastRoute {


    /**
     * @phpstan-import-type ParsedRoute from RouteParser
     * @phpstan-type ExtraParameters array<string, string|int|bool|float>
     * @phpstan-type StaticRoutes array<string, array<string, array{mixed, ExtraParameters}>>
     * @phpstan-type DynamicRouteChunk array{regex: string, suffix?: string, routeMap: array<int|string, array{mixed, array<string, string>, ExtraParameters}>}
     * @phpstan-type DynamicRouteChunks list<DynamicRouteChunk>
     * @phpstan-type DynamicRoutes array<string, DynamicRouteChunks>
     * @phpstan-type RouteData array{StaticRoutes, DynamicRoutes}
     */
    interface DataGenerator
    {
        /**
         * Adds a route to the data generator. The route data uses the
         * same format that is returned by RouterParser::parser().
         *
         * The handler doesn't necessarily need to be a callable, it
         * can be arbitrary data that will be returned when the route
         * matches.
         *
         * @param ParsedRoute     $routeData
         * @param ExtraParameters $extraParameters
         */
        public function addRoute(string $httpMethod, array $routeData, mixed $handler, array $extraParameters = []): void;

        /**
         * Returns dispatcher data in some unspecified format, which
         * depends on the used method of dispatch.
         *
         * @return RouteData
         */
        public function getData(): array;
    }
}

namespace FastRoute {


    use FastRoute\Dispatcher\Result\Matched;
    use FastRoute\Dispatcher\Result\MethodNotAllowed;
    use FastRoute\Dispatcher\Result\NotMatched;

    interface Dispatcher
    {
        public const NOT_FOUND = 0;
        public const FOUND = 1;
        public const METHOD_NOT_ALLOWED = 2;

        /**
         * Dispatches against the provided HTTP method verb and URI.
         *
         * Returns an object that also has an array shape with one of the following formats:
         *
         *     [self::NOT_FOUND]
         *     [self::METHOD_NOT_ALLOWED, ['GET', 'OTHER_ALLOWED_METHODS']]
         *     [self::FOUND, $handler, ['varName' => 'value', ...]]
         */
        public function dispatch(string $httpMethod, string $uri): Matched|NotMatched|MethodNotAllowed;
    }
}

namespace FastRoute {


    use LogicException;

    use function sprintf;
    use function var_export;

    /** @final */
    class BadRouteException extends LogicException implements Exception
    {
        public static function alreadyRegistered(string $route, string $method): self
        {
            return new self(sprintf('Cannot register two routes matching "%s" for method "%s"', $route, $method));
        }

        public static function namedRouteAlreadyDefined(string $name): self
        {
            return new self(sprintf('Cannot register two routes under the name "%s"', $name));
        }

        public static function invalidRouteName(mixed $name): self
        {
            return new self(sprintf('Route name must be a non-empty string, "%s" given', var_export($name, true)));
        }

        public static function shadowedByVariableRoute(string $route, string $shadowedRegex, string $method): self
        {
            return new self(
                sprintf(
                    'Static route "%s" is shadowed by previously defined variable route "%s" for method "%s"',
                    $route,
                    $shadowedRegex,
                    $method,
                ),
            );
        }

        public static function placeholderAlreadyDefined(string $name): self
        {
            return new self(sprintf('Cannot use the same placeholder "%s" twice', $name));
        }

        public static function variableWithCaptureGroup(string $regexPart, string $name): self
        {
            return new self(sprintf('Regex "%s" for parameter "%s" contains a capturing group', $regexPart, $name));
        }
    }
}

namespace FastRoute {


    use function is_string;
    use function preg_match;
    use function preg_quote;

    /**
     * @internal
     *
     * @phpstan-import-type ExtraParameters from DataGenerator
     * @phpstan-import-type ParsedRoute from RouteParser
     */
    class Route
    {
        public readonly string $regex;

        /** @var array<string, string> $variables */
        public readonly array $variables;

        /**
         * @param ParsedRoute     $routeData
         * @param ExtraParameters $extraParameters
         */
        public function __construct(
            public readonly string $httpMethod,
            array $routeData,
            public readonly mixed $handler,
            public readonly array $extraParameters,
        ) {
            [$this->regex, $this->variables] = self::extractRegex($routeData);
        }

        /**
         * @param ParsedRoute $routeData
         *
         * @return array{string, array<string, string>}
         */
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

        /**
         * Tests whether this route matches the given string.
         */
        public function matches(string $str): bool
        {
            $regex = '~^' . $this->regex . '$~';

            return (bool) preg_match($regex, $str);
        }
    }
}

namespace FastRoute {


    use function array_key_exists;
    use function array_reverse;
    use function is_string;

    /**
     * @phpstan-import-type ProcessedData from ConfigureRoutes
     * @phpstan-import-type ExtraParameters from DataGenerator
     * @phpstan-import-type RoutesForUriGeneration from GenerateUri
     * @phpstan-import-type ParsedRoutes from RouteParser
     * @final
     */
    class RouteCollector implements ConfigureRoutes
    {
        protected string $currentGroupPrefix = '';

        /** @var RoutesForUriGeneration */
        private array $namedRoutes = [];

        public function __construct(
            protected readonly RouteParser $routeParser,
            protected readonly DataGenerator $dataGenerator,
        ) {}

        /** @inheritDoc */
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

        /** @param ParsedRoutes $parsedRoutes */
        private function registerNamedRoute(mixed $name, array $parsedRoutes): void
        {
            if (! is_string($name) || $name === '') {
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

        /** @inheritDoc */
        public function any(string $route, mixed $handler, array $extraParameters = []): void
        {
            $this->addRoute('*', $route, $handler, $extraParameters);
        }

        /** @inheritDoc */
        public function get(string $route, mixed $handler, array $extraParameters = []): void
        {
            $this->addRoute('GET', $route, $handler, $extraParameters);
        }

        /** @inheritDoc */
        public function post(string $route, mixed $handler, array $extraParameters = []): void
        {
            $this->addRoute('POST', $route, $handler, $extraParameters);
        }

        /** @inheritDoc */
        public function put(string $route, mixed $handler, array $extraParameters = []): void
        {
            $this->addRoute('PUT', $route, $handler, $extraParameters);
        }

        /** @inheritDoc */
        public function delete(string $route, mixed $handler, array $extraParameters = []): void
        {
            $this->addRoute('DELETE', $route, $handler, $extraParameters);
        }

        /** @inheritDoc */
        public function patch(string $route, mixed $handler, array $extraParameters = []): void
        {
            $this->addRoute('PATCH', $route, $handler, $extraParameters);
        }

        /** @inheritDoc */
        public function head(string $route, mixed $handler, array $extraParameters = []): void
        {
            $this->addRoute('HEAD', $route, $handler, $extraParameters);
        }

        /** @inheritDoc */
        public function options(string $route, mixed $handler, array $extraParameters = []): void
        {
            $this->addRoute('OPTIONS', $route, $handler, $extraParameters);
        }

        /** @inheritDoc */
        public function processedRoutes(): array
        {
            $data =  $this->dataGenerator->getData();
            $data[] = $this->namedRoutes;

            return $data;
        }

        /**
         * @deprecated
         *
         * @see ConfigureRoutes::processedRoutes()
         *
         * @return ProcessedData
         */
        public function getData(): array
        {
            return $this->processedRoutes();
        }
    }
}

namespace FastRoute {


    /**
     * @phpstan-import-type StaticRoutes from DataGenerator
     * @phpstan-import-type DynamicRoutes from DataGenerator
     * @phpstan-import-type ExtraParameters from DataGenerator
     * @phpstan-import-type RoutesForUriGeneration from GenerateUri
     * @phpstan-type ProcessedData array{StaticRoutes, DynamicRoutes, RoutesForUriGeneration}
     */
    interface ConfigureRoutes
    {
        public const ROUTE_NAME = '_name';
        public const ROUTE_REGEX = '_route';

        /**
         * Registers a new route.
         *
         * The syntax used in the $route string depends on the used route parser.
         *
         * @param string|string[] $httpMethod
         * @param ExtraParameters $extraParameters
         */
        public function addRoute(string|array $httpMethod, string $route, mixed $handler, array $extraParameters = []): void;

        /**
         * Create a route group with a common prefix.
         *
         * All routes created by the passed callback will have the given group prefix prepended.
         */
        public function addGroup(string $prefix, callable $callback): void;

        /**
         * Adds a fallback route to the collection
         *
         * This is simply an alias of $this->addRoute('*', $route, $handler)
         *
         * @param ExtraParameters $extraParameters
         */
        public function any(string $route, mixed $handler, array $extraParameters = []): void;

        /**
         * Adds a GET route to the collection
         *
         * This is simply an alias of $this->addRoute('GET', $route, $handler)
         *
         * @param ExtraParameters $extraParameters
         */
        public function get(string $route, mixed $handler, array $extraParameters = []): void;

        /**
         * Adds a POST route to the collection
         *
         * This is simply an alias of $this->addRoute('POST', $route, $handler)
         *
         * @param ExtraParameters $extraParameters
         */
        public function post(string $route, mixed $handler, array $extraParameters = []): void;

        /**
         * Adds a PUT route to the collection
         *
         * This is simply an alias of $this->addRoute('PUT', $route, $handler)
         *
         * @param ExtraParameters $extraParameters
         */
        public function put(string $route, mixed $handler, array $extraParameters = []): void;

        /**
         * Adds a DELETE route to the collection
         *
         * This is simply an alias of $this->addRoute('DELETE', $route, $handler)
         *
         * @param ExtraParameters $extraParameters
         */
        public function delete(string $route, mixed $handler, array $extraParameters = []): void;

        /**
         * Adds a PATCH route to the collection
         *
         * This is simply an alias of $this->addRoute('PATCH', $route, $handler)
         *
         * @param ExtraParameters $extraParameters
         */
        public function patch(string $route, mixed $handler, array $extraParameters = []): void;

        /**
         * Adds a HEAD route to the collection
         *
         * This is simply an alias of $this->addRoute('HEAD', $route, $handler)
         *
         * @param ExtraParameters $extraParameters
         */
        public function head(string $route, mixed $handler, array $extraParameters = []): void;

        /**
         * Adds an OPTIONS route to the collection
         *
         * This is simply an alias of $this->addRoute('OPTIONS', $route, $handler)
         *
         * @param ExtraParameters $extraParameters
         */
        public function options(string $route, mixed $handler, array $extraParameters = []): void;

        /**
         * Returns the processed aggregated route data.
         *
         * @return ProcessedData
         */
        public function processedRoutes(): array;
    }
}

namespace FastRoute {


    use Closure;
    use FastRoute\Cache\FileCache;

    use function assert;
    use function is_string;

    /** @phpstan-import-type ProcessedData from ConfigureRoutes */
    final class FastRoute
    {
        /** @var ProcessedData|null */
        private ?array $processedConfiguration = null;

        /**
         * @param Closure(ConfigureRoutes):void  $routeDefinitionCallback
         * @param class-string<RouteParser>      $routeParser
         * @param class-string<DataGenerator>    $dataGenerator
         * @param class-string<Dispatcher>       $dispatcher
         * @param class-string<ConfigureRoutes>  $routesConfiguration
         * @param class-string<GenerateUri>      $uriGenerator
         * @param Cache|class-string<Cache>|null $cacheDriver
         * @param non-empty-string|null          $cacheKey
         */
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
         * @param non-empty-string              $cacheKey
         */
        public static function recommendedSettings(Closure $routeDefinitionCallback, string $cacheKey): self
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

        /**
         * @param Cache|class-string<Cache> $driver
         * @param non-empty-string          $cacheKey
         */
        public function withCache(Cache|string $driver, string $cacheKey): self
        {
            return new self(
                $this->routeDefinitionCallback,
                $this->routeParser,
                $this->dataGenerator,
                $this->dispatcher,
                $this->routesConfiguration,
                $this->uriGenerator,
                $driver,
                $cacheKey,
            );
        }

        public function useCharCountDispatcher(): self
        {
            return $this->useCustomDispatcher(DataGenerator\CharCountBased::class, Dispatcher\CharCountBased::class);
        }

        public function useGroupCountDispatcher(): self
        {
            return $this->useCustomDispatcher(DataGenerator\GroupCountBased::class, Dispatcher\GroupCountBased::class);
        }

        public function useGroupPosDispatcher(): self
        {
            return $this->useCustomDispatcher(DataGenerator\GroupPosBased::class, Dispatcher\GroupPosBased::class);
        }

        public function useMarkDispatcher(): self
        {
            return $this->useCustomDispatcher(DataGenerator\MarkBased::class, Dispatcher\MarkBased::class);
        }

        /**
         * @param class-string<DataGenerator> $dataGenerator
         * @param class-string<Dispatcher>    $dispatcher
         */
        public function useCustomDispatcher(string $dataGenerator, string $dispatcher): self
        {
            return new self(
                $this->routeDefinitionCallback,
                $this->routeParser,
                $dataGenerator,
                $dispatcher,
                $this->routesConfiguration,
                $this->uriGenerator,
                $this->cacheDriver,
                $this->cacheKey,
            );
        }

        /** @param class-string<GenerateUri> $uriGenerator */
        public function withUriGenerator(string $uriGenerator): self
        {
            return new self(
                $this->routeDefinitionCallback,
                $this->routeParser,
                $this->dataGenerator,
                $this->dispatcher,
                $this->routesConfiguration,
                $uriGenerator,
                $this->cacheDriver,
                $this->cacheKey,
            );
        }

        /** @return ProcessedData */
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

            assert(is_string($this->cacheKey));

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
}

namespace FastRoute\DataGenerator {


    use FastRoute\BadRouteException;
    use FastRoute\DataGenerator;
    use FastRoute\Route;
    use FastRoute\RouteParser;

    use function array_chunk;
    use function array_map;
    use function assert;
    use function ceil;
    use function count;
    use function is_string;
    use function max;
    use function round;

    /**
     * @internal
     *
     * @phpstan-import-type StaticRoutes from DataGenerator
     * @phpstan-import-type DynamicRouteChunk from DataGenerator
     * @phpstan-import-type DynamicRoutes from DataGenerator
     * @phpstan-import-type RouteData from DataGenerator
     * @phpstan-import-type ExtraParameters from DataGenerator
     * @phpstan-import-type ParsedRoute from RouteParser
     */
    abstract class RegexBasedAbstract implements DataGenerator
    {
        /** @var StaticRoutes */
        protected array $staticRoutes = [];

        /** @var array<string, array<string, Route>> */
        protected array $methodToRegexToRoutesMap = [];

        abstract protected function getApproxChunkSize(): int;

        /**
         * @param array<string, Route> $regexToRoutesMap
         *
         * @return DynamicRouteChunk
         */
        abstract protected function processChunk(array $regexToRoutesMap): array;

        /** @inheritDoc */
        public function addRoute(string $httpMethod, array $routeData, mixed $handler, array $extraParameters = []): void
        {
            if ($this->isStaticRoute($routeData)) {
                $this->addStaticRoute($httpMethod, $routeData, $handler, $extraParameters);
            } else {
                $this->addVariableRoute($httpMethod, $routeData, $handler, $extraParameters);
            }
        }

        /** @inheritDoc */
        public function getData(): array
        {
            if ($this->methodToRegexToRoutesMap === []) {
                return [$this->staticRoutes, []];
            }

            return [$this->staticRoutes, $this->generateVariableRouteData()];
        }

        /** @return DynamicRoutes */
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

        /** @return positive-int */
        private function computeChunkSize(int $count): int
        {
            $numParts = max(1, round($count / $this->getApproxChunkSize()));
            $size = (int) ceil($count / $numParts);
            assert($size > 0);

            return $size;
        }

        /** @param ParsedRoute $routeData */
        private function isStaticRoute(array $routeData): bool
        {
            return count($routeData) === 1 && is_string($routeData[0]);
        }

        /**
         * @param ParsedRoute     $routeData
         * @param ExtraParameters $extraParameters
         */
        private function addStaticRoute(string $httpMethod, array $routeData, mixed $handler, array $extraParameters): void
        {
            $routeStr = $routeData[0];
            assert(is_string($routeStr));

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

        /**
         * @param ParsedRoute     $routeData
         * @param ExtraParameters $extraParameters
         */
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
}

namespace FastRoute\Dispatcher {


    use FastRoute\DataGenerator;
    use FastRoute\Dispatcher;
    use FastRoute\Dispatcher\Result\Matched;
    use FastRoute\Dispatcher\Result\MethodNotAllowed;
    use FastRoute\Dispatcher\Result\NotMatched;

    /**
     * @internal
     *
     * @phpstan-import-type StaticRoutes from DataGenerator
     * @phpstan-import-type DynamicRouteChunk from DataGenerator
     * @phpstan-import-type DynamicRouteChunks from DataGenerator
     * @phpstan-import-type DynamicRoutes from DataGenerator
     * @phpstan-import-type RouteData from DataGenerator
     */
    abstract class RegexBasedAbstract implements Dispatcher
    {
        /** @var StaticRoutes */
        protected array $staticRouteMap = [];

        /** @var DynamicRoutes */
        protected array $variableRouteData = [];

        /** @param RouteData $data */
        public function __construct(array $data)
        {
            [$this->staticRouteMap, $this->variableRouteData] = $data;
        }

        /** @param DynamicRouteChunks $routeData */
        abstract protected function dispatchVariableRoute(array $routeData, string $uri): ?Matched;

        public function dispatch(string $httpMethod, string $uri): Matched|NotMatched|MethodNotAllowed
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

            // For HEAD requests, attempt fallback to GET
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

            // If nothing else matches, try fallback routes
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

            // Find allowed methods for this URI by matching against all other HTTP methods as well
            $allowedMethods = [];

            foreach ($this->staticRouteMap as $method => $uriMap) {
                if ($method === $httpMethod || ! isset($uriMap[$uri])) {
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

            // If there are no allowed methods the route simply does not exist
            if ($allowedMethods !== []) {
                $result = new MethodNotAllowed();
                $result->allowedMethods = $allowedMethods;

                return $result;
            }

            return new NotMatched();
        }
    }
}

namespace FastRoute\Cache {


    use Closure;
    use FastRoute\Cache;
    use FastRoute\ConfigureRoutes;
    use RuntimeException;

    use function chmod;
    use function dirname;
    use function file_put_contents;
    use function is_array;
    use function is_dir;
    use function is_writable;
    use function mkdir;
    use function rename;
    use function restore_error_handler;
    use function set_error_handler;
    use function unlink;
    use function var_export;

    use const LOCK_EX;

    /** @phpstan-import-type ProcessedData from ConfigureRoutes */
    final class FileCache implements Cache
    {
        private const DIRECTORY_PERMISSIONS = 0775;
        private const FILE_PERMISSIONS = 0664;

        /**
         * This is cached in a local static variable to avoid instantiating a closure each time we need an empty handler
         */
        private static Closure $emptyErrorHandler;

        public function __construct()
        {
            self::$emptyErrorHandler ??= static function (): void {};
        }

        /** @inheritdoc */
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

        /** @return ProcessedData|null */
        private static function readFileContents(string $path): ?array
        {
            // error suppression is faster than calling `file_exists()` + `is_file()` + `is_readable()`, especially because there's no need to error here
            set_error_handler(self::$emptyErrorHandler);
            $value = include $path;
            restore_error_handler();

            if (! is_array($value)) {
                return null;
            }

            // @phpstan-ignore-next-line because we won´t be able to validate the array shape in a performant way
            return $value;
        }

        private static function writeToFile(string $path, string $content): void
        {
            $directory = dirname($path);

            if (! self::createDirectoryIfNeeded($directory) || ! is_writable($directory)) {
                throw new RuntimeException('The cache directory is not writable "' . $directory . '"');
            }

            set_error_handler(self::$emptyErrorHandler);

            $tmpFile = $path . '.tmp';

            if (file_put_contents($tmpFile, $content, LOCK_EX) === false) {
                restore_error_handler();

                return;
            }

            chmod($tmpFile, self::FILE_PERMISSIONS);

            if (! rename($tmpFile, $path)) {
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
}

namespace FastRoute\Cache {


    use FastRoute\Cache;
    use Psr\SimpleCache\CacheInterface;

    use function is_array;

    final class Psr16Cache implements Cache
    {
        public function __construct(private readonly CacheInterface $cache) {}

        /** @inheritDoc */
        public function get(string $key, callable $loader): array
        {
            $result = $this->cache->get($key);

            if (is_array($result)) {
                // @phpstan-ignore-next-line because we won´t be able to validate the array shape in a performant way
                return $result;
            }

            $data = $loader();
            $this->cache->set($key, $data);

            return $data;
        }
    }
}

namespace FastRoute\DataGenerator {


    use function count;
    use function implode;

    /** @final */
    class CharCountBased extends RegexBasedAbstract
    {
        protected function getApproxChunkSize(): int
        {
            return 30;
        }

        /** @inheritDoc */
        protected function processChunk(array $regexToRoutesMap): array
        {
            $routeMap = [];
            $regexes = [];

            $suffixLen = 0;
            $suffix = '';
            $count = count($regexToRoutesMap);
            foreach ($regexToRoutesMap as $regex => $route) {
                $suffixLen++;
                $suffix .= "\t";

                $regexes[] = '(?:' . $regex . '/(\t{' . $suffixLen . '})\t{' . ($count - $suffixLen) . '})';
                $routeMap[$suffix] = [$route->handler, $route->variables, $route->extraParameters];
            }

            $regex = '~^(?|' . implode('|', $regexes) . ')$~';

            return ['regex' => $regex, 'suffix' => '/' . $suffix, 'routeMap' => $routeMap];
        }
    }
}

namespace FastRoute\DataGenerator {


    use function count;
    use function implode;
    use function max;
    use function str_repeat;

    /** @final */
    class GroupCountBased extends RegexBasedAbstract
    {
        protected function getApproxChunkSize(): int
        {
            return 10;
        }

        /** @inheritDoc */
        protected function processChunk(array $regexToRoutesMap): array
        {
            $routeMap = [];
            $regexes = [];
            $numGroups = 0;
            foreach ($regexToRoutesMap as $regex => $route) {
                $numVariables = count($route->variables);
                $numGroups = max($numGroups, $numVariables);

                $regexes[] = $regex . str_repeat('()', $numGroups - $numVariables);
                $routeMap[$numGroups + 1] = [$route->handler, $route->variables, $route->extraParameters];

                ++$numGroups;
            }

            $regex = '~^(?|' . implode('|', $regexes) . ')$~';

            return ['regex' => $regex, 'routeMap' => $routeMap];
        }
    }
}

namespace FastRoute\DataGenerator {


    use function count;
    use function implode;

    /** @final */
    class GroupPosBased extends RegexBasedAbstract
    {
        protected function getApproxChunkSize(): int
        {
            return 10;
        }

        /** @inheritDoc */
        protected function processChunk(array $regexToRoutesMap): array
        {
            $routeMap = [];
            $regexes = [];
            $offset = 1;
            foreach ($regexToRoutesMap as $regex => $route) {
                $regexes[] = $regex;
                $routeMap[$offset] = [$route->handler, $route->variables, $route->extraParameters];

                $offset += count($route->variables);
            }

            $regex = '~^(?:' . implode('|', $regexes) . ')$~';

            return ['regex' => $regex, 'routeMap' => $routeMap];
        }
    }
}

namespace FastRoute\DataGenerator {


    use function implode;

    /** @final */
    class MarkBased extends RegexBasedAbstract
    {
        protected function getApproxChunkSize(): int
        {
            return 30;
        }

        /** @inheritDoc */
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
}

namespace FastRoute\Dispatcher {


    use FastRoute\Dispatcher\Result\Matched;

    use function assert;
    use function end;
    use function preg_match;

    /** @final */
    class CharCountBased extends RegexBasedAbstract
    {
        /** @inheritDoc */
        protected function dispatchVariableRoute(array $routeData, string $uri): ?Matched
        {
            foreach ($routeData as $data) {
                assert(isset($data['suffix']));

                if (preg_match($data['regex'], $uri . $data['suffix'], $matches) !== 1) {
                    continue;
                }

                [$handler, $varNames, $extraParameters] = $data['routeMap'][end($matches)];

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
}

namespace FastRoute\Dispatcher {


    use FastRoute\Dispatcher\Result\Matched;

    use function count;
    use function preg_match;

    /** @final */
    class GroupCountBased extends RegexBasedAbstract
    {
        /** @inheritDoc */
        protected function dispatchVariableRoute(array $routeData, string $uri): ?Matched
        {
            foreach ($routeData as $data) {
                if (preg_match($data['regex'], $uri, $matches) !== 1) {
                    continue;
                }

                [$handler, $varNames, $extraParameters] = $data['routeMap'][count($matches)];

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
}

namespace FastRoute\Dispatcher {


    use FastRoute\Dispatcher\Result\Matched;

    use function assert;
    use function preg_match;

    /** @final */
    class GroupPosBased extends RegexBasedAbstract
    {
        /** @inheritDoc */
        protected function dispatchVariableRoute(array $routeData, string $uri): ?Matched
        {
            foreach ($routeData as $data) {
                if (preg_match($data['regex'], $uri, $matches) !== 1) {
                    continue;
                }

                // find first non-empty match
                // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedFor
                for ($i = 1; $matches[$i] === ''; ++$i) {
                }

                assert(isset($i));

                [$handler, $varNames, $extraParameters] = $data['routeMap'][$i];

                $vars = [];
                foreach ($varNames as $varName) {
                    $vars[$varName] = $matches[$i++];
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
}

namespace FastRoute\Dispatcher {


    use FastRoute\Dispatcher\Result\Matched;

    use function preg_match;

    /** @final */
    class MarkBased extends RegexBasedAbstract
    {
        /** @inheritDoc */
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
}

namespace FastRoute\Dispatcher\Result {


    use ArrayAccess;
    use FastRoute\DataGenerator;
    use FastRoute\Dispatcher;
    use OutOfBoundsException;
    use RuntimeException;

    /**
     * @phpstan-import-type ExtraParameters from DataGenerator
     * @implements ArrayAccess<int, Dispatcher::FOUND|mixed|array<string, string>>
     */
    final class Matched implements ArrayAccess
    {
        /** @readonly */
        public mixed $handler;

        /**
         * @readonly
         * @var array<string, string> $variables
         */
        public array $variables = [];

        /**
         * @readonly
         * @var ExtraParameters
         */
        public array $extraParameters = [];

        public function offsetExists(mixed $offset): bool
        {
            return $offset >= 0 && $offset <= 2;
        }

        public function offsetGet(mixed $offset): mixed
        {
            return match ($offset) {
                0 => Dispatcher::FOUND,
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
}

namespace FastRoute\Dispatcher\Result {


    use ArrayAccess;
    use FastRoute\Dispatcher;
    use OutOfBoundsException;
    use RuntimeException;

    /** @implements ArrayAccess<int, Dispatcher::METHOD_NOT_ALLOWED|non-empty-list<string>> */
    final class MethodNotAllowed implements ArrayAccess
    {
        /**
         * @readonly
         * @var non-empty-list<string> $allowedMethods
         */
        public array $allowedMethods;

        public function offsetExists(mixed $offset): bool
        {
            return $offset === 0 || $offset === 1;
        }

        public function offsetGet(mixed $offset): mixed
        {
            return match ($offset) {
                0 => Dispatcher::METHOD_NOT_ALLOWED,
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
}

namespace FastRoute\Dispatcher\Result {


    use ArrayAccess;
    use FastRoute\Dispatcher;
    use OutOfBoundsException;
    use RuntimeException;

    /** @implements ArrayAccess<int, Dispatcher::NOT_FOUND> */
    final class NotMatched implements ArrayAccess
    {
        public function offsetExists(mixed $offset): bool
        {
            return $offset === 0;
        }

        public function offsetGet(mixed $offset): mixed
        {
            return match ($offset) {
                0 => Dispatcher::NOT_FOUND,
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
}

namespace FastRoute {


    use FastRoute\GenerateUri\GeneratedUri;
    use FastRoute\GenerateUri\UriCouldNotBeGenerated;

    /**
     * @phpstan-import-type ParsedRoutes from RouteParser
     * @phpstan-type RoutesForUriGeneration array<non-empty-string, ParsedRoutes>
     * @phpstan-type UriSubstitutions array<non-empty-string, non-empty-string>
     */
    interface GenerateUri
    {
        /**
         * @param UriSubstitutions $substitutions
         *
         * @throws UriCouldNotBeGenerated
         */
        public function forRoute(string $name, array $substitutions = []): GeneratedUri;
    }
}

namespace FastRoute\GenerateUri {


    use FastRoute\GenerateUri;
    use FastRoute\RouteParser;

    use function array_key_exists;
    use function array_keys;
    use function assert;
    use function count;
    use function is_string;
    use function preg_match;

    /**
     * @phpstan-import-type RoutesForUriGeneration from GenerateUri
     * @phpstan-import-type UriSubstitutions from GenerateUri
     * @phpstan-import-type ParsedRoute from RouteParser
     */
    final class FromProcessedConfiguration implements GenerateUri
    {
        /** @param RoutesForUriGeneration $processedConfiguration */
        public function __construct(private readonly array $processedConfiguration) {}

        /** @inheritDoc */
        public function forRoute(string $name, array $substitutions = []): GeneratedUri
        {
            if (! array_key_exists($name, $this->processedConfiguration)) {
                throw UriCouldNotBeGenerated::routeIsUndefined($name);
            }

            $missingParameters = [];

            foreach ($this->processedConfiguration[$name] as $parsedRoute) {
                $missingParameters = $this->missingParameters($parsedRoute, $substitutions);

                // Only attempt to generate the path if we have the necessary info
                if (count($missingParameters) === 0) {
                    return $this->generatePath($name, $parsedRoute, $substitutions);
                }
            }

            assert(count($missingParameters) > 0);

            throw UriCouldNotBeGenerated::insufficientParameters(
                $name,
                $missingParameters,
                array_keys($substitutions),
            );
        }

        /**
         * Returns the expected parameters that were not passed as substitutions
         *
         * @param ParsedRoute      $parts
         * @param UriSubstitutions $substitutions
         *
         * @return list<string>
         */
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

        /**
         * @param ParsedRoute      $parsedRoute
         * @param UriSubstitutions $substitutions
         */
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
                    throw UriCouldNotBeGenerated::parameterDoesNotMatchThePattern($route, $parameterName, $regex);
                }

                $path .= $substitutions[$parameterName];
                unset($substitutions[$parameterName]);
            }

            assert($path !== '');

            return new GeneratedUri($path, $substitutions);
        }
    }
}

namespace FastRoute\GenerateUri {


    use FastRoute\GenerateUri;
    use Psr\Http\Message\UriInterface;
    use Stringable;

    use function http_build_query;

    /** @phpstan-import-type UriSubstitutions from GenerateUri */
    final class GeneratedUri implements Stringable
    {
        /**
         * @param non-empty-string $path
         * @param UriSubstitutions $unmatchedSubstitutions
         */
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
}

namespace FastRoute\GenerateUri {


    use FastRoute\Exception;
    use LogicException;

    use function count;
    use function implode;
    use function sprintf;

    final class UriCouldNotBeGenerated extends LogicException implements Exception
    {
        public static function routeIsUndefined(string $name): self
        {
            return new self('There is no route with name "' . $name . '" defined');
        }

        public static function parameterDoesNotMatchThePattern(
            string $route,
            string $parameter,
            string $expectedPattern,
        ): self {
            return new self(
                sprintf(
                    'Route "%s" expects the parameter [%s] to match the regex `%s`',
                    $route,
                    $parameter,
                    $expectedPattern,
                ),
            );
        }

        /**
         * @param non-empty-list<string> $missingParameters
         * @param list<string>           $givenParameters
         */
        public static function insufficientParameters(
            string $route,
            array $missingParameters,
            array $givenParameters,
        ): self {
            return new self(
                sprintf(
                    'Route "%s" expects at least parameter values for [%s], but received %s',
                    $route,
                    implode(',', $missingParameters),
                    count($givenParameters) === 0 ? 'none' : '[' . implode(',', $givenParameters) . ']',
                ),
            );
        }
    }
}

namespace FastRoute\RouteParser {


    use FastRoute\BadRouteException;
    use FastRoute\RouteParser;

    use function assert;
    use function count;
    use function in_array;
    use function is_array;
    use function preg_match;
    use function preg_match_all;
    use function preg_split;
    use function rtrim;
    use function str_contains;
    use function strlen;
    use function substr;
    use function trim;

    use const PREG_OFFSET_CAPTURE;
    use const PREG_SET_ORDER;

    /**
     * Parses route strings of the following form:
     *
     * "/user/{name}[/{id:[0-9]+}]"
     *
     * @phpstan-import-type ParsedRoute from RouteParser
     * @final
     */
    class Std implements RouteParser
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

        /** @inheritDoc */
        public function parse(string $route): array
        {
            $routeWithoutClosingOptionals = rtrim($route, ']');
            $numOptionals = strlen($route) - strlen($routeWithoutClosingOptionals);

            // Split on [ while skipping placeholders
            $segments = preg_split('~' . self::VARIABLE_REGEX . '(*SKIP)(*F) | \[~x', $routeWithoutClosingOptionals);
            assert(is_array($segments));

            if ($numOptionals !== count($segments) - 1) {
                // If there are any ] in the middle of the route, throw a more specific error message
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

        /**
         * Parses a route string that does not contain optional segments.
         *
         * @return ParsedRoute
         */
        private function parsePlaceholders(string $route): array
        {
            if ((int) preg_match_all('~' . self::VARIABLE_REGEX . '~x', $route, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER) === 0) {
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
            // Needs to have at least a ( to contain a capturing group
            if (! str_contains($regex, '(')) {
                return;
            }

            // Semi-accurate detection for capturing groups
            if (preg_match(self::CAPTURING_GROUPS_REGEX, $regex) !== 1) {
                return;
            }

            throw BadRouteException::variableWithCaptureGroup($regex, $variableName);
        }
    }
}

namespace FastRoute {


    use FastRoute\Cache\FileCache;
    use LogicException;

    use function function_exists;
    use function is_string;

    if (! function_exists('FastRoute\simpleDispatcher')) {
        /**
         * @deprecated since v2.0 and will be removed in v3.0
         *
         * @see FastRoute::recommendedSettings()
         * @see FastRoute::disableCache()
         *
         * @param callable(ConfigureRoutes):void                                                                                                                                                                                                                                                           $routeDefinitionCallback
         * @param array{routeParser?: class-string<RouteParser>, dataGenerator?: class-string<DataGenerator>, dispatcher?: class-string<Dispatcher>, routeCollector?: class-string<ConfigureRoutes>, cacheDisabled?: bool, cacheKey?: string, cacheFile?: string, cacheDriver?: class-string<Cache>|Cache} $options
         */
        function simpleDispatcher(callable $routeDefinitionCallback, array $options = []): Dispatcher
        {
            return \FastRoute\cachedDispatcher(
                $routeDefinitionCallback,
                ['cacheDisabled' => true] + $options,
            );
        }

        /**
         * @deprecated since v2.0 and will be removed in v3.0
         *
         * @see FastRoute::recommendedSettings()
         *
         * @param callable(ConfigureRoutes):void                                                                                                                                                                                                                                                           $routeDefinitionCallback
         * @param array{routeParser?: class-string<RouteParser>, dataGenerator?: class-string<DataGenerator>, dispatcher?: class-string<Dispatcher>, routeCollector?: class-string<ConfigureRoutes>, cacheDisabled?: bool, cacheKey?: string, cacheFile?: string, cacheDriver?: class-string<Cache>|Cache} $options
         */
        function cachedDispatcher(callable $routeDefinitionCallback, array $options = []): Dispatcher
        {
            $options += [
                'routeParser' => RouteParser\Std::class,
                'dataGenerator' => DataGenerator\MarkBased::class,
                'dispatcher' => Dispatcher\MarkBased::class,
                'routeCollector' => RouteCollector::class,
                'cacheDisabled' => false,
                'cacheDriver' => FileCache::class,
            ];

            $loader = static function () use ($routeDefinitionCallback, $options): array {
                $routeCollector = new $options['routeCollector'](
                    new $options['routeParser'](),
                    new $options['dataGenerator']()
                );

                $routeDefinitionCallback($routeCollector);

                return $routeCollector->processedRoutes();
            };

            if ($options['cacheDisabled'] === true) {
                return new $options['dispatcher']($loader());
            }

            $cacheKey = $options['cacheKey'] ?? $options['cacheFile'] ?? null;

            if ($cacheKey === null) {
                throw new LogicException('Must specify "cacheKey" option');
            }

            $cache = $options['cacheDriver'];

            if (is_string($cache)) {
                $cache = new $cache();
            }

            return new $options['dispatcher']($cache->get($cacheKey, $loader));
        }
    }
}
